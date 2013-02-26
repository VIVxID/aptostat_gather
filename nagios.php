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

$result = $con->nag_state();

Log::writeLog("nagios",json_encode($result));

Log::writeState("nagios",$result);

//DATA MANIPULATION GOES HERE.

$mutex->unlock();
