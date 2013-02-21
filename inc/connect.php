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
	
	//Setup curl
	
	$curl = curl_init();
	
	$options = array(
            CURLOPT_URL => "http://nagios.lon.aptoma.no:8080/state",
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_RETURNTRANSFER => true
    );
	
	curl_setopt_array($curl,$options);
	
	$response = json_decode(curl_exec($curl),true);
	
	var_dump($response);
	
	}
	
	
}

