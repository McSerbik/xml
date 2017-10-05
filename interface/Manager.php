<?php

namespace app\_interface;

interface Manager
{
    /**
     * @param $name
     * city name
     * @return $id
     * id of country or false
     */
    public static function check_country_record($name);

    /**
     * @param $name
     * city name
     * @return $id
     * id of city or false
     */
    public static function check_city_record($name);

    /**
     * @param $name
     * table name
     * @return bool
     * true or false
     */


    /**
     * @param $name
     * table name
     * @return bool
     * true or null
     */
    public static function check_table($name);

    public static function create_table($name);



    /**
     * @param $name
     * procedure name
     * @return bool
     * true or false
     */
    public static function create_procedure($name);


    /**
     * @param $mysqli
     * mysqli connection
     * @param $xml
     * xml file
     * @param $perform
     * prepared template
     * @param $atr
     * xml file attributes
     * @return
     * true or exception
     */
    public static function total_parse($mysqli, $xml, $perform, $atr);


}