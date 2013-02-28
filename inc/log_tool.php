<?php
//Log handler for Pingdom&Nagios connections
abstract class Log
{

    public function writeLog($name, $message)
    //Writes a timestamp and a specified message to a general system log.
    {
        $logHandle = fopen("log/".$name.".log","a+");
        fwrite($logHandle,date("y.m.d H.i.s")." - ".$message."\n");
    }

    public function writeReport($name, $array)
    {
    //WriteReport recieves an associative array containing all reports where the status is not "up". See pingdom.php and connect.php
        $reportHandle = fopen("log/".$name."_report.log","a+");
        $message = "";

        foreach ($array as $report) {
        
            //Formulates a report with the format DATE TIME - NAME OF SYSTEM is STATUS. Checked with TYPE.
            $string = date("y.m.d.H.i.s",intval($report["lasterrortime"]))." - ".
            $report["name"]." is ".$report["status"].". Checked with ".$report["type"].".\n";

            fwrite($reportHandle,$string);
        }
    }

    public function writeState($name,$array)
    {
        $reportHandle = fopen("log/".$name."_report.log","a+");

        foreach ($array as $name => $report) {

            $string = $name.": \n";
            fwrite($reportHandle,$string);

            foreach ($report as $service) {

                $string = "       Checked: ".date("y.m.d.H.i.s",intval($service["lastcheck"]))." Changed: ".date("y.m.d.H.i.s",intval($service["statechange"]))." ".$service["state"]." ".
                          $service["type"]." => ".$service["output"]."\n";
                fwrite($reportHandle,$string);
            }
        }
    }
}
