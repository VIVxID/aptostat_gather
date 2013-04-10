#Aptostat_gather
This is the back-end part which will fetch status messages from Aptoma's Pingdom and Nagios, process them and store them in a database. It also caches information for the live status panel and the 7-day history table.

## Environment (Requirements)
- PHP
- MySQL
- Memcached

Aptostat_gather relies on the database schema which is used in Aptostat_api
Follow the instructions in Apostat_api
