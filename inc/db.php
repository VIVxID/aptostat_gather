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
                ->useGroupsQuery()
                    ->filterByProposedFlag('2')
                ->endUse()
                ->filterByCheckType($report["type"])
                ->filterByErrorMessage($report["status"])
                ->findOne();

            //If a reported error does not already exist in the database, save it.
            if (is_null($match)) {

                $group = new Groups();
                $group->setProposedFlag('2');
                $group->setTimestamp(time());

                $serv = ServiceQuery::create()->findOneByName($report["hostname"]);

                $entry = new Report();
                $entry->setErrorMessage($report["status"]);
                $entry->setTimestamp($report["lasterrortime"]);
                $entry->setCheckType($report["type"]);
                $entry->setIdSource('2');
                $entry->setIdService($serv->getIdService());
                $entry->addGroups($group);

                $entry->save();

            }
        }
    }

    function nagSave($nagResult)
    {

        foreach ($nagResult as $name => $report) {

            foreach ($report as $service) {

                //Checks the database for matching hidden reports.
                $matchInvis = GroupsQuery::create()
                    ->useReportQuery()
                        ->filterByIdSource('1')
                        ->filterByCheckType($service["type"])
                        ->useServiceQuery()
                            ->filterByName($name)
                        ->endUse()
                    ->endUse()
                    ->filterByProposedFlag('4')
                    ->findOne();

                //Checks the database for matching displayed reports.
                $matchVis = ReportQuery::create()
                    ->useServiceQuery()
                        ->filterByName($name)
                    ->endUse()
                    ->filterByIdSource('1')
                    ->filterByCheckType($service["type"])
                    ->findOne();

                //If a matching report is found as hidden, make it visible.
                if (!is_null($matchInvis)) {

                    $group = GroupsQuery::create()->findPK(array($matchInvis->getIdGroup(),$matchInvis->getIdReport()));
                    $group->setProposedFlag($service["state"]);
                    $group->setTimestamp(time());
                    $group->save();

                //If no matching report is found, create as hidden.
                } elseif (is_null($matchVis)) {

                    $group = new Groups();
                    $group->setProposedFlag('4');
                    $group->setTimestamp(time());

                    $serv = ServiceQuery::create()->findOneByName($name);

                    foreach ($serv as $tmp) {

                        $servId = $tmp->getIdService();

                    }

                    $entry = new Report();
                    $entry->setErrorMessage($service["output"]);
                    $entry->setTimestamp($service["statechange"]);
                    $entry->setCheckType($service["type"]);
                    $entry->setIdSource('1');
                    $entry->setIdService($serv->getIdService());
                    $entry->addGroups($group);

                    $entry->save();

                }
            }
        }
    }

    function flagResolvedPingdom($pingdom)
    {

        $found = 0;

        //Fetch all relevant information about existing unresolved reports.
        $pingReports = ReportQuery::create()
            ->useGroupsQuery()
                ->filterByProposedFlag(array(1,2,4))
            ->endUse()
            ->join('Report.Groups')
            ->withColumn('Groups.IdGroup', 'IdGroup')
            ->withColumn('Groups.ProposedFlag', 'Flag')
            ->join('Report.Service')
            ->withColumn('Service.Name', 'ServiceName')
            ->filterByIdSource('2')
            ->find();

        //Compare unresolved reports with the checklist recieved from Pingdom. If Pingdom
        //is no longer reporting an error on the system, flag it as resolved.
        foreach ($pingReports as $query) {

            foreach ($pingdom as $report) {

                if ($query->getServiceName() == $report["hostname"] and $query->getCheckType() == $report["type"]) {

                    $found = 1;

                }
            }

            if ($found == 0) {

                $removal = GroupsQuery::create()->findPK(array($query->getIdGroup(), $query->getIdReport()));
                $removal->setProposedFlag('5');
                $removal->setTimestamp(time());
                $removal->save();

            }

            $found = 0;

        }
    }

    function flagResolvedNagios($nagios)
    {

        $found = 0;

        //Fetch all relevant information about existing unresolved reports.
        $nagReports = ReportQuery::create()
            ->useGroupsQuery()
                ->filterByProposedFlag(array(1,2))
            ->endUse()
            ->join('Report.Groups')
            ->withColumn('Groups.IdGroup', 'IdGroup')
            ->withColumn('Groups.ProposedFlag', 'Flag')
            ->join('Report.Service')
            ->withColumn('Service.Name', 'ServiceName')
            ->filterByIdSource('1')
            ->find();


        //Compare unresolved reports with the checklist recieved from Nagios. If Nagios
        //is no longer reporting an error on the system, flag it as resolved.
        foreach ($nagReports as $query) {

            foreach ($nagios as $name => $report) {

                foreach ($report as $service) {

                    if ($query->getServiceName() == $name and $query->getFlag() == $service["state"] and $query->getCheckType() == $service["type"]){

                        $found = 1;

                    }
                }
            }

            if ($found == 0) {

                $removal = GroupsQuery::create()->findPK(array($query->getIdGroup(), $query->getIdReport()));
                $removal->setProposedFlag('5');
                $removal->setTimestamp(time());
                $removal->save();

            }

            $found = 0;

        }
    }

    function groupReports()
    {

        $list = array();

        //Fetch a list of all unresolved reports.
        $reports = ReportQuery::create()
            ->useGroupsQuery()
                ->filterByProposedFlag(array(1,2,4))
            ->endUse()
            ->join('Report.Groups')
            ->withColumn('Groups.IdGroup', 'IdGroup')
            ->find();

        //Build an array with the fetched reports grouped by hostname.
        foreach ($reports as $report) {

            $list[$report->getIdService()][$report->getIdGroup()] = $report->getIdReport();

        }

        //If a given host has more than two unresolved reports, they are given the same group ID.
        foreach ($list as $group) {

            if (count($group) >= 2) {

                $groupMaster = key($group);

                foreach ($group as $key => $value) {

                    if ($key != $groupMaster) {

                        //Propel does not like to update primary keys. Using custom SQL as a workaround.
                        $prop = Propel::getConnection("Aptostat");

                        $sql = "update Groups set IdGroup=".$groupMaster." where IdReport = ".$value.";";
                        $stmt = $prop->prepare($sql);
                        $stmt->execute();

                    }
                }
            }
        }
    }
}
