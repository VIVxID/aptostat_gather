#!/usr/bin/php
<?php

//Logfile located in log/pingdom.log
require "inc/log_tool.php";
require "inc/connect.php";
require "inc/mutex.php";

$con = new Connect();
$mutex = new Mutex("pingdom");

$mutex->lock();

//
//Connect and retrieve from Pingdom.
//

$login = file("/var/apto/ping", FILE_IGNORE_NEW_LINES);

$result = $con->pingFetch($login[0],$login[1],$login[2]);

foreach ($result as $report) {

    //Checks the database for matching reports.
    $match = ReportQuery::create()
        ->useServiceQuery()
            ->filterByName($report["hostname"])
        ->endUse()
        ->filterByIdSource('2')
        ->useGroupsQuery()
            ->filterByProposedFlag('2')
        ->endUse()
        ->filterByCheckType($service["type"])
        ->filterByErrorMessage($service["status"])
        ->findOne();

    if (is_null($match)) {

        $group = new Groups();
        $group->setProposedFlag('2');

        $serv = ServiceQuery::create()->findByName($report["hostname"]);

        foreach ($serv as $tmp) {

            $servId = $tmp->getIdService();

        }

        $entry = new Report();
        $entry->setErrorMessage($report["status"]);
        $entry->setTimestamp($report["lasterrortime"]);
        $entry->setCheckType($report["type"]);
        $entry->setIdSource('1');
        $entry->setIdService($servId);

        $group->addReport($entry);

        $entry->save();

    }
}


Logger::writeReport("pingdom",$result);

//DATA MANIPULATION GOES HERE.

$mutex->unlock();
