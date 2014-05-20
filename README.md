IMPReSS
=======

IMPReSS is a web application and portal of Standard Operating Procedures used in 
high-throughput phenotyping of knockout mice. It has an administration interface 
that allows you to define Procedures and list what Parameters are captured during 
experiments. IMPReSS is running live at <http://www.mousephenotype.org/impress>.

This software is open source and licensed under the Apache 2.0 license.

System Requirements
-------------------

* Server software: PHP 5.3.5+
    + php.ini:
        - max_execution_time = 300
        - memory_limit = 512M
        - allow_url_fopen = On
        - file_uploads = On
        - post_max_size = 8M
        - upload_max_filesize = 8M
        - soap.wsdl_cache_enabled = Off (Recommended)
    + Extensions:
        - Curl
        - Gd2
        - Mbstring
        - Mysql
        - Mysqli
        - Pdo_mysql
        - Soap
        - Xmlrpc
        - Xsl
        - Openssl
        - Zip
* Database: Tested on MySQL 5.5 but probably works with 5.0.15+
* Web server: Apache 2.2+ with url_rewrite enabled (should work with other servers if properly configured)
* Disk space: ~500MB for IMPReSS and dependencies


Installation
------------

* Install PHP
    + Ensure [php](http://www.php.net/manual/en/install.php) is available from the command line:
        - `php -v`
---

* Install Git Client executable
    + Ensure [git](http://git-scm.com/book/en/Getting-Started-Installing-Git) is available on the command line:
        - `git --version`
---

* Install Drupal
    1. Download and install [Drupal 7](https://drupal.org/download)
    2. Log in as the administrator
    3. Click People > Permissions > Roles
    4. Add the role "IMPReSS Admin" - This role is for regular editing of IMPReSS so you can give a "data wrangler" this role. Super users have the administrator role.
---

* Install IMPReSS
    1. Download IMPReSS to a web-accessible directory (e.g. /var/usr/html)
    2. There are a bunch of configuration files you need to edit with the most important settings pointed out:
        * ./application/config/config.php:
            + $config['base_url']
            + $config['mousephenotypeurl']
            + $config['mousephenotypedb']
        * ./application/config/config_impress.php - also set your [local timezone](https://php.net/manual/en/timezones.php) here
            + $config['server']
        * ./application/config/database.php
            + $db['default']['hostname']
            + $db['default']['username']
            + $db['default']['password']
            + $db['default']['database']
            + $db['default']['dbcollat']
---

* Create a database in MySQL (e.g. impress) and run impress.sql into it. Run these SQL commands to prep the database:
    + `TRUNCATE TABLE logs;`
    + `TRUNCATE TABLE change_logs;`
    + `TRUNCATE TABLE not_in_beta;`
---

* Install and update project dependencies using Composer. On the command line:
    + `cd /var/usr/html`    (or wherever you installed IMPReSS)
    + `php composer.phar self-update`
    + `php composer.phar update`
