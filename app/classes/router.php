<?php
namespace LWT;
/**
 * @file
 * Router Class
 * 
 * Determines routing for all requests
 * TODO add in some permissions and such
 *
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2015-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 *
 */
class Router{
  
  public $uri;      /**< Request URI to route the request */
  public $method;   /**< HTTP Method used for the request */
  public $session;  /**< User session from bootstrap */
  public $post;     /**< POST Request processed from bootstrap */
  public $files;    /**< FILES array processed from bootstrap */
  public $get;      /**< GET Request processed from bootstrap */

  /**
   * Constructor
   *
   * @param string $uri Request URI for this request
   * @param string $method Lowercase HTTP method for the request
   * @param Session $session Session object for user
   * @param object $user_inputs Input data from user (POST, FILES, GET)
   *
   */
  public function __construct($uri = '/', $method = 'get', $session = [], $user_inputs = []){
    if (fnmatch('*//*', $uri)){
      $newuri = preg_replace('/\/+/', '/', $uri);
      header("Location: {$newuri}");
      exit;
    }
    if (fnmatch('*../*', $uri)){
      $newuri = preg_replace('/\.\.\/+/', '/', $uri);
      header("Location: {$newuri}");
      exit;
    }
    if (strlen($uri) > 1 && substr($uri, -1) == '/'){
      $newuri = substr($uri, 0, -1);
      header("Location: {$newuri}");
      exit;
    }
    $this->uri = $uri;
    $this->method = $method;
    $this->session = $session;
    $this->post = $user_inputs->post;
    $this->files = $user_inputs->files;
    $this->get = $user_inputs->get;
  }
 
  /**
   * Routes the request based on URI path data
   */
  public function process(){
    // Get user information
    if (isset($this->session['user_id'])){
      $user = new User($this->session['user_id']);
    }
    else{
      $user = new User(0);
    }
    $path = new Path($this->uri,$user);
    define('APP_ROOT', $path->root);
    // Load enabled modules and chosen theme
    $module = new Module($path->module_id);
    $module->loadMods(1);
    $module->loadMods(0);
    $module->loadTheme($path);
  }
}

