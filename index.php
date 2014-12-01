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

// Load the core
$PATH = $_SERVER['DOCUMENT_ROOT'] . '/includes/core';
$modules = scandir($PATH);
foreach ($includes as $include){
  if (is_dir($PATH . '/' . $include) && $include != '.' && $include != '..' && fnmatch("*.php", $include)){
    include ($PATH . '/' . $include);
  }
}

phpinfo();
