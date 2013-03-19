#!/usr/bin/php
<?php
//Propel
require_once '/var/www/vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init("/var/www/build/conf/aptostat-conf.php");
set_include_path("/var/www/build/classes" . PATH_SEPARATOR . get_include_path());

//Includes.
require "inc/log_tool.php";
require "inc/connect.php";
require "inc/mutex.php";
require "inc/db.php";

//Initiate objects
$apto = new Aptostat();
$con = new Connect();
$mutex = new Mutex("fetch");

if($mutex->lock()){

    //Setup authentication and collect from Pingdom.
    $login = file("/var/apto/ping", FILE_IGNORE_NEW_LINES);
    $pingResult = $con->pingFetch($login[0],$login[1],$login[2]);

    //Collect from Nagios in London and Amsterdam. Intersect and save identical reports.
    $london = $con->nagFetch("lon");
    $amsterdam = $con->nagFetch("ams");
    $nagResult = array_intersect_assoc($london,$amsterdam);

    //Execute Propel.
    $apto->pingSave($pingResult);
    $apto->nagSave($nagResult);

    //Check to see if any reports should be updated.
    $apto->flagResolvedNagios($nagResult);
    $apto->flagResolvedPingdom($pingResult);

}

$mutex->unlock();
