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
$amster = $con->nagState("ams");

$result = array_intersect_assoc($london,$amster);

foreach ($result as $name => $report) {

    foreach ($report as $service) {

        $match = ReportQuery::create()
            ->filterByTimestamp($service["statechange"])
            ->useServiceQuery()
                ->filterByName($name)
            ->endUse()
            ->useGroupsQuery()
                ->filterByProposedFlag('4')
            ->endUse()
            ->filterByCheckType($service["type"])
            ->find();
            
        if (!is_null($match)) {
        
            foreach ($match as $groupId) {
            
                $group = GroupsQuery::create()->findOneByIdGroup($groupId->getIdGroup);
                $group->setProposedFlag($service["state"]);
                $group->save();
                
            }
        } else {
        
            $group = new Groups();
            $group->setProposedFlag($service["state"]);
        
            $serv = ServiceQuery::create()->findOneByIdService($name);
        
            $entry = new Report();
            $entry->setErrorMessage($service["output"]);
            $entry->setTimestamp($service["timestamp"]);
            $entry->setCheckType($service["type"]);
            $entry->setIdSource('1');
            $entry->setIdService($serv);
            
            $group->addReport($report);
            
            $entry->save();
        
        }
    }
}

Logger::writeState("nagios",$result);

$mutex->unlock();
