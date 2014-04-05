<?php

/**
 * @file 
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * This file handles authentication and user management
 */

/**
 * Checks user credentials against information found in the profile database
 * 
 * @param string $username 
 * @param string $password
 * @return boolean $status returns TRUE on success and FALSE on failure
 */
function lwt_auth_authenticate_user($username,$password){
    
  // initialize error variable
  $error = '';
  //cleanse input
  $user = trim(strtolower($username));
  $pass = trim($password);
  $user_info = lwt_database_fetch_simple(DB_NAME, 'users', array('id'), array('login' => $user));
  if (count($user_info)>0){
    $pwd_info = lwt_database_fetch_simple(DB_NAME, 'passwords', NULL, array('user_id' => $user_info[0]['id']), NULL, array('valid_date'));
    //Check for password
    foreach ($pwd_info as $pwd){
      $hash = $pwd['hash'];
      $key = $pwd['key'];
      $valid_date = $pwd['valid_date'];
      $passwords[$valid_date] = array($hash, $key);
    }
    if (isset($hash)){
      $hashed = crypt($pass, '$2a$07$'.$key.'$');
      if ($hash == $hashed){
        //Fetching user info
        $user_info = lwt_database_fetch_simple(DB_NAME, 'users', NULL, array('login' => $user));
        $_SESSION['authenticated']['id'] = $user_info[0]['id'];
        $_SESSION['authenticated']['user'] = $user_info[0]['login'];
        $_SESSION['authenticated']['firstname'] = $user_info[0]['firstname'];
        $_SESSION['authenticated']['lastname'] = $user_info[0]['lastname'];
        $_SESSION['authenticated']['email'] = $user_info[0]['email'];
        $_SESSION['authenticated']['desc'] = $user_info[0]['desc'];
        
        //fetching roles and groups
        $_SESSION['authenticated']['groups'] = array();
        $_SESSION['authenticated']['roles'] = array();
        $groups = lwt_database_fetch_simple(DB_NAME, 'user_groups', NULL, array('user_id' => $_SESSION['authenticated']['id']));
        foreach ($groups as $group){
          $_SESSION['authenticated']['groups'][] = $group['group_id'];
        }
        $roles = lwt_database_fetch_simple(DB_NAME, 'user_roles', NULL, array('user_id' => $_SESSION['authenticated']['id']));
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

function lwt_auth_session_resetpassword($email){
  $result = lwt_database_fetch_simple(DB_NAME, 'users', NULL, array('email' => $email));
  if (count($result) > 0){
    $user = $result[0]['id'];
    $passwords = lwt_database_fetch_simple(DB_NAME, 'passwords', array('user_id', 'valid_date'), array('user_id' => $result[0]['id']), NULL, array('valid_date'));
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
      $test = lwt_database_fetch_simple(DB_NAME, 'passwords', array('reset_code'), array('reset_code' => $reset_code));
      if (count($test) == 0){
        $loop = FALSE;
      }
    }
    $sql = "UPDATE `passwords` SET `reset` = 1 , `reset_code`='{$reset_code}' WHERE `user_id` = {$user_id} and `valid_date` = '{$valid_date}'";
    $success = lwt_database_write_raw(DB_NAME,$sql);
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
 * Sets a password for a user
 * 
 * @param string $user_id User ID
 * @param string $pass password
 * 
 * @return array $status an array determining success or failure with message explaining what happened
 *  
 */


function lwt_auth_session_setpassword($user_id, $pass){
  $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
  $len = strlen($chars);
  $key = "";
  for ($i = 0; $i<22; $i++){
    $num = rand(0,$len-1);
    $key .= substr($chars, $num, 1);
  }
  $hashed = crypt($pass, '$2a$07$'.$key.'$');
  date_default_timezone_set('UTC');
  $current_date = date("Y-m-d H:i:s");
  $sql = "INSERT INTO `passwords` (`user_id`, `valid_date`, `hash`, `key`) VALUES ('".$user_id."', '".$current_date."', '".$hashed."', '".$key."')";
  $success = lwt_database_write_raw(DB_NAME,$sql);
  return $success;
}

/**
 * Website gatekeeper (makes sure you are authenticated and didn't time out)
 * 
 * @param string $request Request URI
 * @param boolean $mainetenance Set to true if maintenance mode is on
 * 
 * @return string Request if successfully passed the gate
 * 
 */
function lwt_auth_session_gatekeeper($request, $maintenance = false){
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
