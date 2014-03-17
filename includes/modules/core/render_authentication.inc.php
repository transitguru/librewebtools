<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders Authentication related pages
 */

/**
 * Renders a login page
 * 
 * @return boolean Successful completion
 */
function lwt_render_login(){

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
function lwt_render_profile(){
  $result = lwt_database_fetch_simple(DB_NAME,'users',NULL,array('id' => $_SESSION['authenticated']['id']));
  $profile = $result[0]; /**< Array of profile information */
  $submit = 'Update'; /**< Submit button value */

  // Check if _POST is set and process form
  $message = '';
  if (isset($_POST['submit']) && $_POST['submit']=='Update'){
    $message = '<span class="success"></span>';
    $error = false;
    if ($_SESSION['authenticated']['user'] != $_POST['login']){
      $result = lwt_database_fetch_simple(DB_NAME,'users',NULL,array('login' => $_POST['login']));
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
      $status = lwt_database_write(DB_NAME, 'users', $inputs, array('id' => $_SESSION['authenticated']['id']));
      $error = $status['error'];
      $message = $status['message'];
      $result = lwt_database_fetch_simple(DB_NAME,'users',NULL,array('id' => $_SESSION['authenticated']['id']));
      $profile = $result[0]; 
    }
  }
  elseif (isset($_POST['submit']) && $_POST['submit']=='Cancel'){
    $message = '<span class="warning">Profile was not changed.</span>';
    $result = lwt_database_fetch_simple(DB_NAME,'users',NULL,array('id' => $_SESSION['authenticated']['id']));
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

function lwt_render_password(){
  $submit = 'Update';


  // Check if _POST is set and process form
  $message = '';
  if (isset($_POST['submit']) && $_POST['submit']=='Update'){
  $message = '<span class="success">Data submitted correctly</span>';
  $error = false;
    if (!lwt_auth_authenticate_user($_SESSION['authenticated']['user'], $_POST['current_pwd'])){
      $message = '<span class="error">Existing password is not valid, please re-enter it.</span>';
      $error = true;
    }
    elseif ($_POST['pwd'] != $_POST['conf_pwd']){
      $message = '<span class="error">New Passwords do not match.</span>';
      $error = true;
    }
    if (!$error){
      $status = lwt_auth_session_setpassword($_SESSION['authenticated']['id'], $_POST['pwd']);
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
function lwt_render_forgot(){
  if($_SERVER['REQUEST_URI'] == APP_ROOT){
    if ($_POST['submit'] == 'Reset Password'){
      $email = $_POST["email"];
      lwt_auth_session_resetpassword($email);
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
    $result = lwt_database_fetch_simple(DB_NAME, 'passwords', array('user_id', 'reset_code'), array('reset_code' => $reset_request));
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
          $status = lwt_auth_session_setpassword($_SESSION['reset_user'], $inputs['pwd']);
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

