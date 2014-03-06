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



function lwt_install($request){
  $install = FALSE;
  // Check if settings file exists
  $success = file_exists($_SERVER['DOCUMENT_ROOT'] . '/includes/modules/core/settings.inc.php');
  if (!$success){
    $install = TRUE;
  }
  
  // Check to see if lwt can log in
  $creds = lwt_database_get_credentials('librewebtools');
  $conn = mysqli_connect('localhost', $creds['user'], $creds['pass'], 'librewebtools');
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
  elseif($install){
    if (isset($_POST['install']) && $_POST['install'] == 'Install'){
      // Do installation Stuff
      echo "Should be doing install stuff<pre>";
      var_dump($_POST);
      echo "</pre>";
      // Create database information
      $conn = new mysqli('localhost', $_POST['root_db_user'], $_POST['root_db_pass']);
      $sql = "CREATE SCHEMA `{$_POST['lwt_db_name']}` DEFAULT CHARACTER SET utf8";
      $conn->real_query($sql);
      $sql = "CREATE USER '{$_POST['lwt_db_user']}'@'localhost' IDENTIFIED BY '{$_POST['lwt_db_pass']}'";
      $conn->real_query($sql);
      $sql = "GRANT ALL PRIVILEGES ON `{$_POST['lwt_db_name']}`.*  TO'{$_POST['lwt_db_user']}'@'localhost'";
      $conn->real_query($sql);
      $sql = "FLUSH PRIVILEGES";
      $conn->real_query($sql);
      
      // Import database
      exec("cd '" . $_SERVER['DOCUMENT_ROOT'] . "/includes/sql'");
      exec("mysql -u {$_POST['root_db_user']} -p'{$_POST['root_db_pass']}' {$_POST['lwt_db_name']} < core.sql");
      
      exit;
    }
    else{
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Install LibreWebTools</title>
  </head>
  <body>
    <p>Please enter details below on this one screen install enable the databases so that you can use LibreWebTools</p>
    <form action="" method="post">
      <label for="root_db_user">Root Database User (should already exist)</label><input type="text" name="root_db_user" /><br />
      <label for="root_db_pass">Root Database Password (should already exist)</label><input type="password" name="root_db_pass" /><br />
      <label for="lwt_db_name">Create LibreWebTools Database</label><input type="text" name="lwt_db_name" /><br />
      <label for="lwt_db_user">Create LibreWebTools Database User</label><input type="text" name="lwt_db_user" /><br />
      <label for="lwt_db_pass">Create LibreWebTools Database Password</label><input type="text" name="lwt_db_pass" /><br />
      <label for="lwt_admin_user">Create Admin Web User</label><input type="text" name="lwt_admin_user" /><br />
      <label for="lwt_admin_pass">Create Admin Web Password</label><input type="text" name="lwt_admin_pass" /><br />
      <input type="submit" name="install" value="Install" />
    </form>
  </body>
</html>
<?php
      exit;
    }
    
  }
  return $request;
}
