<?php
/*
 *  This file generates SQL-code for populating the database based
 *  on current information from Pingdom and Nagios. If servers or checks
 *  are added to either system, excecute this file to generate new SQL and
 *  run it at the database to update the service list. The generated SQL will
 *  not remove hosts that no longer exist.
 */

echo "Initiating...\n";
require_once 'config.php';
require_once API_PATH . 'vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init(API_PATH . "build/conf/aptostat-conf.php");
set_include_path(API_PATH . "build/classes" . PATH_SEPARATOR . get_include_path());
$login = file(CREDENTIALS_FILE, FILE_IGNORE_NEW_LINES);

// Pingdom
$curl = curl_init();

echo "Setting up connection to Pingdom\n";
$options = array(
    CURLOPT_URL => "https://api.pingdom.com/api/2.0/checks",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_USERPWD => $login[0].":".$login[1],
    CURLOPT_HTTPHEADER => array("App-Key: ".$login[2]),
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($curl,$options);

echo "Connecting to Pingdom...\n";
$response = json_decode(curl_exec($curl),true);

if (isset($response['error'])) {
    echo "Problems with connecting to Pingdom\n";
    echo "Error: " . $response['error']['errormessage'] . "\n";
    exit;
}
$checkList = $response["checks"];
echo "Connection success\n";

foreach ($checkList as $check) {

    echo "Preparing to insert (If it does not exist): " . $check["hostname"] . "\n";
    $con = Propel::getConnection(ServicePeer::DATABASE_NAME);
    $sql = "INSERT IGNORE INTO Service (Name) VALUES(:hostname)";
    $stmt = $con->prepare($sql);
    $stmt->execute(array(':hostname' => $check["hostname"]));
    echo "Insertions successful\n";

}

echo "All Pingdom hostnames successfully inserted\n";

// Nagios
$curl2 = curl_init();

echo "Setting up connection to Nagios\n";
$options = array(
    CURLOPT_URL => "http://nagios.lon.aptoma.no:8080/state",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_RETURNTRANSFER => true
);

curl_setopt_array($curl,$options);

echo "Connecting to Nagios...\n";
$response = json_decode(curl_exec($curl),true);

$checkList = $response["content"];
echo "Connection success\n";

foreach ($checkList as $checkName => $check) {

    echo "Preparing to insert (If it does not exist): " . $checkName . "\n";
    $con = Propel::getConnection(ServicePeer::DATABASE_NAME);
    $sql = "INSERT IGNORE INTO Service (Name) VALUES(:hostname)";
    $stmt = $con->prepare($sql);
    $stmt->execute(array(':hostname' => $checkName));
    echo "Insertions successful\n";

}
echo "All Nagios hostnames successfully inserted\n";

echo "Script finished\n";
exit;