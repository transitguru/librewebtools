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
$start = microtime(true);
define('DOC_ROOT', getcwd());

$PATH = DOC_ROOT . '/includes/core';
$includes = scandir($PATH);
foreach ($includes as $include){
  if (is_file($PATH . '/' . $include) && $include != '.' && $include != '..' && fnmatch("*.php", $include)){
    include ($PATH . '/' . $include);
  }
}

$svg = file_get_contents(DOC_ROOT . '/includes/design/design.svg');
echo $svg;
$end = microtime(true);
$time = 1000 * ($end - $start);
echo $time . "ms";
