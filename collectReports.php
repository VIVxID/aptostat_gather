<?php
define("APIPATH", '/var/wwwApi/');
require_once APIPATH . 'vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init(APIPATH . "build/conf/aptostat-conf.php");
set_include_path(APIPATH . "build/classes" . PATH_SEPARATOR . get_include_path());

require "inc/curlService.php";
require "inc/databaseService.php";

$dbService = new DatabaseService();
$curlService = new CurlService();
$mutexService = new MutexService();

if ($mutexService->checkKillswitch()) {
    if ($mutexService->lockCollection()) {

        //Setup authentication and collect from Pingdom.
        $login = file("/var/apto/ping", FILE_IGNORE_NEW_LINES);
        $pingResult = $curlService->collectPingdom($login[0],$login[1],$login[2]);

        //Collect from Nagios in London and Amsterdam. Intersect and save identical reports.
        $london = $curlService->collectNagios("lon");
        $amsterdam = $curlService->collectNagios("ams");
        $nagResult = array_intersect_assoc($london,$amsterdam);

        $dbService->saveNagios($nagResult);
        $dbService->savePingdom($pingResult);

        $dbService->updateNagios($nagResult);
        $dbService->updatePingdom($pingResult);

        $mutexService->unlockCollection();

    } else {
        exit();
    }

} else {
    exit();
}