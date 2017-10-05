<?php
ini_set('display_errors', '0');
ignore_user_abort(1);
define('ROOT', dirname(__FILE__));
spl_autoload_call(ROOT . '/interface/Storage');
spl_autoload_call(ROOT . '/interface/Manager');
spl_autoload_call(ROOT . '/models/Connect');
spl_autoload_call(ROOT . '/models/Reviewer');
require_once ROOT . "/config/db.php";
