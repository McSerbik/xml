<?php


require_once('autoload.php');

use app\models\Connect;
use app\models\Reviewer;


if (!Reviewer::check_table('countries'))
    Reviewer::create_table('countries');
if (!Reviewer::check_table('cities'))
    Reviewer::create_table('cities');
if (!Reviewer::check_table('trafficcost'))
    Reviewer::create_table('trafficcost');

Reviewer::create_procedure('GetCountryId');
Reviewer::create_procedure('GetCityId');



$mysqli = Connect::get_connect();

$perform = $mysqli->prepare("INSERT INTO `trafficcost` (`date`, `campaign`, `tizer`, `site`, `money`,`city_id`) VALUES (?, ?, ?, ?,?,?) 
 ON DUPLICATE KEY UPDATE `money` = trafficcost.money + VALUES(`money`) , trafficcost.clicks = trafficcost.clicks + 1 ; ");


$dir = ROOT . "/files";
$catalog = opendir($dir);
$start = microtime(true);

while ($filename = readdir($catalog)) {

    if ($filename != '.' and $filename != '..') {
        $filename = $dir . "/" . $filename;

        if (file_exists($filename)) {
            $xml = file_get_contents($filename);
            $output = iconv(mb_detect_encoding($xml), "UTF-8", $xml);

            $xml = simplexml_load_string(str_replace('windows-1251', "UTF-8", $output));

            Reviewer::total_parse($mysqli, $xml, $perform, $xml->attributes());


        } else {
            exit('Failed to open xml.');
        }

    }
}
closedir($catalog);
$time = microtime(true) - $start;
printf('Script was running  %.1F sec.', $time);









