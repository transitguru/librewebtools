<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transiguru.info>
 * 
 * Authentication and session management 
 */

/**
 * Sets a password for a user
 * 
 * @param int $user_id User ID
 * @param string $pass password
 * 
 * @return array $status an array determining success or failure with message explaining what happened
 *  
 */
function lwt_auth_setpassword($user_id, $pass){
  $hashed = password_hash($pass, PASSWORD_DEFAULT);
  date_default_timezone_set('UTC');
  $current_date = date("Y-m-d H:i:s");
  $sql = "INSERT INTO `passwords` (`user_id`, `valid_date`, `hashed`) VALUES ({$user_id}, '{$current_date}', '{$hashed}')";
  $success = lwt_db_write_raw(DB_NAME,$sql);
  return $success;
}

/**
 * Resets a user's lost password
 * 
 * @param string $user_id User ID
 * @param string $pass password
 * 
 * @return array $status an array determining success or failure with message explaining what happened
 *  
 */
function lwt_auth_resetpassword($email){
  $result = lwt_db_fetch(DB_NAME, 'users', NULL, array('email' => $email));
  if (count($result) > 0){
    $user = $result[0]['id'];
    $passwords = lwt_db_fetch(DB_NAME, 'passwords', array('user_id', 'valid_date'), array('user_id' => $result[0]['id']), NULL, array('valid_date'));
    foreach ($passwords as $data){
      $user_id = $data['user_id'];
      $valid_date = $data['valid_date'];
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
      $test = lwt_db_fetch(DB_NAME, 'passwords', array('reset_code'), array('reset_code' => $reset_code));
      if (count($test) == 0){
        $loop = FALSE;
      }
    }
    $sql = "UPDATE `passwords` SET `reset` = 1 , `reset_code`='{$reset_code}' WHERE `user_id` = {$user_id} and `valid_date` = '{$valid_date}'";
    $success = lwt_db_write_raw(DB_NAME,$sql);
    if (!$success){
      echo $conn->error;
      echo "Fail!\n";
    }
    $headers = "From: LibreWebTools <noreply@transitguru.info>\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8";
    mail($email, "Password Reset", "Username: ".$user_id."\r\nPlease visit the following url to reset your password:\r\nhttp://transitguru.info/forgot/".$reset_code."/", $headers);
  }
  else{

  }
}

/**
 * Checks user credentials against information found in the profile database
 * 
 * @param string $username 
 * @param string $password
 * @return boolean $status returns TRUE on success and FALSE on failure
 */
function lwt_auth_authenticate($username,$password){
  // initialize error variable
  $error = '';
  //cleanse input
  $user = trim(strtolower($username));
  $pass = trim($password);
  $user_info = lwt_db_fetch(DB_NAME, 'users', array('id'), array('login' => $user));
  if (count($user_info)>0){
    $pwd_info = lwt_db_fetch(DB_NAME, 'passwords', NULL, array('user_id' => $user_info[0]['id']), NULL, array('valid_date'));
    //Check for password
    foreach ($pwd_info as $pwd){
      $hashed = $pwd['hashed'];
      $valid_date = $pwd['valid_date'];
      $passwords[$valid_date] = $hashed;
    }
    if (isset($hashed)){
      if (password_verify($pass, $hashed)){
        //Fetching user info
        $user_info = lwt_db_fetch(DB_NAME, 'users', NULL, array('login' => $user));
        $_SESSION['authenticated']['id'] = $user_info[0]['id'];
        $_SESSION['authenticated']['user'] = $user_info[0]['login'];
        $_SESSION['authenticated']['firstname'] = $user_info[0]['firstname'];
        $_SESSION['authenticated']['lastname'] = $user_info[0]['lastname'];
        $_SESSION['authenticated']['email'] = $user_info[0]['email'];
        $_SESSION['authenticated']['desc'] = $user_info[0]['desc'];
        
        //fetching roles and groups
        $_SESSION['authenticated']['groups'] = array();
        $_SESSION['authenticated']['roles'] = array();
        $groups = lwt_db_fetch(DB_NAME, 'user_groups', NULL, array('user_id' => $_SESSION['authenticated']['id']));
        foreach ($groups as $group){
          $_SESSION['authenticated']['groups'][] = $group['group_id'];
        }
        $roles = lwt_db_fetch(DB_NAME, 'user_roles', NULL, array('user_id' => $_SESSION['authenticated']['id']));
        foreach ($roles as $role){
          $_SESSION['authenticated']['roles'][] = $role['role_id'];
        }
        $_SESSION['start'] = time();
        $_SESSION['message'] = '<span class="success">You have successfully logged in.</span>';
        return true;
      }
      else {
        // if no match, return false
        $error = '<span class="error">Invalid username or password</span>';
        $_SESSION['message'] = $error;
        return false;
      } 
    }
  }
  $error = '<span class="error">Invalid username or password</span>';
  $_SESSION['message'] = $error;
  return false;
}

/**
 * Website gatekeeper (makes sure you are authenticated and didn't time out)
 *
 * @todo rethink how the redirect works in an AJAX environment
 * 
 * @param string $request Request URI
 * @param boolean $mainetenance Set to true if maintenance mode is on
 * 
 * @return string Request if successfully passed the gate
 * 
 */
function lwt_auth_gatekeeper($request, $maintenance = false){
  session_start();
    
  $timelimit = 60 * 60; /**< time limit in seconds */
  $now = time(); /**< current time */
  
  
  $redirect = '/'; /**< URI to redirect if timeout */
  
  if ($request != $redirect){
    $_SESSION['requested_page'] = $request;

    if ($now > $_SESSION['start'] + $timelimit  && isset($_SESSION['authenticated'])){
      // if timelimit has expired, destroy authenticated session
      unset($_SESSION['authenticated']);
      $_SESSION['start'] = time() - 86400;
      $_SESSION['message'] = "Your session has expired, please logon.";
      header("Location: {$redirect}");
      exit;
    }
    elseif (isset($_SESSION['authenticated']['user'])){
      // if it's got this far, it's OK, so update start time
      $_SESSION['start'] = time();
      $_SESSION['message'] = "Welcome {$_SESSION['authenticated']['user']}!";
    }
  }
  
  // Now route the request
  if (!$maintenance){
    if (substr($request,-1)!="/"){
      $request .= "/";
      header("location: $request");
      exit;
    }
  }
  elseif ($request != "/maintenance/"){
    header("location: /maintenance/");
    exit;
  }
  return $request;
}

/**
 * Renders a login page
 * 
 * @return boolean Successful completion
 */
function lwt_auth_login(){

?>
        <?php echo $_SESSION['message']; ?><br />
            <form id="login-form" method="post" action="">
              <label for="username">Username:</label> <input type="text" name="username" /><br />
              <label for="pwd">Password:</label> <input type="password" name="pwd" />
              <input name="login" type="submit" id="login" value="Log In">
            </form>
        <p>
          Need to <a href="/register/">register</a>? <br />
          <a href="/forgot/">Forgot</a> your password?
        </p>
<?php
  return TRUE;
}

/**
 * Renders a user profile editing page
 * 
 * @return boolean Successful completion
 */
function lwt_auth_profile(){
  $result = lwt_db_fetch(DB_NAME,'users',NULL,array('id' => $_SESSION['authenticated']['id']));
  $profile = $result[0]; /**< Array of profile information */
  $submit = 'Update'; /**< Submit button value */

  // Check if _POST is set and process form
  $message = '';
  if (isset($_POST['submit']) && $_POST['submit']=='Update'){
    $message = '<span class="success"></span>';
    $error = false;
    if ($_SESSION['authenticated']['user'] != $_POST['login']){
      $result = lwt_db_fetch(DB_NAME,'users',NULL,array('login' => $_POST['login']));
      if (count($result > 0)){
        $message = '<span class="error">Username already exists</span>';
        $error = TRUE;
      }
    }
    if (!$error){
      $inputs = array();
      $inputs['login'] = $_POST['login'];
      $inputs['firstname'] = $_POST['firstname'];
      $inputs['lastname'] = $_POST['lastname'];
      $inputs['email'] = $_POST['email'];
      $status = lwt_db_write(DB_NAME, 'users', $inputs, array('id' => $_SESSION['authenticated']['id']));
      $error = $status['error'];
      $message = $status['message'];
      $result = lwt_db_fetch(DB_NAME,'users',NULL,array('id' => $_SESSION['authenticated']['id']));
      $profile = $result[0]; 
    }
  }
  elseif (isset($_POST['submit']) && $_POST['submit']=='Cancel'){
    $message = '<span class="warning">Profile was not changed.</span>';
    $result = lwt_db_fetch(DB_NAME,'users',NULL,array('id' => $_SESSION['authenticated']['id']));
    $profile = $result[0];
  }
    
?>
<?php echo $message; ?><br />
      <h1>Edit your Profile</h1>
      <form action="" method="post" name="update_profile" id="update_profile">
        <label for="login">Username</label><input name="login" value="<?php echo $profile['login']; ?>" /><br />
        <label for="firstname">First Name</label><input name="firstname" value="<?php echo $profile['firstname']; ?>" /><br />
        <label for="lastname">Last Name</label><input name="lastname" value="<?php echo $profile['lastname']; ?>" /><br />
        <label for="email">Email</label><input name="email" value="<?php echo $profile['email']; ?>" /><br />
        <input type="submit" name="submit" value="Update" /><input type="submit" name="submit" value="Cancel" />
      </form>
<?php
}

/**
 * Renders a password change form (for those already logged in)
 * 
 * @return boolean Successful completion
 */
function lwt_auth_password(){
  $submit = 'Update';


  // Check if _POST is set and process form
  $message = '';
  if (isset($_POST['submit']) && $_POST['submit']=='Update'){
  $message = '<span class="success">Data submitted correctly</span>';
  $error = false;
    if (!lwt_auth_authenticate($_SESSION['authenticated']['user'], $_POST['current_pwd'])){
      $message = '<span class="error">Existing password is not valid, please re-enter it.</span>';
      $error = true;
    }
    elseif ($_POST['pwd'] != $_POST['conf_pwd']){
      $message = '<span class="error">New Passwords do not match.</span>';
      $error = true;
    }
    if (!$error){
      $status = lwt_auth_setpassword($_SESSION['authenticated']['id'], $_POST['pwd']);
      if ($status){
        $message = '<span class="success">Password successfully updated.</span>';
        $passes['current_pwd']['string'] = $passes['pwd']['string'] = $passes['conf_pwd']['string'] = '';
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
        <input type="submit" name="submit" id="submit" value="<?php echo $submit; ?>" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Cancel" />
      </form>
<?php
  return TRUE;
}


/**
 * Renders the forgotten password page
 * 
 * @return boolean Successful completion
 */
function lwt_auth_forgot(){
  if($_SERVER['REQUEST_URI'] == APP_ROOT){
    if ($_POST['submit'] == 'Reset Password'){
      $email = $_POST["email"];
      lwt_auth_resetpassword($email);
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
    $result = lwt_db_fetch(DB_NAME, 'passwords', array('user_id', 'reset_code'), array('reset_code' => $reset_request));
    if (count($result) == 0){
?>
    <p>The reset code does not match. Please visit the <a href="<?php echo APP_ROOT; ?>">Forgot Password</a> page</p>
<?php
    }
    else{
      $_SESSION['reset_user'] = $result[0]['user_id'];  
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
          $status = lwt_auth_setpassword($_SESSION['reset_user'], $inputs['pwd']);
          if ($status){
              $_SESSION['message'] = '<span class="success">Password successfully updated.</span>';
              $_SESSION['requested_page'] = "/";
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
  <input type="submit" name="submit" id="submit" value="<?php echo $submit; ?>" />&nbsp;&nbsp;<input type="submit" name="submit" id="cancel" value="Cancel" />
</form>
<?php
    }
  }
  return TRUE;
  
}

/**
 * Processes Login on login page (must be tied to a content item in the database)
 * 
 * @return void
 * 
 */
function lwt_auth_authentication(){
  if (isset($_SESSION['redirect']) && $_SESSION['redirect'] != ''){
    $redirect = $_SESSION['redirect'];
  }
  elseif (!isset($_SESSION['requested_page']) || $_SESSION['requested_page'] == APP_ROOT){
    $redirect = "/";
  }
  else{
    $redirect = $_SESSION['requested_page'];
  }
  if (isset($_POST['login']) && $_POST['login'] == 'Log In') {
     // strip whitespace from user input
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // authenticate user
    $success = lwt_auth_authenticate($username, $password);
    if ($success){
      session_regenerate_id();
      unset($_SESSION['redirect']);
      header("Location: {$redirect}");
      exit;
    }
    else{
      header("Location: " . APP_ROOT);
      exit;
    }
  }
}

/**
 * Processes Logout on logout page (must be tied to a content item in the database)
 * 
 * @return void
 * 
 */
function lwt_auth_logout(){
  if (isset($_COOKIE[session_name()])){
    setcookie(session_name(), '', time()-86400, '/');
  }
  // end session and redirect
  session_destroy();
  header("Location: /");
  exit;
}

