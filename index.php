<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
ini_set("error_reporting","E_ALL & ~E_NOTICE");
ini_set("display_errors","0");

// TODO: dependance to user language
setlocale(LC_ALL,"de_DE");
date_default_timezone_set("Europe/Berlin");

// change the following paths if necessary

$yii=dirname(__FILE__). '/../yii/framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);
Yii::createWebApplication($config)->run();
