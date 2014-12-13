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

// Collect globals
$uri = $_SERVER['REQUEST_URI'];          /**< Request URI */

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

// Check to see if the site is installed
$installer = new coreInstaller();
if ($installer->install == true && $uri !== '/install/'){
  header("Location: /install/");
  exit;
}
elseif ($installer->install == true && $uri === '/install/'){
  if (isset($_POST['db'])){
    $installer->build($_POST['db']);
  }
  $installer->view();
}

// Get user information
if (isset($_SESSION['user_id'])){
  $user = new coreUser($_SESSION['user_id']);
}
else{
  $user = new coreUser(0);
}

// Load page request
$page = new corePage($uri, $user);
define('APP_ROOT', $page->root);

// Load enabled modules and chosen theme
$theme = new coreModule($page->theme_id);
$theme->loadMods(1);
$theme->loadMods(0);
$theme->loadTheme($page);

var_dump($page);

