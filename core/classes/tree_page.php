<?php

/**
 * corePage Class
 * 
 * This object handles page requests from the user
 *
 * @category Page Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class corePage extends coreTree{
  public $table = 'pages'; /**< Table name in Database */
  public $uri = '/';  /**< Request from the User, as a string */
  public $title = '';  /**< Title of the page, as loaded from the database */
  public $header = '200 OK';  /**< HTTP status of the request */
  public $access = false; /**< Whether this request can be fulfilled */
  public $page_id = null; /**< Page ID that would be fetched from database */
  public $app_root = 0; /**< Determines if this request is the root of a "subapp" */
  public $ajax_call = null; /**< Function to call prior to page load */
  public $render_call = null; /**< Function to call while loading page (in content area) */
  public $created = ''; /**< Date created in ISO format */
  public $activated = null; /**< Date when it is desired for page to be valid */
  public $deactivated = null; /**< Date when it is desired to deactivate the page */
  public $theme_id = null; /**< Default template to use when rendering the page */
  public $root = ''; /**< Application root where database stopped */
  public $content = ''; /**< Page content to be shown if valid content */
  
  /**
   * Creates request
   *
   * @param string $uri Request URI from user
   * @param User $user User object requesting the page
   */
  public function __construct($uri, $user){
    $this->uri = $uri;
    $db = new coreDb();
    $path = explode("/",$uri);
    $i = 0;
    $this->app_root = 0;
    $this->page_id = null;
    $this->root = '';
    $this->content = '';
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
          $this->theme_id = $db->output[0]['theme_id'];
          $this->root .= $url_code . '/';
        }
        else{
          $this->page_id = null;
          $this->header = "404 Not Found";
          $this->access = FALSE;
          $this->title = 'Not Found';
          $this->theme_id = null;
          return;
        }
      }
    }
    
    // Check permissions
    if (count($user->roles)>0){
      $roles = $user->roles;
    }
    else{
      $roles = array(0);
    }
    $this->access = $this->permissions($user);
    if ($this->access){
      // Check to see if it is still published
      $time = date('Y-m-d H:i:s');
      if (!is_null($this->activated) && $time < $this->activated){
        $this->access = false;
      }
      if (!is_null($this->activated) && $time < $this->activated){
        $this->access = false;
      }
      if(!is_null($this->deactivated) && $time > $this->eactivated){
        $this->access = false;
      }
    }
    if ($this->access){
      // Retrieve any page content, if it exists
      $db->fetch_raw("SELECT * FROM `page_content` WHERE `page_id` = {$this->page_id} ORDER BY `created` DESC LIMIT 1");
      if ($db->affected_rows > 0){
        $this->content = $db->output[0]['content'];
      }
    }
    else{
      $this->page_id = null;
      $this->header = "404 Not Found";
      $this->title = 'Not Found';
      $this->ajax_call = null;
      $this->render_call = null;
      $this->theme_id = null;
    }
    return;    
  }
  
  /**
   * Processes permissions to content based on group and role
   *
   * @param coreUser $user User object containing authentication information
   *
   * @return boolean $access Whether the user is allowed to access the location
   */ 
  private function permissions($user){
    //First, never ever lockout THE Admin user
    if ($user->id == 1){
      return TRUE;
    }

    //Assume no access
    $access = FALSE;
    
    //Load the user's grouptree
    $all_groups = array();
    if (count($user->groups) > 0){
      foreach ($user->groups as $group_id){
        $group = new coreGroup($group_id);
        $all_groups = $group->traverse($all_groups);
      }
    }
    else{
      $group = new coreGroup(1);
      $all_groups = $group->traverse($all_groups);
    } 

    // Get the allowable groups for the page
    $db = new coreDb();
    $db->fetch('page_groups', NULL, array('page_id' => $this->page_id));
    if ($db->affected_rows > 0){
      foreach ($db->output as $record){
        if (in_array($record['group_id'],$all_groups)){
          $access = TRUE;
        }
      }
    }
    
    // Check for Role overrides (if unset, means everyone can access!)
    $db->fetch('page_roles', NULL, array('page_id' => $this->page_id));
    if ($db->affected_rows > 0){
      //Reset access to false
      $access = FALSE;
      foreach ($db->output as $record){
        if (in_array($record['role_id'],$user->roles)){
          $access = TRUE;
        }
      }
    }
    return $access;
  }
}
