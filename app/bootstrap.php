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
 * @copyright  Copyright (c) 2015-2018
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
$uri = '/';         /**< Request URI */
$session = array(); /**< User Session */
$post = array();    /**< Information from POST  */
$files = array();   /**< Information from FILES (only when used in webserver) */
$get = array();     /**< Information GET */
$method = 'get';    /**< Lowercase method such as POST, PUT, or GET */

// Collect globals when using webserver
if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])){
  $uri = $_SERVER['REQUEST_URI'];
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
      $post = json_decode($raw, true);
    }
    elseif ($method == 'post'){
      $post = $_POST;
      $files = $_FILES;
    }
    else{
      $raw = file_get_contents('php://input');
      parse_str($raw, $post);
    }
  }
  if (isset($_GET)){
    $get = $_GET;
  }
  if (isset($_COOKIE) && isset($_COOKIE['librewebtools'])){
    $session = new phuSession($_COOKIE['librewebtools']);
  }
}

// The data gets through the router, which will route the request
$router = new LWT\Router($uri, $method, $session, $post, $files, $get);
$router->process();
