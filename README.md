#Aptostat_gather
This is the back-end part which will fetch status messages from Aptoma's Pingdom and Nagios, process them and store them in a database. It also caches information for the live status panel and the 7-day history table.

## Environment (Requirements)
- PHP
- MySQL
- Memcached
- PHP-Memcached
- Propel

Aptostat_gather relies on the database schema which is used in Aptostat_api, and uses the same Propel object models.
Follow the instructions in Apostat_api for setting up propel. Then update collectReports with the proper path.

Install memcached and set it up as a service.

Run collectReports.php, fetchLive.php and fetchUptime.php as crontabs. We recommend 1-minute intervals for
collectReports and fetchLive, and an hour for fetchUptime.
