<?php
namespace LWT;
/**
 * User Class
 * 
 * allows for loading and editing of user information and authentication
 * 
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class User{
  public $id = 0;               /**< User ID (0 if not logged in) */
  public $login = '';           /**< User Login */
  public $firstname = '';       /**< First name */
  public $lastname = '';        /**< Last name */
  public $email = '';           /**< Email address */
  public $created = '';         /**< Date that the user was created */
  public $desc = '';            /**< Description of user */
  public $groups = array();     /**< Groups that the user is a member of */
  public $roles = array();      /**< Roles that a user is a member of */
  public $message = '';         /**< Message to view when editing or viewing a profile */
  public $error = 0;            /**< Error (zero means no error) */
  
  /**
   * Constructs user based on user ID in database, or makes an empty user
   *
   * @param int $id Optional user ID to lookup in the database, or create new
   */
  public function __construct($id = 0){
    if ($id>0){
      // Lookup user by ID
      $db = new Db();
      $db->fetch('users', null, array('id' => $id));
      if ($db->affected_rows == 1){
        $this->id = $db->output[0]['id'];
        $this->login = $db->output[0]['login'];
        $this->firstname = $db->output[0]['firstname'];
        $this->lastname = $db->output[0]['lastname'];
        $this->created = $db->output[0]['created'];
        $this->email = $db->output[0]['email'];
        $this->desc = $db->output[0]['desc'];
        $db->fetch('user_roles', null, array('user_id' => $id), null, null, 'role_id');
        $this->roles = array();
        if ($db->affected_rows > 0){
          foreach ($db->output as $key => $value){
            $this->roles[$key] = $value['role_id'];
          }
        }
        $db->fetch('user_groups', null, array('user_id' => $id), null, null, 'group_id');
        $this->groups = array();
        if ($db->affected_rows > 0){
          foreach ($db->output as $key => $value){
            $this->groups[$key] = $value['group_id'];
          }
        }
      }
      else{
        $this->clear();
      }
    }
    else{
      // Ensure it is empty
      $this->clear();
      $this->id = $id;
    }
  }
  
  /**
   * Removes User information from object, but does not destroy the object
   */
  public function clear(){
    $this->id = 0;
    $this->login = '';
    $this->firstname = '';
    $this->lastname = '';
    $this->email = '';
    $this->created = '';
    $this->desc = '';
    $this->groups = array();
    $this->roles = array();
  }
  
  /**
   * Renders User Login Form
   */
  public function renderLogin(){
?>
    <?php echo $this->message; ?><br />
        <form id="login-form" method="post" action="">
          <label for="username">Username:</label> <input type="text" name="username" /><br />
          <label for="pwd">Password:</label> <input type="password" name="pwd" />
          <input name="login" type="submit" id="login" value="Log In">
        </form>
    <p>
      <a href="/forgot/">Forgot</a> your password?
    </p>
<?php
  }
  
  /**
   * Sets user information using login credentials
   *
   * @param string $username Login name for the user
   * @param string $password Unhashed password from the user
   */
  public function login($username, $password){
    //cleanse input
    $user = trim(strtolower($username));
    $pass = trim($password);
    $db = new Db();
    
    //lookup the user by ID
    $db->fetch('users', array('id'), array('login' => $user));
    if ($db->affected_rows > 0){
      $id = $db->output[0]['id'];
      $db->fetch('passwords', NULL, array('user_id' => $id), NULL, array('valid_date'));
      //Check for password
      if ($db->affected_rows>0){
        foreach ($db->output as $pwd){
          $hashed = $pwd['hashed'];
          $valid_date = $pwd['valid_date'];
          $passwords[$valid_date] = $hashed;
        }
        if (isset($hashed)){
          if (password_verify($pass, $hashed)){
            //Create the user!
            $this->__construct($id);
          }
        }
      }
    }
  }
  
  /**
   * Sets a password for a user (User must be loaded)
   * 
   * @param string $pass password
   */
  public function setpassword($pass=null){
    if (is_null($pass) && $this->id > 0){
      // Create a random password
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
      $len = strlen($chars);
      $pass = '';
      for ($i = 0; $i<10; $i++){
        $num = rand(0,$len-1);
        $reset_code .= substr($chars, $num, 1);
      }
    }
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $current_date = date("Y-m-d H:i:s");
    $db = new Db();
    $db->write('passwords', array('user_id' => $this->id, 'valid_date' => $current_date, 'hashed' => $hashed));
  }
  
  /**
   * Resets a user's lost password
   * 
   * @param string $email User's email address
   *
   */
  public function resetpassword($email){
    $db = new Db();
    $db->fetch('users', NULL, array('email' => $email));
    if ($db->affected_rows > 0){
      $id = $db->output[0]['id'];
      $login = $db->output[0]['login'];
      $db->fetch('passwords', array('user_id', 'valid_date'), array('user_id' => $id), NULL, array('valid_date'));
      if ($db->affected_rows > 0){
        foreach ($db->output as $data){
          $user_id = $data['user_id'];
          $valid_date = $data['valid_date'];
        }
      }
      $loop = TRUE;
      while ($loop){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $len = strlen($chars);
        $reset_code = "";
        for ($i = 0; $i<80; $i++){
          $num = rand(0,$len-1);
          $reset_code .= substr($chars, $num, 1);
        }
        $db->fetch('passwords', array('reset_code'), array('reset_code' => $reset_code));
        if ($db->affected_rows == 0){
          $loop = FALSE;
        }
      }
      $unix = time() + 24 * 60 * 60;
      $reset_date = date('Y-m-d H:i:s',$unix);
      $sql = "UPDATE `passwords` SET `reset_date` = '{$reset_date}' , `reset_code`='{$reset_code}' WHERE `user_id` = {$user_id} AND `valid_date` = '{$valid_date}'";
      $db->write_raw($sql);
      if ($db->error > 0){
        echo $db->error;
        echo "Fail!\n";
      }
      else{
        $headers = "From: LibreWebTools <noreply@transitguru.limited>\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8";
        mail($email, "Password Reset", "Username: {$login}\r\nPlease visit the following url to reset your password:\r\nhttp://librewebtools.org/forgot/{$reset_code}/", $headers);
      }
    }
  }

  /**
   * Renders a user profile editing page (intended for one's own user)
   */
  public function renderProfile(){
    if ($this->id > 0){
      $this->message = '';
      $this->error = 0;
      
      // Define form fields
      $fields = array();
      $fields['login'] = new Field($this->login, 'text', 'nowacky', true, 40);
      $fields['login']->element = 'text';
      $fields['login']->label = 'Login';
      $fields['login']->name = 'login';
      
      $fields['firstname'] = new Field($this->firstname, 'text', 'oneline', true, 100);
      $fields['firstname']->element = 'text';
      $fields['firstname']->label = 'First Name';
      $fields['firstname']->name = 'firstname';
      
      $fields['lastname'] = new Field($this->lastname, 'text', 'oneline', true, 100);
      $fields['lastname']->element = 'text';
      $fields['lastname']->label = 'Last Name';
      $fields['lastname']->name = 'lastname';
      
      $fields['email'] = new Field($this->email, 'text', 'email', true, 255);
      $fields['email']->element = 'text';
      $fields['email']->label = 'Email';
      $fields['email']->name = 'email';
      
      if (isset($_POST['submit']) && $_POST['submit']=='Update'){
        $this->message = '<span class="success">Success!</span>';
        $this->error = 0;
        
        // Set values to User POST
        $fields['login']->value = $_POST['login'];
        $fields['firstname']->value = $_POST['firstname'];
        $fields['lastname']->value = $_POST['lastname'];
        $fields['email']->value = $_POST['email'];
        
        // Validate the fields
        foreach ($fields as $key => $field){
          $fields[$key]->validate();
          if ($fields[$key]->error){
            $this->error = $fields[$key]->error;
          }
        }
        
        // Check for unique login
        if (!$this->error && $this->login != $fields['login']->value){
          $test = new Db();
          $test->fetch('users',NULL,array('login' => $fields['login']->value));
          if ($test->affected_rows > 0){
            $fields['login']->message = 'Already Taken: ';
            $this->error = 9999;
          }
        }
        
        if (!$this->error){
          $this->login = $fields['login']->value;
          $this->firstname = $fields['firstname']->value;
          $this->lastname = $fields['lastname']->value;
          $this->email = $fields['email']->value;
          $this->write();
          if (!$this->error){
            $this->message = '<span class="success">Success!</span>';
          }
        }
        else{
          $this->message = '<span class="error">Please fix the fields</span>';
        }
      }
      elseif (isset($_POST['submit']) && $_POST['submit']=='Cancel'){
        $this->message = '<span class="warning">Profile was not changed.</span>';
      }
      
      echo $this->message; ?><br />
      <h1>Edit your Profile</h1>
      <form action="" method="post" name="update_profile" id="update_profile">
<?php
      foreach ($fields as $field){
        $field->render();
      }
?>
        <input type="submit" name="submit" value="Update" /><input type="submit" name="submit" value="Cancel" />
      </form>      
<?php      
    }
  }
  
  /**
   * Renders the reset password page (for those already logged in)
   */
  public function renderPassword(){
    // Check if _POST is set and process form
    $message = '';
    if (isset($_POST['submit']) && $_POST['submit']=='Update'){
    $message = '<span class="success">Data submitted correctly</span>';
    $error = false;
      $testuser = new User();
      $testuser->login($this->login, $_POST['current_pwd']);
      if ($testuser->id < 1){
        $message = '<span class="error">Existing password is not valid, please re-enter it.</span>';
        $error = true;
      }
      elseif ($_POST['pwd'] != $_POST['conf_pwd']){
        $message = '<span class="error">New Passwords do not match.</span>';
        $error = true;
      }
      if (!$error){
        $this->setpassword($_POST['pwd']);
        if (!$this->error){
          $message = '<span class="success">Password successfully updated.</span>';
        }
        else{
          $message = '<span class="error">Error updating password.</span>';
        }
      }
    }
    if (isset($_POST['submit']) && $_POST['submit']=='Cancel'){
      $message = '<span class="warning">Password was not changed.</span>';
    }
      
?>
<?php echo $message; ?><br />
  <form action='' method='post' name='update_profile' id='update_profile'>
    <label for="current_pwd">Current Password</label><input name="current_pwd" type="password" /><br />
    <label for="pwd">New Password</label><input name="pwd" type="password" /><br />
    <label for="conf_pwd">Confirm Password</label><input name="conf_pwd" type="password" /><br />
    <input type="submit" name="submit" id="submit" value="Update" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Cancel" />
  </form>
<?php
  }
  
  /**
   * Renders the forgot password page
   */
  public function renderForgot(){
    if($_SERVER['REQUEST_URI'] == APP_ROOT){
      if ($_POST['submit'] == 'Reset Password'){
        $email = $_POST["email"];
        $this->resetpassword($email);
        $message = '<span class="warning">The information has been submitted. You should receive password reset instructions in your email.</span>';
      }
  ?>
        <?php echo $message; ?><br />
        <form action='' method='post' name='update_profile' id='update_profile'>
          <label for="email">Email Address: </label><input type="text" name="email" id="email" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Reset Password" /><br />
        </form>
  <?php
    }
    else{
      $chars = strlen(APP_ROOT);
      $reset_request = trim(substr($_SERVER['REQUEST_URI'],$chars),"/ ");
      $date = date('Y-m-d H:i:s');
      $db = new Db();
      $db->fetch_raw("SELECT * FROM `passwords` WHERE `reset_code`='{$reset_request}' AND `reset_date` > '{$date}'");
      if ($db->affected_rows == 0){
  ?>
      <p>The reset code does not match. Please visit the <a href="<?php echo APP_ROOT; ?>">Forgot Password</a> page</p>
  <?php
      }
      else{
        $_SESSION['reset_user'] = $db->output[0]['user_id'];  
        $submit = 'Update';
      
        // Check if _POST is set and process form
        $message = '';
        if ($_POST['submit']=='Update'){
          // Define form fields
          $inputs['pwd'] = $_POST['pwd'];
          $inputs['conf_pwd'] = $_POST['conf_pwd'];
        
          if ($inputs['pwd'] != $inputs['conf_pwd']){
            $message = '<span class="error">New Passwords do not match.</span>';
            $error = true;
          }
          if (!$error){
            $this->__construct($_SESSION['reset_user']);
            $this->setpassword($inputs['pwd']);
            if (!$this->error){
              $message = '<span class="success">Password successfully updated.</span>';
              unset($_SESSION['reset_user']);
              header("Location: /login/");
            }
            else{
              $message = '<span class="error">Error updating password.</span>';
            }
          }
        }
        if ($_POST['submit']=='Cancel'){
          $message = '<span class="warning">Password was not changed.</span>';
        }
      
  ?>
  <?php echo $message; ?><br />
  <h1>Edit your Password</h1>
  <form action='' method='post' name='update_profile' id='update_profile'>
    <label for="pwd">Password: </label><input name="pwd"  type="password" value="" /><br />
    <label for="conf_pwd">Confirm Password: </label><input name="conf_pwd"  type="password" value="" /><br />
    <input type="submit" name="submit" id="submit" value="Update" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Cancel" />
  </form>
  <?php
      }
    }
  
  }
  
  /**
   * Writes a user profile
   */
  public function write(){
    $db = new Db();
    $inputs['login'] = $this->login;
    $inputs['firstname'] = $this->firstname;
    $inputs['lastname'] = $this->lastname;
    $inputs['email'] = $this->email;
    $inputs['desc'] = $this->desc;
    if ($this->id > 0){
      $db->write('users', $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    elseif ($this->id < 0){
      $inputs['created'] = date('Y-m-d H:i:s');
      $this->created = $inputs['created'];
      $db->write('users', $inputs);      
      $this->error = $db->error;
      $this->message = $db->message;
      if (!$db->error){
        $this->id = $db->insert_id;
      }
    }
    else{
      $this->error = 1;
      $this->message = 'Cannot write user number 0';
    }
    if (!$this->error){
      // Empty out groups and roles database tables
      $db->write_raw("DELETE FROM `user_groups` WHERE `user_id` = {$this->id}");
      $db->write_raw("DELETE FROM `user_roles` WHERE `user_id` = {$this->id}");

      // Write the new roles and groups
      foreach ($this->groups as $group){
        $db->write('user_groups', array('group_id' => $group, 'user_id' => $this->id));
      }
      foreach ($this->roles as $role){
        $db->write('user_roles', array('role_id' => $role, 'user_id' => $this->id));
      }
    }
  }

  /**
   * Deletes the record, then clears the object
   */
  public function delete(){
    if ($this->id > 0){
      $db = new Db();
      $db->write_raw("DELETE FROM `users` WHERE `id`={$this->id}");
      if(!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }
}

