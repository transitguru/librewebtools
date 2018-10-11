# INSTALLING LibreWebTools

Here are a few simple steps for installing LibreWebTools

 0. In the examples, I am assuming your webroot is located at
    `/home/librewebtools/public_html/`, so make sure to replace this with the
    actual webroot where you install your files. All paths are relative to the
    webroot. I am also assuming that you set up your apache virtual hosts is a
    basic setup with AllowOverride All enabled for your directory as this site
    relies on .htaccess files.
    
 1. Copy the app/settings.php.example to app/settings.php. Then edit the 
    app/settings.php to your desired database user and password settings. Make 
    sure you use a different password than the one given in the default file.
 
 2. Navigate to files/ in your webroot, then invoke `chown www-data ./` to make
    sure your web browser may edit add any directories within this directory. 
    If you are using `libapache2-mpm-itk`, your user and group will likely be
    different than `www-data` and must be properly set to the user and group 
    that ownes the webroot in the apache configuration.
    
 3. Navigate to your website using your web browser. It will do a few checks
    and it is determined that the database is not installed, a form will appear
    to collect basic information to build your database based on the 
    instructions in the app/settings.php and your form data regarding the 
    administrative website user. At this moment, it will remove the database
    if it exists then add it back in based on the inputs in `app/settings.php`.
