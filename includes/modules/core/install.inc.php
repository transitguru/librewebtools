<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Functions to check for installation in the databases, and installs them
 * if user accepts
 * 
 * @todo create these functions and databases
 * 
 */


/**
 * Checks to see if the database and settings are defined
 * 
 * @return Request URI if no install needed
 */
 
function lwt_install($request){
  $install = FALSE;
  
  // Check to see if lwt can log in
  $creds = lwt_database_get_credentials(DB_NAME);
  $conn = mysqli_connect('localhost', $creds['user'], $creds['pass'], DB_NAME);
  if (!$conn){
    $install = TRUE;
  }

  
  // Check for existence of admin user password  
  if (!$install){
    $users = lwt_database_fetch_simple('librewebtools', 'passwords', NULL, array('user_id' => 1));
    if (count($users) == 0){
      $install = TRUE;
    }
  }
  if ($install && $request != '/install/'){
    header('Location: /install/');
    exit;
  }
  elseif ($install){
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Install LibreWebTools</title>
  </head>
  <body>
    <p>Please go to INSTALL.txt in your webroot to install your site. The contents of this file appears below.</p>
    <pre>
<?php 
  $dump = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/INSTALL.txt');
  echo $dump;
?>
    </pre>
  </body>
</html>
<?php
    exit;
    
  }
  return $request;
}
