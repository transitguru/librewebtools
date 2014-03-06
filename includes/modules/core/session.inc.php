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
        $_SESSION['authenticated']['company'] = $company_id = $user_info[0]['id'];
        $_SESSION['authenticated']['user'] = $user_info[0]['login'];
        $_SESSION['authenticated']['firstname'] = $user_info[0]['firstname'];
        $_SESSION['authenticated']['lastname'] = $user_info[0]['lastname'];
        $_SESSION['authenticated']['email'] = $user_info[0]['email'];
        $_SESSION['authenticated']['role'] = $user_info[0]['role'];
        if (!is_null($user_info[0]['image'])){
            $_SESSION['authenticated']['image'] = $user_info[0]['image'];
        }

        //fetching company info
        echo $c;
        $company_info = lwt_database_fetch_simple(DB_NAME, 'companies', NULL, array('id' => $company_id));
        $_SESSION['authenticated']['company_name'] = $company_info[0]['company_name'];
        if (!is_null($logo)){
            $_SESSION['authenticated']['logo'] = $company_info[0]['logo'];
        }
        $_SESSION['start'] = time();
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
    $user = $result[0]['user_id'];
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
    $reset_code = $conn->real_escape_string($reset_code);
    $user_id = $conn->real_escape_string($user_id);
    $valid_date = $conn->real_escape_string($valid_date);
    $sql = "UPDATE `Password` SET `reset` = 1 , `reset_code`='".$reset_code."' WHERE `user_id` = '".$user_id."' and `valid_date` = '".$valid_date."'";
    $success = lwt_database_write_raw(DB_NAME,$sql);
    if (!$success){
      echo $conn->error;
      echo "Fail!\n";
    }
    echo $reset_code;
    $headers = "From: Data Tools <noreply@transitguru.info>\r\n";
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


function lwt_auth_session_gatekeeper($request, $maintenance = false){
  session_start();
    
  $timelimit = 60 * 60; /**< time limit in seconds */
  $now = time(); /**< current time */
  
  /** URIs available without login */
  $public_uris = array(
    "/",
    "/contact/",
    "/sitemap/",
    "/register/",
    "/forgot/",
    "/maintenance/",
  );

  $redirect = '/login/'; /**< URI to redirect if rejected */
  
  // Determine if you are asking to logout
  if ($request == "/logout/"){
    if (isset($_COOKIE[session_name()])){
      setcookie(session_name(), '', time()-86400, '/');
    }
    // end session and redirect
    session_destroy();
    header("Location: /");
    exit;
  }
  elseif ($request == "/register/"){
    $_SESSION['message'] = "Please register using the access key that you were provided.";
  }
  elseif (fnmatch("/forgot/*",$request)){
    return $request;
  }
  elseif ($request != $redirect){
    $_SESSION['requested_page'] = $request;

    if (!isset($_SESSION['authenticated'])){
      $_SESSION['message'] = "Please logon to access tools!";
      header("Location: $redirect");
      exit;
    }
    elseif ($now > $_SESSION['start'] + $timelimit  && !in_array($request,$public_uris)){
      // if timelimit has expired, destroy authenticated session and redirect
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
  else{
    $_SESSION['message'] = "Please logon to access tools!";
  }
  
  lwt_process_authentication();
  
  // Now route the request
  if (!$maintenance){

    // handle files
    if (substr($request,-1)!="/" and fnmatch('/file/*',$request)){
      $file = $_SERVER['DOCUMENT_ROOT']."/FILES/".$request;
      $simple = true;
      $size = filesize($file);
      $type = finfo_file($finfo, $file, FILEINFO_MIME_TYPE);
      header('Pragma: ');         // leave blank to avoid IE errors
      header('Cache-Control: ');  // leave blank to avoid IE errors
      header('Content-Length: ' . $size);
      header('Content-Type: ' .$type);
      sleep(0); // gives browser a second to digest headers
      readfile($file);
      exit;
    }
    // add slashes if not a file
    elseif (fnmatch('/grapher/*',$request)){
      return $request;
    }
    elseif (substr($request,-1)!="/"){
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
