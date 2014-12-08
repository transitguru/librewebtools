<?php

/**
 * @file
 * Bootstrap file for LibreWebTools
 *
 * This bootstraps the entire application and will provide a means to 
 * access all available modules.
 *
 * @category   Bootstrap
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.info>
 * @copyright  Copyright (c) 2014
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */

// Start the session
session_start();
$_SESSION['user_id'] = 0; /**< This will change once login part is set up! */
$start = microtime(true); /**< This will be removed!!! */

// Load the settings file
require_once (DOC_ROOT . '/core/settings.php');

// Load all core classes
$PATH = DOC_ROOT . '/core/classes';
$includes = scandir($PATH);
foreach ($includes as $include){
  if (is_file($PATH . '/' . $include) && $include != '.' && $include != '..' && fnmatch("*.php", $include)){
    require_once ($PATH . '/' . $include);
  }
}

// Test content (the design diagram, for the moment)
echo "<!DOCTYPE html>\n<html>\n  <head>\n    <meta charset=\"utf-8\" />\n    <title>LibreWebTools</title>\n</head>\n  <body>\n    <h1>Under Construction</h1><p>Please visit my <a href=\"https://github.com/transitguru/librewebtools\">GitHub</a> for more information about release plans.</p>\n";
$svg = file_get_contents(DOC_ROOT . '/core/design/design.svg');
echo $svg;

// Boot site object
$site = new coreSite($_SERVER['REQUEST_URI'], $_SESSION, $_POST);
$site->boot();

// Show time count and end the HTML tags (Will be removed)
$end = microtime(true);
$time = 1000 * ($end - $start);
echo $time . "ms";
echo "\n  </body>\n</html>";

