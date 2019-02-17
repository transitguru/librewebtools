# INSTALLING LibreWebTools

Here are a few simple steps for installing LibreWebTools

 0. In the examples, I am assuming your webroot is located at `/var/www/lwt` or
    `/home/librewebtools/public_html`, so make sure to replace this with the
    actual webroot where you install your files. All paths are relative to the
    webroot. You must set up your apache virtual hosts with a basic set up that
    has an AllowOverride All enabled for your directory because this site
    relies on .htaccess files. The following are the minimum requirements for
    the components of your server stack:

    - Apache (version 2.2 or greater) (https://httpd.apache.org/)
    - PHP 7.1.0 (or greater) (https://secure.php.net/) and these PHP packages:
      - php-curl
      - php-gd
      - php-mbstring
      - php-mysql
      - php-pgsql
      - php-sqlite
      - php-xml
    - One of the following databases:
      - MariaDB 5.5 (or greater) (https://mariadb.org/).
      - MySQL 5.5 (or greater) (https://www.mysql.com/). MariaDB is a fully
        compatible drop-in replacement for MySQL. LibreWebTools core utilizes
        features that would be common among both MariaDB and MySQL.
      - PostgreSQL 9.1 (or greater) (https://www.postgresql.org/).
      - SQLite 3.14 (or greater) (https://sqlite.org/).

 1. Copy the app/settings.php.example to app/settings.php. Then edit the
    app/settings.php to your desired database user and password settings. Make
    sure you use a different password than the one given in the default file.

 2. Follow the particular database type's INSTALL file for more information to
    properly set up your database.

 3. Navigate to files/ in your webroot, then invoke `chown www-data ./` to make
    sure your web browser may edit add any directories within this directory.
    The same should be done for the data/ directory. If you are using
    `libapache2-mpm-itk`, which is likely if your webroot is located in a /home/
    directory instead of /var/www/, your user and group will likely be different
    than `www-data` and must be properly set to the user and group that owns the
    webroot as specified in the apache configuration.

 4. Navigate to your website using your web browser. It will do a few checks and
    if it is determined that the database is not installed, a form will appear
    to collect basic information to build your database based on the
    instructions in the app/settings.php and your form data regarding the
    administrative website user. At this moment, it will remove the database
    if it exists then add it back in based on the inputs in `app/settings.php`.

