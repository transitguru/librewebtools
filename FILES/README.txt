The Subdirectories in this folder are not tracked in git. If your application
has its own upload engine, make sure that it will create its directory within 
this directory. To make sure that happens, run `chown www-data` when in this 
directory to ensure your web server user may do its work in this directory.
Also, avoid naming your folder 'core' since that is the name for the directory
for the core uploader.
