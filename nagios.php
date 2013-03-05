#!/usr/bin/php
<?php
//Propel
require_once '/var/www/vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init("/var/www/build/conf/aptostat-conf.php");
set_include_path("/var/www/build/classes" . PATH_SEPARATOR . get_include_path());

require "inc/log_tool.php";
require "inc/connect.php";
require "inc/mutex.php";

$con = new Connect();
$mutex = new Mutex("nagios");

$mutex->lock();

$london = $con->nagState("lon");
$amsterdam = $con->nagState("ams");

$result = array_intersect_assoc($london,$amsterdam);

foreach ($result as $name => $report) {

    foreach ($report as $service) {

        //Checks the database for matching invisible reports.
        $matchInvis = ReportQuery::create()
            ->filterByTimestamp($service["statechange"])
            ->useServiceQuery()
                ->filterByName($name)
            ->endUse()
            ->useGroupsQuery()
                ->filterByProposedFlag('4')
            ->endUse()
            ->filterByIdSource('1')
            ->filterByCheckType($service["type"])
            ->findOne();

        //Checks the database for matching visible reports.
        $matchVis = ReportQuery::create()
            ->filterByTimestamp($service["statechange"])
            ->useServiceQuery()
                ->filterByName($name)
            ->endUse()
            ->filterByIdSource('1')
            ->filterByCheckType($service["type"])
            ->findOne();

        if (!is_null($matchInvis)) {

            $group = GroupsQuery::create()->findPK($matchInvis->getIdGroup());
            $group->setProposedFlag($service["state"]);
            $group->save();

        } elseif (is_null($matchVis)) {

            $group = new Groups();
            $group->setProposedFlag('4');

            $serv = ServiceQuery::create()->findByName($name);

            foreach ($serv as $tmp) {

                $servId = $tmp->getIdService();

            }

            $entry = new Report();
            $entry->setErrorMessage($service["output"]);
            $entry->setTimestamp($service["statechange"]);
            $entry->setCheckType($service["type"]);
            $entry->setIdSource('1');
            $entry->setIdService($servId);

            $group->addReport($entry);

            $entry->save();

        }
    }
}

Logger::writeState("nagios",$result);

$mutex->unlock();
