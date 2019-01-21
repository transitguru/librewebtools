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
  public $path;     /**< URI Exploded to components */
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
   * @param array $post POST data from user
   * @param array $files FILES data from user
   * @param array $get GET data from user
   *
   */
  public function __construct($uri = '/', $method = 'get', $session = array(), $post = array(), $files = array(), $get = array()){
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
    $this->path = explode('/', $uri);
    $this->session = $session;
    $this->post = $post;
    $this->files = $files;
    $this->get = $get;
  }
 
  /**
   * Routes the request based on URI path data
   */
  public function process(){
    if($this->path[0] == '' && isset($this->path[1])){
      
      if($this->path[1] == '' && !isset($this->path[2])){
        echo 'This is index';
      }
      elseif($this->path[1] == 'home' && !isset($this->path[2])){
        echo 'This is home';
      }
      else{
        http_response_code(404);
        echo '404';
      }
    }
    else{
      echo 'This was a script attempt';
    }
  }
}
