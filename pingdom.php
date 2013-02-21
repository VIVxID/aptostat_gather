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

$result = $con->ping_fetch($login[0],$login[1],$login[2]);

Log::writeReport("pingdom",$result);

//DATA MANIPULATION GOES HERE.

$mutex->unlock();
