<?php
//Connection to Pingdom&Nagios

class CurlService
{

    public function collectPingdom($user,$pass,$key)
    {

        $errors = array();

        $curl = curl_init();

        $options = array(
            CURLOPT_URL => "https://api.pingdom.com/api/2.0/checks",
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERPWD => "$user:$pass",
            CURLOPT_HTTPHEADER => array("App-Key: $key"),
            CURLOPT_RETURNTRANSFER => true

        );

        curl_setopt_array($curl,$options);

        $response = json_decode(curl_exec($curl),true);

        $checkList = $response["checks"];

        foreach ($checkList as $check) {

            if ($check["status"]  == "down") {

                    $errors[] = $check;
            }
        }
    return $errors;
    }

    public function collectNagios($country)
    {

        $curl = curl_init();
        $tmpError = 0;
        $tmpErrors = array();
        $errors = array();

        $options = array(
            CURLOPT_URL => "http://nagios.".$country.".aptoma.no:8080/state",
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true
        );
        curl_setopt_array($curl,$options);

        $response = json_decode(curl_exec($curl),true);
        $checkList = $response["content"];

        //Go through each check on each host in sequence, and fetch errors (if any).
        foreach ($checkList as $checkName => $check) {

            foreach ($check["services"] as $name => $service) {

                if ($service["current_state"] != "0") {

                    $tmpError = 1;
                    $tmpErrors[] = array("output" => $service["plugin_output"],
                                            "type" => $name,
                                            "lastcheck" => $service["last_check"],
                                            "state" => $service["current_state"],
                                            "statechange" => $service["last_state_change"]);
                }
            }

            if ($tmpError == 1) {
                    foreach ($tmpErrors as $tmp) {
                        $errors[$checkName][] = $tmp;
                    }
                }

                $tmpError = 0;
                $tmpErrors = array();

        }

        return $errors;
    }
}
