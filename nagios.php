#!/usr/bin/php
<?php

//Logfile located in log/pingdom.log
require "inc/log_tool.php";
require "inc/connect.php";
require "inc/mutex.php";

$con = new Connect();
$mutex = new Mutex("nagios");

$mutex->lock();

//
//Connect and retrieve from Pingdom.
//

$london = $con->nagState("lon");
$amster = $con->nagState("ams");

$result = array_intersect_assoc($london,$amster);

Log::writeState("nagios",$result);

//DATA MANIPULATION GOES HERE.

$mutex->unlock();
