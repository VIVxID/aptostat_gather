<?php
/*
 *  This file generates SQL-code for populating the database based
 *  on current information from Pingdom and Nagios. If servers or checks
 *  are added to either system, excecute this file to generate new SQL and
 *  run it at the database to update the service list. The generated SQL will
 *  not remove hosts that no longer exist.
 */
$login = file("/var/apto/ping", FILE_IGNORE_NEW_LINES);
$fil = fopen("populate.sql","a+");

$curl = curl_init();

//OBS: Bytt ut loginvariablene med user, password og app key.
$options = array(
    CURLOPT_URL => "https://api.pingdom.com/api/2.0/checks",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_USERPWD => $login[0].":".$login[1],
    CURLOPT_HTTPHEADER => array("App-Key: ".$login[2]),
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($curl,$options);

$response = json_decode(curl_exec($curl),true);
$checkList = $response["checks"];

foreach ($checkList as $check) {

    // Hostnames are set to be unique, to ensure hosts only get saved once.
    fwrite($fil,"INSERT IGNORE INTO Service (Name) VALUES ('".$check["hostname"]."');\n");

}

$curl2 = curl_init();

$options = array(
    CURLOPT_URL => "http://nagios.lon.aptoma.no:8080/state",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($curl,$options);

$response = json_decode(curl_exec($curl),true);
$checkList = $response["content"];

foreach ($checkList as $checkName => $check) {

     fwrite($fil,"INSERT IGNORE INTO Service (Name) VALUES ('".$checkName."');\n");

}