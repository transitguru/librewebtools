<?php

/**
 * coreSite Class
 *
 * Boots the site
 * 
 * @category Bootstrap
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreSite{
  // Define public variables
  public $uri = '/';          /**< Request URI */
  public $session = array();  /**< User Session Data */
  public $post = array();     /**< Post Data */
  
  /**
   * Create the bootloader object
   */
  public function __construct($uri, $session=array(), $post=array()){
    $this->uri = $uri;
    $this->session = $session;
    $this->post = $post;
  }
  
  /**
   * Boot the site
   */
  public function boot(){
    // Get settings
    $settings = new coreSettings();
  
    // Get user information
    if (isset($this->session['user_id'])){
      $user = new coreUser($this->session['user_id']);
    }
    else{
      $user = new coreUser(0);
    }
    
    // Load page request
    $page = new coreRequest($this->uri, $user);
    echo "<pre>\n\nUser Information\n";
    var_dump($user);
    echo "\n\nPage Information\n";
    var_dump($page);
    
    echo '</pre>';
  }

}
