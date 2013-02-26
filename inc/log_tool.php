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

            //Reads the logfile, reverses it and gets the key of the first matching report, in order to reduce duplicates.
            $reportFile = file("log/".$name."_report.log");
            rsort($reportFile);
            $search = array_search($report["name"],$reportFile);
            
            if ($search == false) {

                    //Formulates a report with the format DATE TIME - NAME OF SYSTEM is STATUS. Checked with TYPE. MESSAGE
                    $string = date("y.m.d.H.i.s",intval($report["lasterrortime"]))." - ".
                    $report["name"]." is ".$report["status"].". Checked with ".$report["type"].". ".$report["message"]."\n";

                    fwrite($reportHandle,$string);
            
            } else {
            
                $checkDate = explode(".",substr($reportFile[$search],0,17));
                $makeDate = mktime($checkDate[3],$checkDate[4],$checkDate[5],$checkDate[1],$checkDate[2],$checkDate[0]);
            
                if ($makeDate < ($report["lasterrortime"] - 900)) {

                    //Formulates a report with the format DATE TIME - NAME OF SYSTEM is STATUS. Checked with TYPE.
                    $string = date("y.m.d.H.i.s",intval($report["lasterrortime"]))." - ".
                    $report["name"]." is ".$report["status"].". Checked with ".$report["type"].". ".$report["message"]."\n";

                    fwrite($reportHandle,$string);
                }
            }
        }
    }
}
