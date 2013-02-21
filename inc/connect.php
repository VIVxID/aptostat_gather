<?php
//
//Gathering from Pingdom/Nagios
//

class Connect
{

    public function ping_fetch($user,$pass,$key)
    {

        $errors = array();

        //Init curl
        $curl = curl_init();

        //Setup curl
        $options = array(
            CURLOPT_URL => "https://api.pingdom.com/api/2.0/checks",
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERPWD => "$user:$pass",
            CURLOPT_HTTPHEADER => array("App-Key: $key"),
            CURLOPT_RETURNTRANSFER => true

        );

        curl_setopt_array($curl,$options);

        //Excecute and save result as an assoc-array
        $response = json_decode(curl_exec($curl),true);

        if (isset($response["error"])) {
            Log::writeLog("pingdom","Pingdom: ".$response["error"]["errormessage"]);
            exit();
        }

        //Filter out all checks where status == "up"
        $checkList = $response["checks"];

        foreach ($checkList as $check) {

            if ($check["status"]  != "up") {

                    $errors[] = $check;
            }
        }
    return $errors;
    }

    public function nag_fetch()
    {
        $errors = array();
        $unixTime = array();
        
        //Setup curl
        $curl = curl_init();

        $options = array(
            CURLOPT_URL => "http://nagios.lon.aptoma.no:8080/log",
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true
        );

        curl_setopt_array($curl,$options);

        $response = json_decode(curl_exec($curl),true);
        $checkList = $response["content"];

        foreach ($checkList as $check) {

            $unixTime[] = substr($check,0,12);
            $alert = substr($check,13,strpos($check,":")-13);

            //Parsing for service alerts
            if ($alert == "SERVICE ALERT") {

                $hostSplit = strpos($check,";");
                $hostEnd = strpos($check,";",$hostSplit+1);
                $host = substr($check,strpos($check,":")+2,$hostSplit-strpos($check,":")-2);
                $host = $host."|".substr($check,$hostSplit+1,$hostEnd-$hostSplit-1);

                $flag = substr($check,$hostEnd+1,strpos($check,";",$hostEnd+1)-$hostEnd-1);

                $message = substr($check,strripos($check,";")+1);

                if ($flag != "OK") {
                    echo $host." - ".$flag." - ".$message."\n";
                }

            }

            //External commands have an additional sub-parameter
            if ($alert == "EXTERNAL COMMAND") {

            }

        }

    return $errors;
    }

}
