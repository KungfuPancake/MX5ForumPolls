# MX5 forum poll chart maker
## Installation
1. Install dependencies with "composer install".
2. You'll need php-gd, php-mysql, php-xml.
3. Copy config/config.yml.dist to config/config.yml and insert username/password values. Retrieve the correct forum ID and change that value too.
4. Create a database. Try "create database mx5 default charset utf8;"
5. Insert schema into database. E.g. "mysql -u mx5 -p mx5 < mx5.sql"

Ensure proper operation by calling "php parsevotes.php" and add a cron job if everything works as expected.
