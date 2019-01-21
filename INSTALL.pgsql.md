# PostgreSQL Requirements

To use PostgreSQL with your LWT installation, the following requirements
must be met: Server has PHP 7.1 or later with PDO, and the PDO pgsql driver
must be enabled. It is recommended to use PostgreSQL v9.0 or later.

You will need to log into psql as postgres to create your database user if it
hasn't already been created for you. Log into the mysql prompt with
`sudo -u postgres psql` assuming 'postgres' is the user that manages psql.
Once you see the psql prompt, enter the following command. 


```
CREATE USER 'username' CREATEDB NOCREATEROLE ENCRYPTED PASSWORD 'password';
```

where:
- `username` is the username of your psql account
- `password` is the password of your psql account

Note: Unless the database user for your LWT installation has all of the
privileges listed above you will not be able to install or run  LWT. This
information should also be entered into the appropriate lines of the
`app/settings.php` configuration file.

If there are no errors, then the commands were successful. If commands are not
found, that means that PostgreSQL was not installed properly.

At this point, this application does not currently support custom schemas within
the database.

