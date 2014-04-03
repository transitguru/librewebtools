<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Bootstrap file for LibreWebTools
 * 
 * This bootstraps the entire application and will provide a means to 
 * access all available modules.
 * 
 * @todo use settings module to ensure modules are turned on or off
 */

$begin = microtime(TRUE);
$debug = FALSE;


// Load all core modules
$PATH = $_SERVER['DOCUMENT_ROOT'] . '/includes/modules';
$modules = scandir($PATH);
foreach ($modules as $module){
  if (is_dir($PATH . '/' . $module) && $module != '.' && $module != '..'){
    $DIR = scandir($PATH . '/' . $module);
    foreach ($DIR as $include){
      if (is_file($PATH . '/' . $module . '/' . $include) && fnmatch("*.php", $include)){
        include ($PATH . '/' . $module . '/' . $include);
      }
    }
  }
}

// Load other "vendor Modules
$PATH = $_SERVER['DOCUMENT_ROOT'] . '/includes/vendor';
$modules = scandir($PATH);
foreach ($modules as $module){
  if (is_dir($PATH . '/' . $module) && $module != '.' && $module != '..'){
    $DIR = scandir($PATH . '/' . $module);
    foreach ($DIR as $include){
      if (is_file($PATH . '/' . $module . '/' . $include) && fnmatch("*.php", $include)){
        include ($PATH . '/' . $module . '/' . $include);
      }
    }
  }
}

// Load settings so that database can connect
lwt_settings_load();

$request = $_SERVER['REQUEST_URI']; /**< Request URI from user */

// Check to see if the database has been installed yet
lwt_install($request);


$maintenance = FALSE; /**< Set maintenance mode */
$request = lwt_auth_session_gatekeeper($request, $maintenance);

// Process Page
$success = lwt_render_wrapper($request); /**< Returns true if function completes! */


if ($debug){
  lwt_test_array_print($_SESSION); 
  
  $end = microtime(TRUE);
  lwt_test_showtime($begin, $end);
}

