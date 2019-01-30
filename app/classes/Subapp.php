<?php
namespace LWT;
/**
 * @file
 * Subapp Class
 * 
 * Facilitates the construction of subapps within modules
 * 
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Subapp{
  /**
   * Generic constructor for a Module that would inherit this class
   *
   * @param string $uri Request URI passed along to the application
   * @param string $method Lowercase method such as get, post
   * @param Object $user_input Inputs from user (POST, GET, FILES)
   * @param \LWT\Session $session User Session object
   */
  public function __construct($uri = '/', $method = 'get', $user_input = [], $session = []){
    $this->uri = $uri;
    $this->method = $method;
    $this->inputs = $user_input;
    $this->session = $session;
  }
}
