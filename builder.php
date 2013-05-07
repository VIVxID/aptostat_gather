<?php
/*
 *  This file generates SQL-code for populating the database based
 *  on current information from Pingdom and Nagios. If servers or checks
 *  are added to either system, excecute this file to generate new SQL and
 *  run it at the database to update the service list. The generated SQL will
 *  not remove hosts that no longer exist.
 */

echo "\tInitiating...\n";
require_once 'config.php';
require_once API_PATH . 'vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init(API_PATH . "build/conf/aptostat-conf.php");
set_include_path(API_PATH . "build/classes" . PATH_SEPARATOR . get_include_path());
$login = file(CREDENTIALS_FILE, FILE_IGNORE_NEW_LINES);

// Pingdom
$curl = curl_init();

echo "\tSetting up connection to Pingdom\n";
$options = array(
    CURLOPT_URL => "https://api.pingdom.com/api/2.0/checks",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_USERPWD => $login[0].":".$login[1],
    CURLOPT_HTTPHEADER => array("App-Key: ".$login[2]),
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($curl,$options);

echo "\tConnecting to Pingdom...\n";
$response = json_decode(curl_exec($curl),true);

if (isset($response['error'])) {
    echo "\t" .chr(27). "[0;31m Problems with connecting to Pingdom\n";
    echo "\tError: " . $response['error']['errormessage'] . chr(27) . "[0m\n";
    exit;
}
$checkList = $response["checks"];
echo "\tConnection success\n";

echo "\tPreparing to insert hostnames (Ignore if they exist)\n";
foreach ($checkList as $check) {

    $con = Propel::getConnection(ServicePeer::DATABASE_NAME);
    $sql = "INSERT IGNORE INTO Service (Name) VALUES(:hostname)";
    $stmt = $con->prepare($sql);
    $stmt->execute(array(':hostname' => $check["hostname"]));
    echo chr(27). "[1;32m." .chr(27). "[0m";

}

echo "\n\tAll Pingdom hostnames successfully inserted\n";

// Nagios
$curl2 = curl_init();

echo "\tSetting up connection to Nagios\n";
$options = array(
    CURLOPT_URL => "http://nagios.lon.aptoma.no:8080/state",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($curl,$options);

echo "\tConnecting to Nagios...\n";
$response = json_decode(curl_exec($curl),true);

$checkList = $response["content"];
echo "\tConnection success\n";

echo "\tPreparing to insert hostnames (Ignore if they exist)\n";
foreach ($checkList as $checkName => $check) {

    $con = Propel::getConnection(ServicePeer::DATABASE_NAME);
    $sql = "INSERT IGNORE INTO Service (Name) VALUES(:hostname)";
    $stmt = $con->prepare($sql);
    $stmt->execute(array(':hostname' => $checkName));
    echo chr(27). "[1;32m." .chr(27). "[0m";

}
echo "\tAll Nagios hostnames successfully inserted\n";

echo "\n\t" .chr(27). "[1;32m ------------ Script finished ------------" .chr(27). "[0m\n";
exit;
