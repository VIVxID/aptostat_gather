#!/usr/bin/php
<?php
//Propel
require_once '/var/www/vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init("/var/www/build/conf/aptostat-conf.php");
set_include_path("/var/www/build/classes" . PATH_SEPARATOR . get_include_path());

//Logfile located in log/pingdom.log
require "inc/log_tool.php";
require "inc/connect.php";
require "inc/mutex.php";
require "inc/db.php";

$apto = new Aptostat();
$con = new Connect();
$mutex = new Mutex("pingdom");

$mutex->lock();

//
//Connect and retrieve from Pingdom and Nagios.
//

$login = file("/var/apto/ping", FILE_IGNORE_NEW_LINES);
$pingResult = $con->pingFetch($login[0],$login[1],$login[2]);

$london = $con->nagState("lon");
$amsterdam = $con->nagState("ams");
$nagResult = array_intersect_assoc($london,$amsterdam);

$apto->pingSave($pingResult);
$apto->nagSave($nagResult);

$apto->groupReports();

$mutex->unlock();
