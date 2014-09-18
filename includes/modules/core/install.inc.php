<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Checks for installation in the databases, and installs them
 * if user accepts
 * 
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
  $creds = lwt_db_creds(DB_NAME);
  $conn = mysqli_connect('localhost', $creds['user'], $creds['pass'], DB_NAME, DB_PORT);
  if (!$conn){
    $install = TRUE;
  }
  
  // Check for existence of admin user password or homepate
  if (!$install){
    $users = lwt_db_fetch(DB_NAME, 'passwords', NULL, array('user_id' => 1));
    if (count($users) == 0){
      $install = TRUE;
    }
    $content = lwt_db_fetch(DB_NAME, 'content', NULL, array('id' => 0));
    if (count($content) == 0){
      $install = TRUE;
    }
  }
  
  if ($install && $request != '/install/'){
    header('Location: /install/');
    exit;
  }
  elseif ($install){
    if (isset($_POST['db'])){
      $db_name = DB_NAME;
      $db_pass = DB_PASS;
      $db_host = DB_HOST;
      $db_user = DB_USER;
      
      if ($_POST['db']['admin_pass'] == $_POST['db']['confirm_pass']){
        $conn = mysqli_connect(DB_HOST, $_POST['db']['root_user'], $_POST['db']['root_pass'], null, DB_PORT);
        if (!$conn){
          echo 'error in database settings!';
        }
        else{
          $error = false;
          
          // Drop the database if it already exists (fresh install)
          $sql = "DROP DATABASE IF EXISTS `{$db_name}`";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken drop";
          }
          
          // Create the LWT database
          $sql = "CREATE DATABASE `{$db_name}` DEFAULT CHARACTER SET utf8";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken create db";
          }
          
          // The following lines must be uncommented if replacing a user
          $sql = "DROP USER '{$db_user}'@'{$db_host}'";
          $conn->real_query($sql);
          
          // Create the database user
          $sql = "CREATE USER '{$db_user}'@'{$db_host}' IDENTIFIED BY '{$db_pass}'";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken create user";
          }
          
          // Grant user to database
          $sql = "GRANT ALL PRIVILEGES ON `{$db_name}`.* TO '{$db_user}'@'{$db_host}'";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken grant";
          }
          
          // Grant user to database
          $sql = "FLUSH PRIVILEGES";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken flush";
          }
          
          
          // Close the temporary connection
          $conn->close();
          
          if ($error){
            // Show that there is an error
            echo 'Error creating database';
          }
          else{
            // Install the databases using the database.inc.php
            $status = lwt_install_db();
            if ($status == 0){
              header("Location: /");
            }
            else{
              echo "There was an error in the installation process!";
            }
          }
        }
      }
      else{
        echo "passwords don't match";
      }
    }
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Install LibreWebTools</title>
  </head>
  <body>
    <p>The site appears to not be installed, Please fill out the fields below to begin installing the LibreWebTools. Before you do so, make sure to adjust the site's <strong>/includes/modules/core/settings.inc.php</strong> file to your desired settings.</p>
    <form action="" method="post" >
      <table>
        <tr><td><label for="db[root_user]">DB Root User</label></td><td><input type="text" name="db[root_user]" /></td></tr>
        <tr><td><label for="db[root_pass]">DB Root Password</label></td><td><input type="password" name="db[root_pass]" /></td></tr>
        <tr><td><label for="db[admin_user]">Website Admin User</label></td><td><input type="text" name="db[admin_user]" /></td></tr>
        <tr><td><label for="db[admin_pass]">Website Admin Password</label></td><td><input type="password" name="db[admin_pass]" /></td></tr>
        <tr><td><label for="db[confirm_pass]">Confirm Website Admin Password</label></td><td><input type="password" name="db[confirm_pass]" /></td></tr>
        <tr><td><label for="db[admin_email]">Website Admin Email</label></td><td><input type="text" name="db[admin_email]" /></td></tr>
      </table>
      <input type="submit" name="db[submit]" value="Install" />
    </form>
  </body>
</html>
<?php
    exit;
    
  }
  return $request;
}

/**
 * Installs the Database for the LWT
 *
 * @return int error
 *
 */
function lwt_install_db(){
  $file = $_SERVER['DOCUMENT_ROOT'] . '/includes/sql/schema.sql';
  $sql = file_get_contents($file);
  
  $status = lwt_db_multiquery(DB_NAME, $sql);

  if ($status['error'] != 0){
    return $status['error'];
  }
  echo "<pre>";
  //Create the group that is "root" (typically no users get assigned this group except the admin)
  $status = lwt_db_write_raw(DB_NAME, "INSERT INTO `groups` (`name`) VALUES ('Everyone')");
  echo $status['error'] . "\n";
  $status = lwt_db_write_raw(DB_NAME, "UPDATE `groups` SET `id`=0");
  echo $status['error'] . "\n";
  $status = lwt_db_write_raw(DB_NAME, "ALTER TABLE `groups` AUTO_INCREMENT=1");
  echo $status['error'] . "\n";
  
  //Add groups starting back at ID 1
  $sql = "INSERT INTO `groups` (`name`) VALUES ('Unauthenticated'), ('Authenticated'), ('Internal'), ('External')";
  $status = lwt_db_write_raw(DB_NAME, $sql);  
  echo $status['error'] . "\n";
  
  // Set group hierarchy
  $sql = "INSERT INTO `group_hierarchy` (`parent_id`,`group_id`) VALUES 
  (0,(SELECT `id` FROM `groups` WHERE `name`='Everyone')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Unauthenticated')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Authenticated')), 
  ((SELECT `id` FROM `groups` WHERE `name`='Authenticated'), (SELECT `id` FROM `groups` WHERE `name`='Internal')),
  ((SELECT `id` FROM `groups` WHERE `name`='Authenticated'), (SELECT `id` FROM `groups` WHERE `name`='External'))";
  $status = lwt_db_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Create the "unauthenticated" role (noone is associated to this role!)
  $status = lwt_db_write_raw(DB_NAME, "INSERT INTO `roles` (`name`, `desc`) VALUES ('Unauthenticated User', 'Non-logged in user')");
  echo $status['error'] . "\n";
  $status = lwt_db_write_raw(DB_NAME, "UPDATE `roles` SET `id`=0");
  echo $status['error'] . "\n";
  $status = lwt_db_write_raw(DB_NAME, "ALTER TABLE `roles` AUTO_INCREMENT=1");
  echo $status['error'] . "\n";
  
  // Create the Administrator role (always set it to an ID of one) and the Authenticated User
  $sql = "INSERT INTO `roles` (`name`, `desc`) VALUES 
  ('Administrator','Administers website'),
  ('Authenticated User', 'Basic user')";
  $status = lwt_db_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Add the Admin User
  $inputs = array(
    'login' => $_POST['db']['admin_user'],
    'firstname' => 'Site',
    'lastname' => 'Administrator',
    'email' => $_POST['db']['admin_email'],
    'desc' =>  'Site Administrator',
  );
  $status = lwt_db_write(DB_NAME, 'users', $inputs);
  echo $status['error'] . "\n";
  $status = lwt_db_write(DB_NAME, 'user_roles', array('role_id' => 1, 'user_id' => 1));
  echo $status['error'] . "\n";
  $status = lwt_db_write(DB_NAME, 'user_groups', array('group_id' => 0, 'user_id' => 1));
  echo $status['error'] . "\n";
  $status = lwt_auth_setpassword(1, $_POST['db']['admin_pass']);
  echo $status['error'] . "\n";

  // Add root homepage at id=0
  $status = lwt_db_write_raw(DB_NAME, "INSERT INTO `content` (`title`,`content`) VALUES ('Home','<p>LibreWebTools is a lightweight content management and web-application development framework. It is currently under development and you may find some breakage. Feel free to go to the <a href=\"https://github.com/transitguru/librewebtools\">GitHub</a> for the source code and instructions on how to set this up.</p>')");
  echo $status['error'] . "\n";
  $status = lwt_db_write_raw(DB_NAME, "UPDATE `content` SET `id`=0");
  echo $status['error'] . "\n";
  $status = lwt_db_write_raw(DB_NAME, "ALTER TABLE `content` AUTO_INCREMENT=1");
  echo $status['error'] . "\n";
  
  // Add required content for site to run
  $sql = "INSERT INTO `content` (`title`,`preprocess_call`,`function_call`,`content`) VALUES
  ('Login','lwt_auth_authentication', 'lwt_render_login',NULL),
  ('File Download','lwt_process_download', 'lwt_render_404',NULL),
  ('Logout','lwt_auth_logout', NULL, NULL),
  ('Profile',NULL, 'lwt_auth_profile', NULL),
  ('Reset Password',NULL, 'lwt_auth_password', NULL),
  ('Forgot Password',NULL, 'lwt_auth_forgot', NULL),
  ('Manage Users','lwt_ajax_admin_users', 'lwt_render_admin_users', NULL),
  ('Manage Content','lwt_ajax_admin_content', 'lwt_render_admin_content', NULL),
  ('Register',NULL, NULL, '<p>User self-registration is currently not enabled</p>'),
  ('Test Page',NULL,NULL,'<p>This is a Test Page<br />Making sure it shows up</p>')";
  $status = lwt_db_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Place pages into correct hierarcy
  $sql = "INSERT INTO `content_hierarchy` (`parent_id`,`content_id`,`url_code`, `app_root`) VALUES
  (0,(SELECT `id` FROM `content` WHERE `title`='Home'),'',0),
  (0,(SELECT `id` FROM `content` WHERE `title`='File Download'),'files',1),
  (0, (SELECT `id` FROM `content` WHERE `title`='Login'), 'login',0),
  (0, (SELECT `id` FROM `content` WHERE `title`='Logout'), 'logout',0),
  (0, (SELECT `id` FROM `content` WHERE `title`='Test Page'), 'test',0),
  (0, (SELECT `id` FROM `content` WHERE `title`='Profile'), 'profile',0),
  (0, (SELECT `id` FROM `content` WHERE `title`='Reset Password'), 'password',0),
  (0, (SELECT `id` FROM `content` WHERE `title`='Manage Users'), 'users',1),
  (0, (SELECT `id` FROM `content` WHERE `title`='Manage Content'), 'content',1),
  (0, (SELECT `id` FROM `content` WHERE `title`='Register'), 'register',1),
  (0, (SELECT `id` FROM `content` WHERE `title`='Forgot Password'), 'forgot',1)";
  $status = lwt_db_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Apply permissions
  $sql = "INSERT INTO `group_access` (`content_id`,`group_id`) VALUES
  ((SELECT `id` FROM `content` WHERE `title`='Home'),0),
  ((SELECT `id` FROM `content` WHERE `title`='File Download'),0),
  ((SELECT `id` FROM `content` WHERE `title`='Login'), 0),
  ((SELECT `id` FROM `content` WHERE `title`='Logout'), 0),
  ((SELECT `id` FROM `content` WHERE `title`='Forgot Password'), 0),
  ((SELECT `id` FROM `content` WHERE `title`='Register'), 0),
  ((SELECT `id` FROM `content` WHERE `title`='Test Page'), (SELECT `id` FROM `groups` WHERE `name`='Internal')),
  ((SELECT `id` FROM `content` WHERE `title`='Profile'), (SELECT `id` FROM `groups` WHERE `name`='Internal')),
  ((SELECT `id` FROM `content` WHERE `title`='Reset Password'), (SELECT `id` FROM `groups` WHERE `name`='Internal'))";
  $status = lwt_db_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Limit admin to certain areas
  $sql = "INSERT INTO `role_access` (`content_id`, `role_id`) VALUES
  ((SELECT `id` FROM `content` WHERE `title`='Manage Users'),(SELECT `id` FROM `roles` WHERE `name`='Administrator')),
  ((SELECT `id` FROM `content` WHERE `title`='Manage Content'),(SELECT `id` FROM `roles` WHERE `name`='Administrator'))";
  $status = lwt_db_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  echo "</pre>";
  return 0;
}

