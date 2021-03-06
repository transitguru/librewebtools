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

/**
 * Information from user (URI, POST, GET, FILES)
 */
$input = (object)[
  'uri' => '/',
  'method' => 'get',
  'content_type' => 'text/plain',
  'get' => (object)[],
  'post' => (object)[],
  'files' => (object)[],
];

// Collect globals when using webserver
if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])){
  $site_root = $_SERVER['DOCUMENT_ROOT'];
  $sr_len = mb_strlen($site_root);
  define('BASE_URI', mb_substr(DOC_ROOT, $sr_len));
  define('HOSTNAME', $_SERVER['SERVER_NAME']); //TODO: USE settings datbase table
  if (isset($_SERVER['HTTPS'])){
    define('PROTOCOL', 'https');
  }
  else{
    define('PROTOCOL', 'http');
  }
  $b_len = mb_strlen(BASE_URI);
  $input->uri = mb_substr($_SERVER['REQUEST_URI'], $b_len);
  $input->method = strtolower($_SERVER['REQUEST_METHOD']);

  if (isset($_SERVER['CONTENT_TYPE'])){
    $input->content_type = $_SERVER['CONTENT_TYPE'];
  }
  else{
    $input->content_type = 'text/plain';
  }
  if (isset($_POST)){
    if (fnmatch('application/json*', $input->content_type)){
      $raw = file_get_contents('php://input');
      $input->post = json_decode($raw);
    }
    elseif ($input->method == 'post'){
      $input->post = json_decode(json_encode($_POST));
      if (isset($_FILES)){
        $input->files = json_decode(json_encode($_FILES));
      }
    }
    else{
      $parsed = [];
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
  else{
    $session = new LWT\Session(0);
  }
}
else{
  define('BASE_URI', '/');
  define('HOSTNAME', 'librewebtools.org');
  define('PROTOCOL', 'https');
}

// Check to see if the application is installed
$installer = new LWT\Installer($input->uri,$input->post);

// Get user information
if (isset($session->user_id)){
  $user = new LWT\User($session->user_id);
}
else{
  $user = new LWT\User(0);
}

// Ensure path is properly formed
$newuri = $input->uri;
while (fnmatch('*//*', $newuri)){
  $newuri = preg_replace('/\/+/', '/', $newuri);
}
while (fnmatch('*../*', $newuri)){
  $newuri = preg_replace('/\.\.\/+/', '/', $newuri);
}
while (strlen($newuri) > 1 && substr($newuri, -1) == '/'){
  $newuri = substr($newuri, 0, -1);
}

if ($newuri != $input->uri){
  header('Location: ' . BASE_URI . $newuri);
  exit;
}

// Load enabled modules and chosen theme
$path = new LWT\Path(-1);
$path->request($input->uri,$user);
$module = new LWT\Module($path->module_id,$input,$session);
$module->loadTemplate($path);

