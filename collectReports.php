<?php
require_once 'config.php';
require_once API_PATH . 'vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init(API_PATH . "build/conf/aptostat-conf.php");
set_include_path(API_PATH . "build/classes" . PATH_SEPARATOR . get_include_path());

require "inc/CurlService.php";
require "inc/DatabaseService.php";
require "inc/MutexService.php";

$dbService = new DatabaseService();
$curlService = new CurlService();
$mutexService = new MutexService();

if ($mutexService->checkKillswitch()) {
    if ($mutexService->lockCollection()) {

        //Setup authentication and collect from Pingdom.
        $login = file(CREDENTIALS_FILE, FILE_IGNORE_NEW_LINES);
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
