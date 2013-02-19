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
    //WriteReport recieves an associative array containing all reports where the status is not "up". See pingdom.php and ping_connect.php
        $reportHandle = fopen("log/".$name."_report.log","a+");

        foreach ($array as $report) {

            $list = array();
            $reportFile = file("log/".$name."_report.log");

            //Formulates a report with the format ID DATE TIME - NAME OF SYSTEM is STATUS. Checked with TYPE on host HOSTNAME.
            $string = $report["id"]." ".date("y.m.d.H.i.s",$report["lasterrortime"])." - ".
            $report["name"]." is ".$report["status"].". Checked with ".
            $report["type"]." on host ".$report["hostname"]."\n";


            $search = array_keys($reportFile,$report["id"]);

            //Gets an array of previous reports on the same system from the logfile.
            foreach ($search as $key) {
                $list[] = $reportFile[$key];
            }

            rsort($list);

            //Creates a UNIX timestamp from the most recent matching report and compares it to the current report.
            //If the most recent report is less than 30 minutes old, a new report will not be made.
            $checkDate = explode(".",substr($list[0],strpos($list[0]," ")+1,17));
            $makeDate = mktime($checkDate[3],$checkDate[4],$checkDate[5],$checkDate[1],$checkDate[2],$checkDate[0]);

            if ($makeDate < ($report["lasterrortime"] - 1800)) { 

                fwrite($reportHandle,$string);

            }
        }
    }
}
