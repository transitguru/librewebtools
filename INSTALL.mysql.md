# MySQL/MariaDB REQUIREMENTS

To use MySQL or MariaDB with your LWT installation, the following
requirements must be met: Server has PHP 7.0 or later with PDO, and the PDO
mysql driver must be enabled. MariaDB 5.5 or greater must be installed or
MySQL 5.5 or greater must be installed. The commands below are identical for
either MySQL or MariaDB.

You will need to log into mysql as root to create your database and
mysql user if it hasn't already been created for you. Log into the mysql prompt
with `sudo mysql` or `mysql -u root -p` depending on how you have root access
configured. Once you see the mysql prompt, enter the following commands.

```mysql
CREATE USER 'username'@'host' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON databasename.* TO 'username'@'host';
FLUSH PRIVILEGES;
```

where:
- `host` is the host where the application is connectiong (usually localhost)
- `databasename` is the name of your database
- `username` is the username of your MySQL account
- `password` is the password required for that username

Note: Unless the database user/host combination for your LWT installation
has all of the privileges listed above you will not be able to install or run 
LWT. This information should also be entered into the appropriate lines
of the `app/settings.php` configuration file.

If successful, MySQL will reply with:

```
Query OK, 0 rows affected
```

If the InnoDB storage engine is available, it will be used for all database
tables. InnoDB provides features over MyISAM such as transaction support,
row-level locks, and consistent non-locking reads.
