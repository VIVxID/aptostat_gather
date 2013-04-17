<?php
//Database management via Propel

class Aptostat
{

    function savePingdom($pingResult)
    {

        foreach ($pingResult as $report) {

            //Checks the database for matching reports.
            $match = ReportQuery::create()
                ->useServiceQuery()
                    ->filterByName($report["hostname"])
                ->endUse()
                ->filterBySource('PINGDOM')
                ->filterByCheckType($report["type"])
                ->join('Report.ReportStatus')
                ->withColumn('MAX(ReportStatus.Timestamp)', 'StatusTime')
                ->withColumn('ReportStatus.Flag', 'Flag')
                ->findOne();

            //If no matching report was found, create it.
            if (is_null($match)) {

                if($report["status"] == "down") {
                    $pingdomStatus = "CRITICAL";
                } else {
                    $pingdomStatus = "WARNING";
                }

                $flag = new ReportStatus();
                $flag->setFlag($pingdomStatus);
                $flag->setTimestamp(time());

                $serv = ServiceQuery::create()->findOneByName($report["hostname"]);

                $entry = new Report();
                $entry->setErrorMessage($report["status"]);
                $entry->setTimestamp($report["lasterrortime"]);
                $entry->setCheckType($report["type"]);
                $entry->setSource('PINGDOM');
                $entry->setIdService($serv->getIdService());
                $entry->save();

                $flag->setIdReport($entry->getIdReport());
                $flag->save();

            }
        }
    }

    function saveNagios($nagResult)
    {

        foreach ($nagResult as $name => $report) {

            foreach ($report as $service) {

                //Checks the database for matching reports.
                $match = ReportQuery::create()
                    ->useServiceQuery()
                        ->filterByName($name)
                    ->endUse()
                    ->filterByCheckType($service["type"])
                    ->filterBySource('NAGIOS')
                    ->join('Report.ReportStatus')
                    ->withColumn('MAX(ReportStatus.Timestamp)', 'StatusTime')
                    ->withColumn('ReportStatus.Flag', 'Flag')
                    ->findOne();

                //If no matching report was found, create it.
                if (is_null($match) or $match->getFlag() == 6) {

                    if ($service["state"] == '2') {
                        $nagiosStatus = "CRITICAL";
                    } elseif ($service["state"] == '1'){
                        $nagiosStatus = "WARNING";
                    }

                    $flag = new ReportStatus();
                    $flag->setFlag($nagiosStatus);
                    $flag->setTimestamp(time());

                    $serv = ServiceQuery::create()->findOneByName($name);

                    $entry = new Report();
                    $entry->setErrorMessage($service["output"]);
                    $entry->setTimestamp($service["statechange"]);
                    $entry->setCheckType($service["type"]);
                    $entry->setSource('NAGIOS');
                    $entry->setIdService($serv->getIdService());
                    $entry->save();

                    $flag->setIdReport($entry->getIdReport());
                    $flag->save();

                }
            }
        }
    }

    function updatePingdom($pingdom)
    {

        $found = 0;

        //Fetch all relevant information about existing unresolved reports.
        $pingReports = ReportQuery::create()
            ->filterBySource('PINGDOM')
            ->join('Report.Service')
            ->withColumn('Service.Name', 'ServiceName')
            ->join('Report.ReportStatus')
            ->withColumn('MAX(ReportStatus.Timestamp)', 'StatusTime')
            ->withColumn('ReportStatus.Flag', 'Flag')
            ->find();

        //Compare unresolved reports with the checklist received from Pingdom.
        foreach ($pingReports as $query) {

            foreach ($pingdom as $report) {

                if ($query->getServiceName() == $report["hostname"] and $query->getCheckType() == $report["type"]) {

                    $found = 1;

                }
            }

            //If a matching report is not found and the newest status is an error, flag as responding.
            if ($found == 0 and $query->getFlag() == "WARNING" || "CRITICAL") {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setFlag('RESPONDING');
                $update->setTimestamp(time());
                $update->save();

            //If a matching report is found and the newest status is responding, flag as an error.
            } elseif ($found == 1 and $query->getFlag() == "RESPONDING") {

                if($report["status"] == "down") {
                    $pingdomStatus = "CRITICAL";
                } else {
                    $pingdomStatus = "WARNING";
                }

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setFlag($pingdomStatus);
                $update->setTimestamp(time());
                $update->save();

            //If a matching report is not found, the newest status is responding, and it has been responding for a day,
            //flag as resolved.
            } elseif ($found == 0 and $query->getFlag() == "RESPONDING" and $query->getTimestamp() < (time()-86400)) {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setFlag('RESOLVED');
                $update->setTimestamp(time());
                $update->save();

            }

            $found = 0;

        }
    }

    function updateNagios($nagios)
    {

        $found = 0;

        //Fetch all relevant information about existing unresolved reports.
        $nagReports = ReportQuery::create()
            ->filterBySource('NAGIOS')
            ->join('Report.Service')
            ->withColumn('Service.Name', 'ServiceName')
            ->join('Report.ReportStatus')
            ->withColumn('MAX(ReportStatus.Timestamp)', 'StatusTime')
            ->withColumn('ReportStatus.Flag', 'Flag')
            ->find();


        //Compare unresolved reports with the checklist received from Nagios.
        foreach ($nagReports as $query) {

            foreach ($nagios as $name => $report) {

                foreach ($report as $service) {

                    if ($query->getServiceName() == $name and $query->getCheckType() == $service["type"]){

                        $found = 1;

                    }
                }
            }

            //If a matching report is not found and the newest status is not responding, set as responding.
            if ($found == 0 and $query->getFlag() == "WARNING" || "CRITICAL") {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setFlag('RESPONDING');
                $update->setTimestamp(time());
                $update->save();

            //If a matching report is found and the newest status is responding, flag as an error.
            } elseif ($found == 1 and $query->getFlag() == "RESPONDING") {

                if ($service["state"] == '2') {
                    $nagiosStatus = "CRITICAL";
                } elseif ($service["state"] == '1'){
                    $nagiosStatus = "WARNING";
                }

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setFlag($nagiosStatus);
                $update->setTimestamp(time());
                $update->save();

            //If a matching report is not found, the newest status is responding, and it has been responding for a day,
            //flag as resolved.
            } elseif ($found = 0 and $query->getFlag() == "RESPONDING" and $query->getTimestamp() < (time()-86400 )) {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setFlag('RESOLVED');
                $update->setTimestamp(time());
                $update->save();

            }

            $found = 0;

        }
    }
}
