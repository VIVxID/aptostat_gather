<?php
require_once '/var/wwwApi/vendor/propel/propel1/runtime/lib/Propel.php';
Propel::init("/var/wwwApi/build/conf/aptostat-conf.php");
set_include_path("/var/wwwApi/build/classes" . PATH_SEPARATOR . get_include_path());

$login = file('/var/apto/ping', FILE_IGNORE_NEW_LINES);
$curl = curl_init();
$m = new \Memcached();
$m->addServer("localhost",11211);
$out = array();
$to = time();
$from = strtotime("-30 days");

$hosts = array(
    "Atika Backoffice" => 615766,
    "DrVideo Encoding" => 615772,
    "DrFront Backoffice" => 615760,
    "DrVideo Backoffice" => 615764,
    "DrVideo CDN" => 615768,
    "DrVideo API" => 615770,
    "DrPublish Backoffice" => 615767,
    "DrPublish API" => 615771);

//Gets uptime history for the last week for every service.
foreach ($hosts as $hostName => $hostID) {

    $options = array(
        CURLOPT_URL => "https://api.pingdom.com/api/2.0/summary.outage/$hostID?from=$from&to=$to",
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_USERPWD => $login[0].":".$login[1],
        CURLOPT_HTTPHEADER => array("App-Key: ".$login[2]),
        CURLOPT_RETURNTRANSFER => true);

    // Execute
    curl_setopt_array($curl,$options);
    $response = json_decode(curl_exec($curl),true);
    $checkList = $response["summary"]["states"];

    foreach ($checkList as $check) {

        //To ensure the returned array is populated with hostnames and dates despite there being no downtime to report.
        if (!isset($out[$hostName][date("d/m/Y",$check["timefrom"])])) {
            $out[$hostName][date("d/m/Y H:i:s",$check["timefrom"])." - ".date("d/m/Y H:i:s",$check["timeto"])] = array();
        }

        if ($check["status"] != "up") {

            $out[$hostName][date("d/m/Y H:i:s",$check["timefrom"])." - ".date("d/m/Y H:i:s",$check["timeto"])][] = $check["timeto"] - $check["timefrom"];

        }
    }
}

$m->set("uptime", $out, 43200);