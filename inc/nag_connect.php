<?php

//
//Gathering from Nagios - TEMP
//

Class Connect_Nag
{

    public function Fetch()
    {

        $curl = curl_init("hoff.lon.aptoma.no/nagioslog.json");
        $result = curl_exec($curl);
        $stuff = xmlrpc_decode($result);
        var_dump($stuff);

    }
}
