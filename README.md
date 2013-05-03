#Aptostat_gather
This is the back-end part which will fetch status messages from Aptoma's Pingdom and Nagios, process them and store them in a database. It also caches information for the live status panel and the 7-day history table.

## Environment (Requirements)
- PHP
- MySQL
- Memcached
- Curl
- PHP-Memcached
- PHP-Curl

Aptostat_gather relies on the database schema which is used in Aptostat_api, and uses the same Propel object models.
Follow the instructions in Apostat_api for setting up propel.
Then update `collectReports.php` with the proper path.

Aptostat_gather relies on memcached to store information that is not real time critical.

If you haven't installed memcached yet, please do so.
Install memcached and its php-extension. Gather is set to use memcached's default config - localhost on port 11211.

### Setting up gather

#### Clone the files

    $ git clone https://github.com/nox27/aptostat_gather.git

#### Make the credentials file
Create the file `ping` in the path `/var/apto/ping`:

    $ sudo mkdir -p /var/apto
    $ sudo touch /var/apto/ping

Use any editor and enter your Pingdom credentials in the following format:

```
username
password
app-key
```

Note: Ensure that the server where gather is deployed has permission to access your Nagios data

#### Change config.php
Update `config.php` with the right path to API root-folder and the Pingdom credentials.

#### Populate Service table
Run `builder.php` with the command:

    php builder.php

#### Set up crontabs
Enter the editor with

    $ crontab -e

Add the following: (Replace `/path/to` with an actual path)

```
    * * * * * cd /path/to/aptostat_gather && php collectReports.php
    * * * * * cd /path/to/aptostat_gather && php fetchLive.php
    0 * * * * cd /path/to/aptostat_gather && php fetchUptime.php
```

Running `builder.php` as a crontab is optional. It all depends on how often you plan to add new hosts.

Run `collectReports.php`, `fetchLive.php` and `fetchUptime.php` as crontabs. We recommend 1-minute intervals for
`collectReports.php` and `fetchLive.php` and 30-60 minutes for `fetchUptime.php`.

## Function

### collectReports.php

collectReports.php handles gathering, saving and updating of error reports from Pingdom and Nagios.

#### Gathering reports

Gather fetches state objects from Pingdom and Nagios containing current status information.

For both systems, gather collects the hostname, the type of check, when the error occured and an error message. Nagios also
returns a status code, where 0 is OK, 1 is Warning and 2 is Critical. We use this error code to set the status of Nagios reports directly.
Pingdom only has an error message, which can be either up, down, unknown or unconfirmed_down. On reports, down is
interpreted as Critical, while unknown and unconfirmed_down are interpreted as warnings. All reports where the status
is 0 (for Nagios) or up (for Pingdom) are discarded at this point.

Pingdom and Nagios checks are unique on the combination hostname + type of check. This is used throughout the system to filter and update errors.

#### Saving reports

Before reports are saved, gather checks the database to see if this report already exists in the database.

- Reports are saved if the following requirements are met:
    - No report with this unique combination of hostname + type of check exists in the database.
    - OR
    - Reports with this unique combination of hostname + type of check exists, but the newest report of its kind has been resolved.

Resolved reports are considered closed and a new report will be created if the same problem occurs on the same host.


#### Updating reports

The update functions collect all information about saved reports from the database and compares them to the collected information
from Pingdom and Nagios. Based on this comparison, several actions can be performed.

- Actions
    - If a report marked as Critical or Warning in the database does not exist in the collection from Nagios or Pingdom, it is marked as Responding.
    - If a report marked as Responding in the database exists in the collection from Nagios or Pingdom, it is updated with the appropriate error status.
    - If a report marked as Responding in the database does not exist in the collection from Nagios or Pingdom, and it has been Responding for over a day, it is marked as Resolved.

Resolved reports are considered closed.

### fetchLive.php

fetchLive.php collects live uptime status from Pingdom. It uses the same API-function as gather to collect its information, 
and caches it in Memcached with a cache duration of 10 minutes.

The cached information can be retrieved with the /live function in the API.

### fetchUptime.php

fetchUptime.php collects uptime history for a predetermined timeframe, set by default to 30 days. It uses the summary.outage
function in the Pingdom API to collect information.

fetchUptime.php collects downtime in seconds for each host, and stores it in Memcached with a cache duration of half a day.

The cached information can be retrieved with the /uptime function in the API.

