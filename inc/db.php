<?php
//Database management via Propel

class Aptostat
{

    function pingSave($pingResult)
    {

        foreach ($pingResult as $report) {

            //Checks the database for matching reports.
            $match = ReportQuery::create()
                ->useServiceQuery()
                    ->filterByName($report["hostname"])
                ->endUse()
                ->filterByIdSource('2')
                ->join('Report.ReportStatus')
		->where('ReportStatus.Timestamp IN (SELECT MAX(Timestamp) FROM ReportStatus WHERE Report.IdReport = ReportStatus.IdReport)')
                ->withColumn('ReportStatus.IdFlag', 'Flag')
                ->filterByCheckType($report["type"])
                ->findOne();

            //If a reported error does not already exist in the database, save it.
            if (is_null($match) or $match->getFlag() == 6) {

                $flag = new ReportStatus();
                $flag->setIdFlag('2');
                $flag->setTimestamp(time());

                $serv = ServiceQuery::create()->findOneByName($report["hostname"]);

                $entry = new Report();
                $entry->setErrorMessage($report["status"]);
                $entry->setTimestamp($report["lasterrortime"]);
                $entry->setCheckType($report["type"]);
                $entry->setIdSource('2');
                $entry->setIdService($serv->getIdService());
                $entry->save();

                $flag->setIdReport($entry->getIdReport());
                $flag->save();

            }
        }
    }

    function nagSave($nagResult)
    {

        foreach ($nagResult as $name => $report) {

            foreach ($report as $service) {

                //Checks the database for matching reports.
                $match = ReportQuery::create()
                    ->useServiceQuery()
                        ->filterByName($name)
                    ->endUse()
                    ->filterByIdSource('1')
                    ->join('Report.ReportStatus')
                    ->where('ReportStatus.Timestamp IN (SELECT MAX(Timestamp) FROM ReportStatus WHERE Report.IdReport = ReportStatus.IdReport)')
                    ->withColumn('ReportStatus.IdFlag', 'Flag')
                    ->filterByCheckType($service["type"])
                    ->findOne();

                //If no matching report is found, create it.
                if (is_null($match) or $match->getFlag() == 6) {

                    $flag = new ReportStatus();
                    $flag->setIdFlag($service["state"]);
                    $flag->setTimestamp(time());

                    $serv = ServiceQuery::create()->findOneByName($name);

                    $entry = new Report();
                    $entry->setErrorMessage($service["output"]);
                    $entry->setTimestamp($service["statechange"]);
                    $entry->setCheckType($service["type"]);
                    $entry->setIdSource('1');
                    $entry->setIdService($serv->getIdService());
                    $entry->save();

                    $flag->setIdReport($entry->getIdReport());
                    $flag->save();

                }
            }
        }
    }

    function flagResolvedPingdom($pingdom)
    {

        $found = 0;

        //Fetch all relevant information about existing unresolved reports.
        $pingReports = ReportQuery::create()
            ->join('Report.ReportStatus')
            ->where('ReportStatus.Timestamp IN (SELECT MAX(Timestamp) FROM ReportStatus WHERE Report.IdReport = ReportStatus.IdReport)')
            ->withColumn('ReportStatus.IdFlag', 'Flag')
            ->join('Report.Service')
            ->withColumn('Service.Name', 'ServiceName')
            ->filterByIdSource('2')
            ->find();

        //Compare unresolved reports with the checklist recieved from Pingdom.
        foreach ($pingReports as $query) {

            foreach ($pingdom as $report) {

                if ($query->getServiceName() == $report["hostname"] and $query->getCheckType() == $report["type"]) {

                    $found = 1;

                }
            }

            //If a matching report is not found and the newest status is an error, flag as responding.
            if ($found == 0 and $query->getFlag() == 2) {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setIdFlag('5');
                $update->setTimestamp(time());
                $update->save();

            //If a matching report is found and the newest status is responding, flag as an error.
            } elseif ($found == 1 and $query->getFlag() == 5) {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setIdFlag('2');
                $update->setTimestamp(time());
                $update->save();

            }

            $found = 0;

        }
    }

    function flagResolvedNagios($nagios)
    {

        $found = 0;

        //Fetch all relevant information about existing unresolved reports.
        $nagReports = ReportQuery::create()
            ->join('Report.ReportStatus')
            ->where('ReportStatus.Timestamp IN (SELECT MAX(Timestamp) FROM ReportStatus WHERE Report.IdReport = ReportStatus.IdReport)')
            ->withColumn('ReportStatus.IdFlag', 'Flag')
            ->join('Report.Service')
            ->withColumn('Service.Name', 'ServiceName')
            ->filterByIdSource('1')
            ->find();


        //Compare unresolved reports with the checklist recieved from Nagios.
        foreach ($nagReports as $query) {

            foreach ($nagios as $name => $report) {

                foreach ($report as $service) {

                    if ($query->getServiceName() == $name and $query->getCheckType() == $service["type"]){

                        $found = 1;

                    }
                }
            }

            //If a matching report is not found and the newest status is not responding, set as responding.
            if ($found == 0 and $query->getFlag() != 5) {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setIdFlag('5');
                $update->setTimestamp(time());
                $update->save();

            //If a matching report is found and the newest status does not match the information from Nagios,
            //update the status.
            } elseif ($found == 1 and $query->getFlag() != $service["state"]) {

                $update = new ReportStatus();
                $update->setIdReport($query->getIdReport());
                $update->setIdFlag($service["state"]);
                $update->setTimestamp(time());
                $update->save();

            }

            $found = 0;

        }
    }
}
