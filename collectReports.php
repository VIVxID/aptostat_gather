<?php
require_once '/var/wwwApi/vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init("/var/wwwApi/build/conf/aptostat-conf.php");
set_include_path("/var/wwwApi/build/classes" . PATH_SEPARATOR . get_include_path());

require "inc/curlService.php";
require "inc/databaseService.php";

$dbService = new DatabaseService();
$curlService = new CurlService();

//Setup authentication and collect from Pingdom.
$login = file("/var/apto/ping", FILE_IGNORE_NEW_LINES);
$pingResult = $curlService->collectPingdom($login[0],$login[1],$login[2]);

//Collect from Nagios in London and Amsterdam. Intersect and save identical reports.
$london = $curlService->collectNagios("lon");
$amsterdam = $curlService->collectNagios("ams");
$nagResult = array_intersect_assoc($london,$amsterdam);

//Execute Propel.
$dbService->saveNagios($nagResult);
$dbService->savePingdom($pingResult);

//Check to see if any reports should be updated.
$dbService->updateNagios($nagResult);
$dbService->updatePingdom($pingResult);
