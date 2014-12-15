<?php

/**
 * coreInstaller Class
 * 
 * Checks for installation, then installs the site
 * 
 * @category Bootstrap
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreInstaller{
  public $install = false; /**< Set to true if site needs to be installed */
  public $error = 0; /**< set to non-zero if an error occurs */
  public $message = ''; /**< Message to show error if it occurs */
  public $console = ''; /**< Console to write progress in case error is emitted */
  
  /**
   * Constructs the installer object, checking to see if it is installed
   */  
  public function __construct(){
    $this->install = false;
    
    // Check to see if the DB can even connect
    $db = new coreDb();
    if ($db->error){
      $this->install = true;
    }
    
    // Check for existence of admin user password or homepage
    if (!$this->install){
      $db->fetch('passwords', NULL, array('user_id' => 1));
      if ($db->affected_rows == 0){
        $this->install = true;
      }
      $db->fetch('pages', NULL, array('id' => 0));
      if ($db->affected_rows == 0){
        $this->install = true;
      }    
    }
  }
  
  /**
   * Render installation page
   *
   */
  public function view(){
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Install LibreWebTools</title>
    <style>
      body {font-family: sans;}
      label {display: block; font-style: italic;}
    </style>
  </head>
  <body>
    <?php echo $this->message; ?>
    <?php echo $this->console; ?>
    <p>The site appears to not be installed, Please fill out the fields below to begin installing the LibreWebTools. Before you do so, make sure to adjust the site's <strong>/core/settings.inc.php</strong> file to your desired settings.</p>
    <form action="" method="post" >
      <label for="db[root_user]">DB Root User</label><input type="text" name="db[root_user]" />
      <label for="db[root_pass]">DB Root Password</label><input type="password" name="db[root_pass]" />
      <label for="db[admin_user]">Website Admin User</label><input type="text" name="db[admin_user]" />
      <label for="db[admin_pass]">Website Admin Password</label><input type="password" name="db[admin_pass]" />
      <label for="db[confirm_pass]">Confirm Website Admin Password</label><input type="password" name="db[confirm_pass]" />
      <label for="db[admin_email]">Website Admin Email</label><input type="text" name="db[admin_email]" />
      <input type="submit" name="db[submit]" value="Install" />
    </form>
  </body>
</html>
<?php
    exit;  
  }
  
  /**
   * Installs the site
   *
   */
  public function build($post){
    if (isset($post)){
      // Define DB variables
      $settings = new coreSettings();
      $db_name = $settings->db['name'];
      $db_pass = $settings->db['pass'];
      $db_host = $settings->db['host'];
      $db_user = $settings->db['user'];
      $db_port = $settings->db['port'];
      
      // If confirmed password, attempt to install
      if ($post['admin_pass'] == $post['confirm_pass']){
        $conn = mysqli_connect($db_host, $post['root_user'], $post['root_pass'], null, $db_port);
        if (!$conn){
          $this->message = 'error in database settings!';
          $this->error = 1;
        }
        else{
          $this->console = "<pre>\n";
          // Drop the database if it already exists (fresh install)
          $sql = "DROP DATABASE IF EXISTS `{$db_name}`";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $this->error = 1;
            $this->console .= "Broken drop\n";
          }
          
          // Create the LWT database
          $sql = "CREATE DATABASE `{$db_name}` DEFAULT CHARACTER SET utf8";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $this->error = 1;
            $this->console .= "Broken create db\n";
          }
          
          // The following lines must be uncommented if replacing a user
          $sql = "DROP USER '{$db_user}'@'{$db_host}'";
          $conn->real_query($sql);
          
          // Create the database user
          $sql = "CREATE USER '{$db_user}'@'{$db_host}' IDENTIFIED BY '{$db_pass}'";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $this->error = 1;
            $this->console .= "Broken create user\n";
          }
          
          // Grant user to database
          $sql = "GRANT ALL PRIVILEGES ON `{$db_name}`.* TO '{$db_user}'@'{$db_host}'";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $this->error = 1;
            $this->console .= "Broken grant\n";
          }
          
          // Grant user to database
          $sql = "FLUSH PRIVILEGES";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $this->error = 1;
            $this->console .= "Broken flush\n";
          }
          
          // Close the temporary connection
          $conn->close();
          
          if ($this->error){
            // Show that there is an error
            $this->message = 'Error creating database';
          }
          else{
            // Install the databases using the database.inc.php
            $status = $this->install_db($post);
            $this->console .= "\n</pre>";
            if ($status == 0){
              header("Location: /");
              exit;
            }
            else{
              $this->message = "There was an error in the installation process!";
            }
          }
        }
      }
      else{
        $this->error = 1;
        $this->message = "Passwords don't match";
        $this->view();
      }
    }
  }

  /**
   * Installs the Database for the LWT
   *
   * @return int error
   *
   */
  private function install_db($post){
    $file = DOC_ROOT . '/core/sql/schema.sql';
    $sql = file_get_contents($file);
    
    $db = new coreDb();
    $db->multiquery($sql);
    if ($db->error != 0){
      $db->error;
    }
    // Set Date
    $date = date('Y-m-d H:i:s');

    $this->console .= "\nGroups\n";
    
    // Add root group at ID=0
    $inputs = array(
      'created' => $date,
      'name' => 'Everyone',
      'parent_id' => null,
      'desc' => 'Root Level Group, Everyone!'
    );
    $db->write('groups', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write_raw("UPDATE `groups` SET `id` = 0");
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write_raw("ALTER TABLE `groups` AUTO_INCREMENT=1");
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Continuing on the autonumbering the rest of the groups
    $inputs['name'] = 'Unauthenticated';
    $inputs['parent_id'] = 0;
    $inputs['desc'] = 'Users who are not logged in, no user gets assigned this group';
    $db->write('groups', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $inputs['name'] = 'Authenticated';
    $inputs['desc'] = 'Basic Authenticated users';
    $db->write('groups', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $auth_id = $db->insert_id;
    // Subgroups of Authenticated
    $inputs['parent_id'] = $auth_id;
    $inputs['name'] = 'Internal';
    $inputs['desc'] = 'Users within the organization';
    $db->write('groups', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $inputs['name'] = 'External';
    $inputs['desc'] = 'Users outside of the organization';
    $db->write('groups', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    
    
    // Starting with role ID=0
    $this->console .= "\nRoles\n";
    $inputs = array(
      'name' => 'Unauthenticated User',
      'desc' => 'Users that are not logged in',
      'created' => $date,
    );
    $db->write('roles', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write_raw("UPDATE `roles` SET `id` = 0");
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write_raw("ALTER TABLE `roles` AUTO_INCREMENT=1");
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Add the rest of the roles
    $inputs['name'] = 'Administrator';
    $inputs['desc'] = 'Administers website';
    $db->write('roles', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $inputs['name'] = 'Authenticated User';
    $inputs['desc'] = 'Basic user';
    $db->write('roles', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Add the Admin User
    $this->console .= "\nAdmin User\n";
    $user = new coreUser(-1);
    $user->login = $post['admin_user'];
    $user->firstname = 'Site';
    $user->lastname = 'Administrator';
    $user->email = $post['admin_email'];
    $user->roles = array(1 => [1]);
    $user->groups = array(0 => [0]);
    $user->write();
    $this->console .= "{$user->error} \n";
    if ($user->error){
      return $user->error;
    }
    $user->setpassword($post['admin_pass']);
    
    // Add the default theme
    $this->console .= "\nThemes and Modules\n";
    $inputs = array(
      'type' => 'theme',
      'core' => 1,
      'code' => 'core',
      'enabled' => 1,
      'required' => 1,
      'name' => 'Default Core',
    );
    $db->write('modules', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    $inputs['type'] = 'module';
    $inputs['name'] = 'Core';
    $db->write('modules', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Add the pages
    $this->console .= "\nPages\n";
    
    // Add the homepage
    $inputs = array(
      'parent_id' => null,
      'theme_id' => 1,
      'user_id' => 1,
      'url_code' => '/',
      'title' => 'Home',
      'app_root' => 0,
      'core_page' => 1,
      'ajax_call' => '',
      'render_call' => '',
      'created' => $date,
    );
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write_raw("UPDATE `pages` SET `id` = 0 , `url_code` = ''");
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write_raw("ALTER TABLE `pages` AUTO_INCREMENT=1");
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => 0, 'group_id' => 0));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    
    // Add the rest of the pages, starting with login
    $inputs = array(
      'parent_id' => 0,
      'theme_id' => 1,
      'user_id' => 1,
      'url_code' => 'login',
      'title' => 'Login',
      'app_root' => 1,
      'core_page' => 1,
      'ajax_call' => 'core_auth_authentication',
      'render_call' => 'core_auth_login',
      'created' => $date,
    );
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => 0));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // File download page
    $inputs['url_code'] = 'file';
    $inputs['title'] ='File Download';
    $inputs['ajax_call'] = 'core_process_download';
    $inputs['render_call'] = 'core_render_404';
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => 0));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Logout
    $inputs['url_code'] = 'logout';
    $inputs['title'] ='Logout';
    $inputs['ajax_call'] = 'core_auth_logout';
    $inputs['render_call'] = null;
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => 0));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Profile
    $inputs['url_code'] = 'profile';
    $inputs['title'] ='Profile';
    $inputs['ajax_call'] = null;
    $inputs['render_call'] = 'core_auth_profile';
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => $auth_id));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    //Password
    $inputs['url_code'] = 'password';
    $inputs['title'] ='Change Password';
    $inputs['ajax_call'] = NULL;
    $inputs['render_call'] = 'core_auth_password';
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => $auth_id));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }

    //Forgot password
    $inputs['url_code'] = 'forgot';
    $inputs['title'] ='Forgot Password';
    $inputs['ajax_call'] = NULL;
    $inputs['render_call'] = 'core_auth_forgot';
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => 0));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }

    //Admin
    $inputs['url_code'] = 'admin';
    $inputs['title'] ='Administration';
    $inputs['ajax_call'] = 'core_admin_ajax';
    $inputs['render_call'] = 'core_admin_page';
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_roles', array('page_id' => $db->insert_id, 'role_id' => 1));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }

    //Javascript CSS Loader
    $inputs['url_code'] = 'scripts';
    $inputs['title'] ='Not Found';
    $inputs['ajax_call'] = 'core_send_scripts';
    $inputs['render_call'] = 'core_render_404';
    $db->write('pages', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    $db->write('page_groups', array('page_id' => $db->insert_id, 'group_id' => 0));
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    // Add Page content (just home page for now)
    $inputs = array(
      'page_id' => 0,
      'user_id' => 1,
      'created' => $date,
      'title' => 'Home',
      'content' => '<p>Welcome to LibreWebTools</p>',
    );
    $db->write('page_content', $inputs);
    $this->console .= "{$db->error} \n";
    if ($db->error != 0){
      return $db->error;
    }
    
    return 0;
  }
}
