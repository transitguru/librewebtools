<?php
namespace LWT;
/**
 * @file
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
  public $http_status = 200;  /**< HTTP status of the request */
  public $access = false; /**< Whether this request can be fulfilled */
  public $path_id = null; /**< Path ID that would be fetched from database */
  public $app = null; /**< Determines if this request is a function */
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
    $this->app = null;
    $this->path_id = null;
    $this->root = '';
    $this->content = '';
    foreach ($path as $i => $url_code){
      if($this->app == null && ($i == 0 || ($i > 0 && $url_code !== ''))){
        $q = (object)[
          'command' => 'select',
          'table' => 'paths',
          'fields' => [],
        ];
        if ($i == 0){
          $q->where = (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $this->path_id, 'id' => 'parent_id'],
              (object)['type' => '=', 'value' => '/', 'id' => 'url_code'],
            ]
          ];
          $db->query($q);
        }
        else{
          $q->where = (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $this->path_id, 'id' => 'parent_id'],
              (object)['type' => '=', 'value' => $url_code, 'id' => 'url_code'],
            ]
          ];
          $db->query($q);
        }
        if ($db->affected_rows > 0){
          $this->path_id = (int) $db->output[0]->id;
          $this->app = $db->output[0]->app;
          $this->created = $db->output[0]->created;
          $this->activated = $db->output[0]->activated;
          $this->deactivated = $db->output[0]->deactivated;
          $this->title = $db->output[0]->title;
          $this->module_id = (int) $db->output[0]->module_id;
          $this->root .= $url_code . '/';
        }
        else{
          $this->path_id = null;
          $this->http_status = 404;
          $this->access = FALSE;
          $this->title = 'Not Found';
          $this->module_id = null;
          return;
        }
      }
    }

    // Check permissions
    if (count($user->roles)==0){
      $user->roles = [0];
    }
    $this->access = $this->permissions($user);
    if ($this->access){
      // Check to see if it is still published
      $time = date('Y-m-d H:i:s');
      if (!is_null($this->activated) && $time < $this->activated){
        $this->access = false;
      }
      if(!is_null($this->deactivated) && $time > $this->deactivated){
        $this->access = false;
      }
    }
    if ($this->access){
      // Retrieve any path content, if it exists
      $db->fetch_raw('SELECT * FROM "path_content" WHERE "path_id" = ' . $this->path_id . ' ORDER BY "created" DESC LIMIT 1');
      if ($db->affected_rows > 0){
        $this->content = $db->output[0]->content;
      }
    }
    else{
      $this->path_id = null;
      $this->http_status = 404;
      $this->title = 'Not Found';
      $this->app = null;
      $this->module_id = null;
    }
  }

  /**
   * Looks up path based on subapp module object class name
   *
   * @param $class Full class path of the subapp
   *
   * @return $path URI path of the subapp
   */
  public static function findapp($class){
    $path = false;
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'paths',
      'fields' => ['url_code', 'parent_id'],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $class, 'id' => 'app']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      $path = $db->output[0]->url_code;
      while ($db->output[0]->parent_id != 0){
        $q = (object)[
          'command' => 'select',
          'table' => 'paths',
          'fields' => ['url_code', 'parent_id'],
          'where' => (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $db->output[0]->parent_id, 'id' => 'id']
            ]
          ]
        ];
        $db->query($q);
        $path = $db->output[0]->url_code . '/' . $path;
      }
    }
    return '/' . $path;
  }

  /**
   * Processes permissions to content based on group and role
   *
   * @param User $user User object containing authentication information
   *
   * @return boolean $access Whether the user is allowed to access the location
   */
  public function permissions($user){
    //First, never ever lockout THE Admin user
    if ($user->id == 1){
      return true;
    }

    //Assume no access
    $access = false;

    //Load the user's grouptree
    $all_groups = $user->allgroups();

    // Get the allowable groups for the path
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'path_groups',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $this->path_id, 'id' => 'path_id']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      foreach ($db->output as $record){
        if (in_array($record->group_id,$all_groups)){
          $access = true;
        }
      }
    }

    // Check for Role overrides (if unset, means everyone can access!)
    $q = (object)[
      'command' => 'select',
      'table' => 'path_roles',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $this->path_id, 'id' => 'path_id']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      //Reset access to false
      $access = false;
      foreach ($db->output as $record){
        if (in_array($record->role_id,$user->roles)){
          $access = true;
        }
      }
    }
    return $access;
  }
}

