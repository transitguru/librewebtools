<?php

/**
 * @file
 * Bootstrap file for LibreWebTools
 *
 * This bootstraps the entire application by collecting inputs and using them
 * to access the router, which will then access appropriate classes
 *
 * @category   Bootstrap
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2015-2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */

// Load the settings files
require_once (DOC_ROOT . '/app/settings.php');

// Load the classes
$PATH = DOC_ROOT . '/app/classes';
$includes = scandir($PATH);
foreach ($includes as $include){
  if (is_file($PATH . '/' . $include) && $include != '.' && $include != '..' && fnmatch("*.php", $include)){
    require_once ($PATH . '/' . $include);
  }
}

// These variables are used to remove reliance on superglobals
$uri = '/';             /**< Request URI */
$session = (object)[];  /**< User Session */
$input = (object)[];    /**< Information from user (POST, GET, FILES)  */
$method = 'get';        /**< Lowercase method such as POST, PUT, or GET */

// Collect globals when using webserver
if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])){
  $site_root = $_SERVER['DOCUMENT_ROOT'];
  $sr_len = mb_strlen($site_root);
  define('BASE_URI', mb_substr(DOC_ROOT, $sr_len));
  $b_len = mb_strlen(BASE_URI);
  $uri = mb_substr($_SERVER['REQUEST_URI'], $b_len);
  $method = strtolower($_SERVER['REQUEST_METHOD']);

  if (isset($_SERVER['CONTENT_TYPE'])){
    $content_type = $_SERVER['CONTENT_TYPE'];
  }
  else{
    $content_type = 'text/plain';
  }
  if (isset($_POST)){
    if (fnmatch('application/json*', $content_type) || fnmatch('text/json*', $content_type)){
      $raw = file_get_contents('php://input');
      $input->post = json_decode($raw);
    }
    elseif ($method == 'post'){
      $input->post = json_decode(json_encode($_POST));
      if (isset($_FILES)){
        $input->files = json_decode(json_encode($_FILES));
      }
    }
    else{
      $parsed = array();
      $raw = file_get_contents('php://input');
      mb_parse_str($raw, $parsed);
      $input->post = json_decode(json_encode($parsed));
    }
  }
  if (isset($_GET)){
    $input->get = json_decode(json_encode($_GET));
  }
  if (isset($_COOKIE) && isset($_COOKIE['librewebtools'])){
    $session = new LWT\Session($_COOKIE['librewebtools']);
  }
}

// Check to see if the application is installed
$installer = new LWT\Installer($input->post);

// Get user information
if (isset($session->user_id)){
  $user = new User($session->user_id);
}
else{
  $user = new User(0);
}

$path = new LWT\Path($uri,$user);
define('APP_ROOT', $path->root);
// Load enabled modules and chosen theme
$module = new LWT\Module($path->module_id,$user_input);
$module->loadMods(1);
$module->loadMods(0);
$module->loadTheme($path);
