<?php
namespace LWT;
/**
 * @file
 * Installer Class
 * 
 * Checks for installation, then installs the site
 * 
 * @category Bootstrap
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Installer{
  public $install = false; /**< Set to true if site needs to be installed */
  public $error = 0; /**< set to non-zero if an error occurs */
  public $message = ''; /**< Message to show error if it occurs */
  public $console = ''; /**< Console to write progress in case error is emitted */
  
  /**
   * Constructs the installer object, checking to see if it is installed
   */  
  public function __construct($uri, $post){
    $this->install = false;
    
    // Check to see if the DB can even connect
    $db = new Db();
    if ($db->error){
      $this->install = true;
    }
    
    // Check for existence of admin user password or homepage
    if (!$this->install){
      $db->fetch('passwords', NULL, array('user_id' => 1));
      if ($db->affected_rows == 0){
        $this->install = true;
      }
      $db->fetch('paths', NULL, array('id' => 0));
      if ($db->affected_rows == 0){
        $this->install = true;
      }    
    }
    if ($this->install == true && $uri !== '/install'){
      header('Location: ' . BASE_URI . '/install');
      exit;
    }
    elseif ($this->install == true && $uri === '/install'){
      if (isset($post->db)){
        $this->build($post);
      }
      $this->view();
    }
  }
  
  /**
   * Render installation page
   *
   */
  private function view(){
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
    <p>The site appears to not be installed, Please fill out the fields below to begin installing the LibreWebTools. Before you do so, make sure to adjust the <strong>/app/settings.php</strong> file to your desired settings.</p>
    <form action="" method="post" >
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
  private function build($post){
    if (isset($post)){
      // Define DB variables
      $settings = new Settings();
      $db_name = $settings->db['name'];
      $db_pass = $settings->db['pass'];
      $db_host = $settings->db['host'];
      $db_user = $settings->db['user'];
      $db_port = $settings->db['port'];
      
      // If confirmed password, attempt to install by creating empty db connection
      if ($post->admin_pass == $post->confirm_pass){
        $db = new Db(null, $db_pass, $db_host, $db_user, $db_port);
        if (!$db){
          $this->message = 'error in database settings!';
          $this->error = 1;
        }
        else{
          $this->console = "<pre>\n";

          // Drop the database if it already exists (fresh install)
          if ($db->db['type'] == 'mysql' || $db->db['type'] == 'pgsql'){
            $sql = 'DROP DATABASE IF EXISTS "' . $db_name . '"';
            $db->write_raw($sql);
            if ($db->error > 0){
              $this->error = 1;
              $this->console .= "Broken drop\n";
            }
          }
          elseif($db->db['type'] == 'sqlite'){
            if(is_file(DOC_ROOT . $db->db['name'])){
              unlink(DOC_ROOT . $db->db['name']);
            }
          }
          else{
            $this->error = 1;
            $this->console .= "Broken drop\n";
          }
          
          // Create the LWT database
          if ($db->db['type'] == 'mysql' || $db->db['type'] == 'pgsql'){
            $sql = 'CREATE DATABASE "' . $db_name . '" DEFAULT CHARACTER SET utf8';
            $db->write_raw($sql);
            if ($db->error > 0){
              $this->error = 1;
              $this->console .= "Broken create db\n";
            }
          }
          elseif($db->db['type'] == 'sqlite'){
            if(!is_file(DOC_ROOT . $db->db['name'])){
              $bytes = file_put_contents(DOC_ROOT . $db->db['name'], '');
            }
            if ($bytes === 'false'){
              $this->error = 1;
              $this->console .= "Broken create db\n";
            }
          }
          else{
            $this->error = 1;
            $this->console .= "Broken create\n";
          }
          
          // Unset the empty db connection
          unset($db);
          
          if ($this->error){
            // Show that there is an error
            $this->message = 'Error creating database';
          }
          else{
            // Install the databases using the install_db method
            $status = $this->install_db($post);
            $this->console .= "\n</pre>";
            if ($status == 0){
              header("Location: /");
              exit;
            }
            else{
              $this->message = "There was an error in the installation process!";
              $this->error = $status;
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
    //Load installer file
    $file = DOC_ROOT . '/app/json/installer.json';
    $json = file_get_contents($file);

    //Keep track of raw SQL for building the tables
    $sql = (object) ['mysql' => '', 'pgsql' => '', 'sqlite' =>''];

    //Build the tables from the installer file
    $table_builder = new LWT\Table();
    $object = json_decode($json);
    if (isset($object->tables) && is_array($object->tables)){
      foreach($object->tables as $table){
        $table_builder->create_sql($table);
        $sql->mysql .= "\n\n" . $table_builder->mysql;
        $sql->pgsql .= "\n\n" . $table_builder->pgsql;
        $sql->sqlite .= "\n\n" . $table_builder->sqlite;
      }
    }
    else{
      $this->console .= "Error making tables\n";
      return 999;
    }

    //Write the tables into the new Db (make sure to use the correct SQL type)
    $db = new Db();
    $db->write_raw($sql->{$db->type});
    if ($db->error != 0){
      $db->error;
    }
    // Set Date for 'created' fields
    $date = date('Y-m-d H:i:s');

    //Loop through inputs and write into the database
    $table = '';
    $inputs = array();
    if (isset($object->data) && is_array($object->data)){
      foreach($object->data as $data){
        $table = $data->table;
        $inputs = $data->inputs;
        if ($table == 'users'){
          // Add the Admin User using the User object
          $user = new LWT\User(-1);
          $user->login = $post->admin_user;
          $user->firstname = $inputs->firstname;
          $user->lastname = $inputs->lastname;
          $user->email = $post->admin_email;
          $user->roles = array(1 => [1]);
          $user->groups = array(0 => [0]);
          $user->write();
          $this->console .= "{$user->error} \n";
          if ($user->error){
            return $user->error;
          }
          $user->setpassword($post->admin_pass);
        }
        else{
          if (isset($inputs->created)){
            $inputs->created = $date;
          }
          $db->write($table, $inputs);
        }
      }
    }
    else{
      $this->console = "Error writing data";
      return 990;
    }

    return 0;
  }
}
