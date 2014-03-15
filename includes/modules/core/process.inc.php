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
  if (isset($_POST['login']) && $_POST['login'] == 'Log In') {
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
  $path = explode("/",$request);
  $i = 0;
  $app_root = 0;
  foreach ($path as $url_code){
    if ($i == 0){
      $i ++;
      $parent_id = 0;
      continue;
    }
    if(($url_code != '' || $parent_id == 0) && $app_root == 0){
      $info = lwt_database_fetch_simple(DB_NAME,'content_hierarchy',NULL, array('parent_id' => $parent_id, 'url_code' => $url_code));
      if (count($info)>0){
        $parent_id = $info[0]['content_id'];
        $app_root = $info[0]['app_root'];
      }
      else{
        echo '<p>The URL in the address bar may be in error, please return <a href="/">home</a>.</p>';
        return TRUE;
      }
    }
  }
  $info = lwt_database_fetch_simple(DB_NAME, 'content', NULL, array('id' => $parent_id));
  if (count($info)>0){
    $fn = $info[0]['function_call'];
    $content = $info[0]['content'];
    if (!is_null($fn) && function_exists($fn)){
      $success = $fn();
    }
    else{
      $success = TRUE;
    }
    if (!is_null($content)){
      echo $content;
    }
    return $success;
  }
}

/**
 * Provides a title for a given request URI
 * 
 * @param string $request Request URI
 * @return string Title to place in Title tags
 */
function lwt_process_title($request){
  $path = explode("/",$request);
  $i = 0;
  $app_root = 0;
  foreach ($path as $url_code){
    if ($i == 0){
      $i ++;
      $parent_id = 0;
      $_SESSION['ROOT'] = '';
      continue;
    }
    if(($url_code != '' || $parent_id == 0) && $app_root == 0){
      $info = lwt_database_fetch_simple(DB_NAME,'content_hierarchy',NULL, array('parent_id' => $parent_id, 'url_code' => $url_code));
      if (count($info)>0){
        $parent_id = $info[0]['content_id'];
        $app_root = $info[0]['app_root'];
        $_SESSION['ROOT'] .= '/' . $url_code;
      }
      else{
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        return 'Not Found';
      }
    }
  }
  if ($_SESSION['ROOT'] != '/'){
    $_SESSION['ROOT'] .= '/';
  }
  $info = lwt_database_fetch_simple(DB_NAME, 'content', array('title'), array('id' => $parent_id));
  if (count($info)>0){
    return $info[0]['title'];
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

