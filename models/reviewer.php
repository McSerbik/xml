<?php

namespace app\models;

use app\_interface\Manager;

Class Reviewer implements Manager
{

    public static function check_country_record($name)
    {
        $mysqli = Connect::get_connect();

        $check_tb = "SELECT IF( EXISTS(
                     SELECT `id`
                     FROM `countries`
                     WHERE `name` =  '$name'), (SELECT `id` FROM `countries` WHERE `name`='$name'),0)";

        $res = $mysqli->query($check_tb);
        $row = $res->fetch_array();
        $id = $row[0];
        return $id;
    }

    public static function check_city_record($name)
    {
        $mysqli = Connect::get_connect();

        $check_tb = "SELECT IF( EXISTS(
                     SELECT `id`
                     FROM `cities`
                     WHERE `name` =  '$name'), (SELECT `id` FROM `cities` WHERE `name`='$name'),0)";

        $res = $mysqli->query($check_tb);
        if ($res) {
            $row = $res->fetch_array();
            $id = $row[0];
            return $id;
        }
        return null;
    }


    public static function check_table($name)
    {
        $mysqli = Connect::get_connect();

        $check_tb = "SELECT IF( EXISTS(
                     SELECT COUNT(*)
                     FROM `$name`), 1,0)";

        $res = $mysqli->query($check_tb);
        if ($res) {
            $row = $res->fetch_array();
            if ($row[0])
                return true;
            else return false;
        }

        return false;
    }

    public static function create_table($name)
    {
        $query = null;
        switch ($name) {
            case 'countries':
                $query = "CREATE TABLE IF NOT EXISTS `countries`(
                          `id` bigint(20)  unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
                          `name` varchar(255) UNIQUE NOT NULL)
                          COLLATE='utf8_general_ci' ENGINE=InnoDB";
                break;

            case 'cities':
                $query = "CREATE TABLE IF NOT EXISTS `cities`(
                          `id` bigint(20) unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
                          `country_id` bigint(20) unsigned NOT NULL,
                          `name` varchar(255) UNIQUE NOT NULL,
                          FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON UPDATE CASCADE ON DELETE CASCADE)
                          COLLATE='utf8_general_ci' ENGINE=InnoDB";
                break;
            case 'trafficcost':
                $query = "CREATE TABLE `trafficcost` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `city_id`  bigint(20) unsigned NOT NULL,
                          `date` date DEFAULT NULL,
                          `network` varchar(100) DEFAULT 'vw',
                          `campaign` varchar(100) DEFAULT NULL,
                          `tizer` varchar(100) DEFAULT NULL,
                          `site` varchar(100) DEFAULT NULL,
                          `views` int(11) NOT NULL DEFAULT '0',
                          `clicks` int(11) NOT NULL DEFAULT '0',
                          `money` decimal(10, 4) DEFAULT NULL,
                          PRIMARY KEY(`id`),
                          UNIQUE KEY `all` (`date`,`tizer`,`site`),
                          FOREIGN KEY(`city_id`) REFERENCES `cities`(`id`) ON UPDATE CASCADE ON DELETE CASCADE ,
                          KEY `date` (`date`))
                          COLLATE='utf8_general_ci' ENGINE=InnoDB";
                break;
        }

        if ($query) {
            $mysqli = Connect::get_connect();
            if (!($res = $mysqli->query("$query"))) {
                $trace = debug_backtrace();
                trigger_error(
                    "data for insert is not corresponding template : " . mysqli_error($mysqli) .
                    ' in file ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return null;
            }
        }
        return true;
    }

    public static function create_procedure($name)
    {
        $query = null;

        switch ($name) {
            case 'GetCountryId':
                $query = "CREATE PROCEDURE GetCountryId(
                                IN  _name varchar(255),
                                OUT _id  bigint(20))
                        BEGIN

                        IF EXISTS (select `id` from `countries` where `name` = _name) THEN
	                    SELECT `id` INTO _id
	                    FROM `countries` WHERE `name` = _name;
	                    
                        ELSE
	                    INSERT INTO `countries` (name) VALUES (_name);
	                    SELECT `id` INTO _id
	                    FROM `countries` WHERE `name` = _name;
	                    END IF;
                        END";
                break;

            case 'GetCityId':
                $query = "CREATE PROCEDURE GetCityId(
                                IN  _name varchar(255),
                                IN  _c_id varchar(255),
                                OUT _id  bigint(20))
                        BEGIN

                        IF EXISTS (select `id` from `cities` where `name` = _name) THEN
	                    SELECT `id` INTO _id
	                    FROM `cities` WHERE `name` = _name;
	                    
                        ELSE
	                    INSERT INTO `cities` (name,country_id) VALUES (_name,_c_id);
	                    SELECT `id` INTO _id
	                    FROM `cities` WHERE `name` = _name;
	                    END IF;
                        END";
                break;
        }

        if ($query) {
            $mysqli = Connect::get_connect();
            if (!($res = $mysqli->query("$query"))) {
                $trace = debug_backtrace();
                trigger_error(
                    "procedure has not created : " . mysqli_error($mysqli) .
                    ' in file ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return null;
            }
        }
        return true;

    }

    public static function total_parse($mysqli, $xml, $perform, $atr)
    {
        foreach ($xml as $item) {


            if ($city_id = Reviewer::check_city_record($item->city)) {
                $perform->bind_param('ssssdi', $atr['date'], $item->camp_id, $item->ad_id, $item->hsite2, $item->cost, $city_id);
                $perform->execute();


            } elseif ($country_id = Reviewer::check_country_record($item->country)) {

                $insert = "INSERT INTO `cities` (`country_id`, `name`) VALUES ('$country_id', '$item->city')";

                if (!$mysqli->query($insert))
                    die("Could not insert the data: (" . $mysqli->errno . ") " . $mysqli->error);
                elseif (!$mysqli->query("SET @_id = ''") || !$mysqli->query("CALL GetCityId('$item->country','$country_id',@_id)")) {
                    die("Could not call stored procedure: (" . $mysqli->errno . ") " . $mysqli->error);
                } else {
                    if (!($res = $mysqli->query("SELECT @_id")))
                        die("Could not get the data: (" . $mysqli->errno . ") " . $mysqli->error);


                    $row = $res->fetch_assoc();
                    $city_id = $row['id'];

                    $perform->bind_param('ssssdi', $atr['date'], $item->camp_id, $item->ad_id, $item->hsite2, $item->cost, $city_id);
                    $perform->execute();
                }
            } else {
                if (!$mysqli->query("SET @_id = '' ") || !$mysqli->query("CALL GetCountryId('$item->country',@_id)"))
                    die("Could not call stored procedure: (" . $mysqli->errno . ") " . $mysqli->error);
                elseif (!($res = $mysqli->query("SELECT @_id")))
                    die("Could not get the data: (" . $mysqli->errno . ") " . $mysqli->error);


                $row = $res->fetch_assoc();
                $country_id = $row['@_id'];


                $insert = "INSERT INTO `cities` (`country_id`, `name`) VALUES ($country_id, '$item->city')";

                if (!$mysqli->query($insert))
                    die("Could not insert the data: (" . $mysqli->errno . ") " . $mysqli->error);
                elseif (!$mysqli->query("SET @_id = ''") || !$mysqli->query("CALL GetCityId('$item->city','$country_id',@_id)"))
                    die("Could not call stored procedure: (" . $mysqli->errno . ") " . $mysqli->error);
                elseif (!($res = $mysqli->query("SELECT @_id")))
                    die("Could not get the data: (" . $mysqli->errno . ") " . $mysqli->error);


                $row = $res->fetch_assoc();
                $country_id = $row['id'];

                $perform->bind_param('ssssdi', $atr['date'], $item->camp_id, $item->ad_id, $item->hsite2, $item->cost, $city_id);
                $perform->execute();

            }
        }
        return true;

    }
}