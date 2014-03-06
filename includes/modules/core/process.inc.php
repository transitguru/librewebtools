<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Functions for processing requests and finding the right page
 * 
 * @todo put the lookup information into the core database
 */



function lwt_process_authentication(){
  if (!isset($_SESSION['requested_page']) || $_SESSION['requested_page'] =='/login/'){
    $redirect = "/";
  }
  else{
    $redirect = $_SESSION['requested_page'];
  }
  if (isset($_POST['login'])) {
     // strip whitespace from user input
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // authenticate user
    $success = lwt_auth_authenticate_user($username, $password);
    if ($success){
      session_regenerate_id();
      header("Location: {$redirect}");
      exit;
    }
    else{
      header("Location: /login/");
      exit;
    }
  }
}

/**
 * Provides markup for the page_content div
 * 
 * @param string $request Request URI
 * @return string HTML Markup from the individual section that was requested
 */
function lwt_process_url($request){
  // Switchboard
  if ($request == '/'){
    return lwt_render_home();
  }
  elseif ($request == '/login/'){
    return lwt_render_login();
  }
  elseif ($request == '/register/'){
    return lwt_render_register();
  }
  elseif ($request == '/password/'){
    return lwt_render_password();
  }
  elseif (fnmatch("/forgot/*",$request)){
    return lwt_render_forgot();
  }
  elseif ($request == '/profile/'){
    return lwt_render_profile();
  }
  elseif ($request == '/maintenance/'){
    echo '<p>This site is under maintenance, please check again soon</p>';
    return TRUE;
  }
  else{
    echo '<p>The URL in the address bar may be in error, please return <a href="/">home</a>.</p>';
    return TRUE;
  }
}

/**
 * Provides a title for a given request URI
 * 
 * @param string $request Request URI
 * @return string Title to place in Title tags
 */
function lwt_process_title($request){
  if ($request == '/'){
    return 'Home';
  }
  elseif ($request == '/login/'){
    return 'Login';
  }
  elseif ($request == '/register/'){
    return 'Register';
  }
  elseif ($request == '/password/'){
    return 'Update Password';
  }
  elseif (fnmatch("/forgot/*",$request)){
    return 'Forgot Password';
  }
  elseif ($request == '/profile/'){
    return 'Profile';
  }
  elseif ($request == '/maintenance/'){
    return 'Maintenance';
  }
  else{
	header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    return 'Not Found';
  }
}

/**
 * Processes AJAX Requests
 * 
 * @return boolean 
 */
function lwt_process_ajax(){
  echo 'You are doing some AJAX!<pre>';
  var_dump($_POST);
  echo '</pre>';
  return TRUE;
}

