# PostgreSQL Requirements

To use PostgreSQL with your LWT installation, the following requirements
must be met: Server has PHP 7.0 or later with PDO, and the PDO pgsql driver 
must be enabled. It is recommended to use PostgreSQL v9.0 or later.

Note that the database must be created with UTF-8 (Unicode) encoding. The
following commands must be made within the shell command line and not within
`psql`. Make sure to take note of the password you enter when creating the user
and put all relevant information in `app/settings.php`.

```
sudo -u postgres createuser --pwprompt --encrypted --no-createrole --no-createdb username
sudo -u postgres createdb --encoding=UTF8 --owner=username databasename
```

where:
- `postgres` is the default user that postgres uses for managing databases
- `databasename` is the name of your database
- `username` is the username of your MySQL account

If there are no errors, then the commands were successful. If commands are not
found, that means that PostgreSQL was not installed properly.

At this point, this application does not currently support custom schemas within
the database.
