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
function core_install($request){
  $install = FALSE;
  
  // Check to see if lwt can log in
  $creds = core_db_creds(DB_NAME);
  $conn = mysqli_connect('localhost', $creds['user'], $creds['pass'], DB_NAME, DB_PORT);
  if (!$conn){
    $install = TRUE;
  }
  
  // Check for existence of admin user password or homepate
  if (!$install){
    $users = core_db_fetch(DB_NAME, 'passwords', NULL, array('user_id' => 1));
    if (count($users) == 0){
      $install = TRUE;
    }
    $content = core_db_fetch(DB_NAME, 'content', NULL, array('id' => 0));
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
            $status = core_install_db();
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
function core_install_db(){
  $file = $_SERVER['DOCUMENT_ROOT'] . '/includes/sql/schema.sql';
  $sql = file_get_contents($file);
  
  $status = core_db_multiquery(DB_NAME, $sql);

  if ($status['error'] != 0){
    return $status['error'];
  }
  echo "<pre>";
  echo "\nRoles\n";
  // Add Groups
  $date = date('Y-m-d H:i:s');
  $inputs = array(
    'created' => $date,
    'name' => 'Unauthenticated',
    'parent_id' => 0,
  );
  $status = core_db_write(DB_NAME, 'groups', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $inputs['name'] = 'Authenticated';
  $status = core_db_write(DB_NAME, 'groups', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $auth_id = $result['insert_id'];
  // Subgroups of Authenticated
  $inputs['parent_id'] = $auth_id;
  $inputs['name'] = 'Internal';
  $status = core_db_write(DB_NAME, 'groups', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $inputs['name'] = 'External';
  $status = core_db_write(DB_NAME, 'groups', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  
  // Add roles
  echo "\nRoles\n";
  $inputs = array(
    'name' => 'Administrator',
    'desc' => 'Administers Website',
    'created' => $date,
  );
  $status = core_db_write(DB_NAME, 'roles', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $inputs['name'] = 'Authenticated User';
  $inputs['desc'] = 'Basic user';
  $status = core_db_write(DB_NAME, 'roles', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  // Add the Admin User
  echo "\Admin User\n";
  $inputs = array(
    'login' => $_POST['db']['admin_user'],
    'firstname' => 'Site',
    'lastname' => 'Administrator',
    'email' => $_POST['db']['admin_email'],
    'desc' =>  'Site Administrator',
    'created' => $date,
  );
  $status = core_db_write(DB_NAME, 'users', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'user_roles', array('role_id' => 1, 'user_id' => 1));
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'user_groups', array('group_id' => 0, 'user_id' => 1));
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_auth_session_setpassword(1, $_POST['db']['admin_pass']);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_auth_setpassword(1, $_POST['db']['admin_pass']);

  // Add the pages
  echo "\nPages\n";
  
  $status = core_db_write(DB_NAME, 'page_groups', array('page_id' => 0, 'group_id' => 0));
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  $inputs = array(
    'parent_id' => 0,
    'url_code' => 'login',
    'title' => 'Login',
    'app_root' => 1,
    'core_page' => 1,
    'ajax_call' => 'core_auth_authentication',
    'render_call' => 'core_auth_login',
    'created' => $date,
  );
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  $inputs['url_code'] = 'file';
  $inputs['title'] ='File Download';
  $inputs['ajax_call'] = 'core_process_download';
  $inputs['render_call'] = 'core_render_404';
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'page_groups', array('page_id' => $status['insert_id'], 'group_id' => 0));
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  $inputs['url_code'] = 'logout';
  $inputs['title'] ='Logout';
  $inputs['ajax_call'] = 'core_auth_logout';
  $inputs['render_call'] = null;
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'page_groups', array('page_id' => $status['insert_id'], 'group_id' => 0));
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  $inputs['url_code'] = 'profile';
  $inputs['title'] ='Profile';
  $inputs['ajax_call'] = null;
  $inputs['render_call'] = 'core_auth_profile';
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'page_groups', array('page_id' => $status['insert_id'], 'group_id' => $auth_id));
  if ($status['error'] != 0){
    return $status['error'];
  }

  $inputs['url_code'] = 'password';
  $inputs['title'] ='Recover Password';
  $inputs['ajax_call'] = NULL;
  $inputs['render_call'] = 'core_auth_forgot';
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'page_groups', array('page_id' => $status['insert_id'], 'group_id' => 0));
  if ($status['error'] != 0){
    return $status['error'];
  }

  $inputs['url_code'] = 'admin';
  $inputs['title'] ='Administration';
  $inputs['ajax_call'] = 'core_ajax_admin';
  $inputs['render_call'] = 'core_render_admin';
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'page_roles', array('page_id' => $status['insert_id'], 'role_id' => 1));
  if ($status['error'] != 0){
    return $status['error'];
  }

  $inputs['url_code'] = 'register';
  $inputs['title'] ='Register';
  $inputs['ajax_call'] = null;
  $inputs['render_call'] = 'core_auth_register';
  $status = core_db_write(DB_NAME, 'pages', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  $status = core_db_write(DB_NAME, 'page_groups', array('page_id' => $status['insert_id'], 'group_id' => 0));
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  // Add Page content (just home page for now)
  $inputs = array(
    'page_id' => 0,
    'created' => $date,
    'title' => 'Home',
    'content' => '<p>Welcome to LibreWeb Tools</p>',
  );
  $status = core_db_write(DB_NAME, 'page_content', $inputs);
  if ($status['error'] != 0){
    return $status['error'];
  }
  
  echo "</pre>";
  return 0;
}

