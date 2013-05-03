<?php
require_once 'config.php';
$login = file(CREDENTIALS_FILE, FILE_IGNORE_NEW_LINES);
$curl = curl_init();
$m = new \Memcached();
$m->addServer("localhost",11211);
$out = array();

$options = array(
    CURLOPT_URL => "https://api.pingdom.com/api/2.0/checks",
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_USERPWD => $login[0].":".$login[1],
    CURLOPT_HTTPHEADER => array("App-Key: ".$login[2]),
    CURLOPT_RETURNTRANSFER => true);

curl_setopt_array($curl,$options);
$response = json_decode(curl_exec($curl),true);
$checkList = $response["checks"];

foreach ($checkList as $check) {
    switch ($check["name"]) {
        case "DrVideo Encoding":
            $out["DrVideo Encoding"] = $check["status"];
            break;
        case "DrVideo Backoffice":
            $out["DrVideo Backoffice"] = $check["status"];
            break;
        case "DrVideo CDN":
            $out["DrVideo CDN"] = $check["status"];
            break;
        case "DrVideo API":
            $out["DrVideo API"] = $check["status"];
            break;
        case "DrFront Backoffice":
            $out["DrFront Backoffice"] = $check["status"];
            break;
        case "DrPublish Backoffice":
            $out["DrPublish Backoffice"] = $check["status"];
            break;
        case "DrPublish API":
            $out["DrPublish API"] = $check["status"];
            break;
        case "Atika Backoffice":
            $out["Atika Backoffice"] = $check["status"];
            break;
    }
}

$m->set("live", $out, 600);