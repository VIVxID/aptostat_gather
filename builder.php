<?php

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

fwrite($fil,"INSERT IGNORE INTO Flag (IdFlag, Name) VALUES ('1', 'Warning');\n");
fwrite($fil,"INSERT IGNORE INTO Flag (IdFlag, Name) VALUES ('2', 'Critical');\n");
fwrite($fil,"INSERT IGNORE INTO Flag (IdFlag, Name) VALUES ('3', 'Internal');\n");
fwrite($fil,"INSERT IGNORE INTO Flag (IdFlag, Name) VALUES ('4', 'Ignored');\n");
fwrite($fil,"INSERT IGNORE INTO Flag (IdFlag, Name) VALUES ('5', 'Responding');\n");
fwrite($fil,"INSERT IGNORE INTO Flag (IdFlag, Name) VALUES ('6', 'Resolved');\n");

fwrite($fil,"INSERT IGNORE INTO Source (IdSource, Name) VALUES ('1', 'Nagios');\n");
fwrite($fil,"INSERT IGNORE INTO Source (IdSource, Name) VALUES ('2', 'Pingdom');\n");

