<?php
namespace LWT;
/**
 * Path Class
 * 
 * This object handles path requests from the user
 *
 * @category Path Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Path extends Tree{
  public $table = 'paths'; /**< Table name in Database */
  public $uri = '/';  /**< Request from the User, as a string */
  public $title = '';  /**< Title of the path, as loaded from the database */
  public $header = '200 OK';  /**< HTTP status of the request */
  public $access = false; /**< Whether this request can be fulfilled */
  public $path_id = null; /**< Path ID that would be fetched from database */
  public $app_root = 0; /**< Determines if this request is the root of a "subapp" */
  public $ajax_call = null; /**< Function to call prior to path load */
  public $render_call = null; /**< Function to call while loading path (in content area) */
  public $created = ''; /**< Date created in ISO format */
  public $activated = null; /**< Date when it is desired for path to be valid */
  public $deactivated = null; /**< Date when it is desired to deactivate the path */
  public $module_id = null; /**< Default template to use when rendering the path */
  public $root = ''; /**< Application root where database stopped */
  public $content = ''; /**< Path content to be shown if valid content */
  
  /**
   * Creates request
   *
   * @param string $uri Request URI from user
   * @param User $user User object requesting the path
   */
  public function __construct($uri, $user){
    $this->uri = $uri;
    $db = new Db();
    $path = explode("/",$uri);
    $i = 0;
    $this->app_root = 0;
    $this->path_id = null;
    $this->root = '';
    $this->content = '';
    foreach ($path as $i => $url_code){
      if($this->app_root == 0 && ($i == 0 || ($i > 0 && $url_code !== ''))){
        $db->fetch('paths',NULL, array('parent_id' => $this->path_id, 'url_code' => $url_code));
        if ($db->affected_rows > 0){
          $this->path_id = $db->output[0]['id'];
          $this->app_root = $db->output[0]['app_root'];
          $this->ajax_call = $db->output[0]['ajax_call'];
          $this->render_call = $db->output[0]['render_call'];
          $this->created = $db->output[0]['created'];
          $this->activated = $db->output[0]['activated'];
          $this->deactivated = $db->output[0]['deactivated'];
          $this->title = $db->output[0]['title'];
          $this->module_id = $db->output[0]['module_id'];
          $this->root .= $url_code . '/';
        }
        else{
          $this->path_id = null;
          $this->header = "404 Not Found";
          $this->access = FALSE;
          $this->title = 'Not Found';
          $this->module_id = null;
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
      // Retrieve any path content, if it exists
      $db->fetch_raw('SELECT * FROM "path_content" WHERE "path_id" = ' . $this->path_id . ' ORDER BY "created" DESC LIMIT 1');
      if ($db->affected_rows > 0){
        $this->content = $db->output[0]['content'];
      }
    }
    else{
      $this->path_id = null;
      $this->header = "404 Not Found";
      $this->title = 'Not Found';
      $this->ajax_call = null;
      $this->render_call = null;
      $this->module_id = null;
    }
    return;    
  }
  
  /**
   * Processes permissions to content based on group and role
   *
   * @param User $user User object containing authentication information
   *
   * @return boolean $access Whether the user is allowed to access the location
   */ 
  private function permissions($user){
    //First, never ever lockout THE Admin user
    if ($user->id == 1){
      return true;
    }

    //Assume no access
    $access = false;
    
    //Load the user's grouptree
    $all_groups = array();
    if (count($user->groups) > 0){
      foreach ($user->groups as $group_id){
        $group = new Group($group_id);
        $all_groups = $group->traverse($all_groups);
      }
    }
    else{
      $group = new Group(1);
      $all_groups = $group->traverse($all_groups);
    } 

    // Get the allowable groups for the path
    $db = new Db();
    $db->fetch('path_groups', NULL, array('path_id' => $this->path_id));
    if ($db->affected_rows > 0){
      foreach ($db->output as $record){
        if (in_array($record['group_id'],$all_groups)){
          $access = true;
        }
      }
    }
    
    // Check for Role overrides (if unset, means everyone can access!)
    $db->fetch('path_roles', NULL, array('path_id' => $this->path_id));
    if ($db->affected_rows > 0){
      //Reset access to false
      $access = false;
      foreach ($db->output as $record){
        if (in_array($record['role_id'],$user->roles)){
          $access = true;
        }
      }
    }
    return $access;
  }
}