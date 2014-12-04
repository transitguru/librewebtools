<?php

/**
 * lwtRequest Class
 * 
 * This object handles page requests from the user
 *
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class lwtRequest{
  public $uri = '/';  /**< Request from the User, as a string */
  public $title = '';  /**< Title of the page, as loaded from the database */
  public $header = '200 OK';  /**< HTTP status of the request */
  public $access = false; /**< Whether this request can be fulfilled */
  public $page_id = null; /**< Page ID that would be fetched from database */
  public $app_root = 0; /**< Determines if this request is the root of a "subapp" */
  public $ajax_call = ''; /**< Function to call prior to page load */
  public $render_call = ''; /**< Function to call while loading page (in content area) */
  public $created = ''; /**< Date created in ISO format */
  public $activated = null; /**< Date when it is desired for page to be valid */
  public $deactivated = null; /**< Date when it is desired to deactivate the page */
  public $template = null; /**< Default template to use when rendering the page */
  public $root = ''; /**< Application root where database stopped */
  
  /**
   * Creates request
   *
   * @param string $uri Request URI from user
   * @param User $user User object requesting the page
   */
  public function __construct($uri, $user){
    $this->uri = $uri;
    $db = new lwtDb(DB_NAME);
    $path = explode("/",$uri);
    $i = 0;
    $this->app_root = 0;
    $this->page_id = null;
    $this->root = '';
    foreach ($path as $i => $url_code){
      if($this->app_root == 0 && ($i == 0 || ($i > 0 && $url_code !== ''))){
        $db->fetch('pages',NULL, array('parent_id' => $this->page_id, 'url_code' => $url_code));
        if ($db->affected_rows > 0){
          $this->page_id = $db->output[0]['id'];
          $this->app_root = $db->output[0]['app_root'];
          $this->ajax_call = $db->output[0]['ajax_call'];
          $this->render_call = $db->output[0]['render_call'];
          $this->created = $db->output[0]['created'];
          $this->activated = $db->output[0]['activated'];
          $this->deactivated = $db->output[0]['deactivated'];
          $this->title = $db->output[0]['title'];
          $this->root .= $url_code . '/';
        }
        else{
          $this->header = "404 Not Found";
          $this->access = FALSE;
          $this->title = 'Not Found';
          return;
        }
      }
    }
    
    return;
    // Set Application root for pages that act like applications
    $output = array();
    $groups = array();
    
    
    // Check permissions
    if (count($user->roles)>0){
      $roles = $user->roles;
    }
    else{
      $roles = array(0);
    }
    if (count($user->groups)>0){
      foreach ($user->groups as $group){
        $groups = core_process_grouptree($group, $groups);
      }
    }
    else{
      $group = 1; /**< Maps to the unauthenticated user */
      $groups = core_process_grouptree($group, $groups);
    }
    $output['access'] = core_process_permissions($page_id, $groups, $roles);
    if ($output['access']){
      // Check to see if it is still published
      $time = date('Y-m-d H:i:s');
      if (!is_null($activated) && $time < $activated){
        $output['access'] = false;
      }
      if (!is_null($activated) && $time < $activated){
        $output['access'] = false;
      }
      if(!is_null($deactivated) && $time > $deactivated){
        $output['access'] = false;
      }
      
      $output['page_id'] = $page_id;
      if ($output['access']){
        // Run ajax call, if it exists
        $ajax_call = $info[0]['ajax_call'];
        $output['title'] = $info[0]['title'];
        if (!is_null($ajax_call) && function_exists($ajax_call)){
          $ajax_call();
        }
        $output['render_call'] = $render_call;
      }
      else{
        $output['title'] = 'Not Found';
      }
      return $output;
    }
    else{
      // Return 404 title and send 404 header
      header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
      $output['title'] = 'Not Found';
      return $output;
    }    
  
  }
  

}
