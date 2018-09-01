# SQLite Requirements

To use SQLite with your LWT installation, the following requirements must 
be met: Server has PHP 7.0 or later with PDO, and the PDO SQLite driver must be
enabled. SQLite version 3.0 or later should be used.

The 'name' key for the database `$db` array in the `app/settings.php` should
contain the full path to the database for this installation to work. All other
fields are not required or recommended to be filled out and can be `null`.

The LWT installer will create the SQLite database for you. The only
requirement is that the installer must have write permissions to the directory
where the database file resides. This directory (not just the database file) 
also has to remain writeable by the web server going forward for SQLite to 
continue to be able to operate.

It is recommended that the sqlite database is in a directory that is not seen
by apache or at least has an `.htaccess` file that prevents serving the file or
directory to the public. The `data/` directory in this project is a good
candidate as it is both ignored by git and Apache denies access.

