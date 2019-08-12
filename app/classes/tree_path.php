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
  public $id = null; /**< Path ID that would be fetched from database */
  public $app = null; /**< Determines if this request is a function */
  public $url_code = null; /**< URL code at particular level */
  public $created = null; /**< Date created in ISO format */
  public $activated = null; /**< Date when it is desired for path to be valid */
  public $deactivated = null; /**< Date when it is desired to deactivate the path */
  public $module_id = null; /**< Default template to use when rendering the path */
  public $groups = []; /**< Groups that the path is accessible to */
  public $roles = []; /**< Roles that a path is accessible to */
  public $history = []; /**< Path content history */
  public $root = ''; /**< Application root where database stopped */
  public $content = []; /**< Path content to be shown if valid content */
  public $url_unique = true;  /**< Flag to show if the url_code is unique */
  public $url_message = '';   /**< Message for error in url_code unique */

  /**
   * Creates Path object
   *
   * @param int $id ID of path
   */
  public function __construct($id){
    $path = false;
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'paths',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $id, 'id' => 'id']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      $path = $db->output[0]->url_code;
      $this->id = (int) $db->output[0]->id;
      $this->app = $db->output[0]->app;
      $this->url_code = $db->output[0]->url_code;
      $this->created = $db->output[0]->created;
      $this->activated = $db->output[0]->activated;
      $this->deactivated = $db->output[0]->deactivated;
      $this->title = $db->output[0]->title;
      $this->module_id = (int) $db->output[0]->module_id;
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
      $this->root = '/' . $path . '/';

      //Find Roles
      $q = (object)[
        'command' => 'select',
        'table' => 'path_roles',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $id, 'id' => 'path_id']
          ]
        ]
      ];
      $db->query($q);
      $this->roles = [];
      if ($db->affected_rows > 0){
        foreach ($db->output as $field){
          $this->roles[] = (int) $field->role_id;
        }
      }

      //Find Groups
      $q->table = 'path_groups';
      $db->query($q);
      $this->groups = [];
      if ($db->affected_rows > 0){
        foreach ($db->output as $field){
          $this->groups[] = (int) $field->group_id;
        }
      }

      // Retrieve any path content history, if it exists
      $q->table = 'path_content';
      $q->sort = (object) ['id' => 'created', 'dir' => 'd'];
      $db->query($q);
      if ($db->affected_rows > 0){
        $this->history = [];
        foreach ($db->output as $field){
          $this->history[] = (object)[
            'id' => (int) $field->id,
            'user_id' => (int) $field->user_id,
            'created' => $field->created,
            'title' => $field->title,
            'summary' => $field->summary,
            'content' => $field->content,
          ];
        }
        $this->content = $this->history[0];
      }
      else{
        $this->content = (object)[
          'id' => (int) -1,
          'user_id' => 1,
          'created' => '',
          'title' => '',
          'summary' => '',
          'content' => '',
        ];
      }
    }
  }

  /**
   * Creates request
   *
   * @param string $uri Request URI from user
   * @param User $user User object requesting the path
   */
  public function request($uri, $user){
    $this->uri = $uri;
    $db = new Db();
    $path = explode("/",$uri);
    $i = 0;
    $this->app = null;
    $this->id = null;
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
              (object)['type' => '=', 'value' => $this->id, 'id' => 'parent_id'],
              (object)['type' => '=', 'value' => '/', 'id' => 'url_code'],
            ]
          ];
          $db->query($q);
        }
        else{
          $q->where = (object)[
            'type' => 'and', 'items' => [
              (object)['type' => '=', 'value' => $this->id, 'id' => 'parent_id'],
              (object)['type' => '=', 'value' => $url_code, 'id' => 'url_code'],
            ]
          ];
          $db->query($q);
        }
        if ($db->affected_rows > 0){
          $this->id = (int) $db->output[0]->id;
        }
        else{
          $this->id = null;
          $this->http_status = 404;
          $this->access = FALSE;
          $this->title = 'Not Found';
          $this->module_id = null;
          return;
        }
      }
    }

    $this->__construct($this->id);
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
    }
    else{
      $this->id = null;
      $this->http_status = 404;
      $this->title = 'Not Found';
      $this->app = null;
      $this->module_id = null;
    }
  }

  /**
   * Lists all items in a nested array
   *
   * @param int $parent_id Parent ID of items being searched (default null)
   *
   * @return array $list All group items as a nested array of objects
   */
  public function list($parent_id = null){
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'paths',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $parent_id, 'id' => 'parent_id']
        ]
      ],
      'sort' => [
        (object) ['id' => 'sortorder'],
        (object) ['id' => 'title'],
      ]
    ];
    $db->query($q);
    $list = [];
    if ($db->affected_rows > 0){
      foreach($db->output as $record){
        if (is_null($record->parent_id)){
          $pid = null;
        }
        else{
          $pid = (int) $record->parent_id;
        }
        $id = (int) $record->id;
        $children = $this->list($id);
        $list[] = (object)[
          'id' => $id,
          'parent_id' => $pid,
          'user_id' => (int) $record->user_id,
          'module_id' => (int) $record->module_id,
          'url_code' => $record->url_code,
          'title' => $record->title,
          'app' => $record->app,
          'core' => (int) $record->core,
          'created' => $record->created,
          'activated' => $record->activated,
          'deactivated' => $record->deactivated,
          'children' => $children,
        ];
      }
    }
    return $list;
  }

  /**
   * Writes a path object
   */
  public function write(){
    $db = new Db();

    /** Query object for writing */
    $q = (object)[
      'table' => 'paths',
      'inputs' => (object)[
        'id' => $this->id,
        'parent_id' => $this->parent_id,
        'user_id' => $this->user_id,
        'module_id' => $this->module_id,
        'url_code' => $this->url_code,
        'title' => $this->title,
        'app' => $this->app,
        'core' => $this->core,
        'created' => $this->created,
        'activated' => $this->activated,
        'deactivated' => $this->deactivated,
      ]
    ];

    /** Query object for testing for duplicate keys */
    $t = (object)[
      'table' => 'paths',
      'command' => 'select',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => 'and', 'items' => 
            [
              (object)['type' => '=', 'value' => $this->url_code, 'id' => 'login', 'cs' => false],
              (object)['type' => '=', 'value' => $this->parent_id, 'id' => 'parent_id'],
            ],
          ],
          (object)['type' => '<>', 'value' => $this->id, 'id' => 'id']
        ]
      ]
    ];
    $db->query($t);
    if ($db->affected_rows > 0){
      $this->error = 99;
      $this->message = 'The marked values below are already taken';
      foreach($db->output as $field){
        if($this->url_code == $field->url_code){
          $this->url_unique = false;
          $this->url_message = 'The email "' . $this->url_code . '" is already taken by another path at this level.';
        }
      }
      return;
    }

    if ($this->id >= 0){
      $q->command = 'update';
      $q->where = (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $this->id, 'id' => 'id']
        ]
      ];
      $db->query($q);
      if ($db->error > 0){
        $this->error = $db->error;
        $this->message = $db->message;
      }
      $msg = 'Path successfully updated.';
    }
    elseif ($this->id < 0){
      $q->command = 'insert';
      $q->inputs->created = date('Y-m-d H:i:s');
      $this->created = $q->inputs->created;
      $db->query($q);
      $this->error = $db->error;
      $this->message = $db->message;
      if ($db->error == 0){
        $this->id = (int) $db->insert_id;
        $msg = 'Path successfully created.';
      }
    }
    if (!$this->error){
      // Empty out groups and roles database tables
      $q = (object)[
        'command' => 'delete',
        'table' => 'path_groups',
        'where' => (object) [ '
          type' => 'and', 'items' => [
            (object) ['type' => '=', 'value' => $this->id, 'id' => 'path_id']
          ]
        ]
      ];
      $db->query($q);
      $q->table = 'path_roles';
      $db->query($q);

      // Write the new roles and groups
      foreach ($this->groups as $group){
        $q = (object)[
          'command' => 'insert',
          'table' => 'path_groups',
          'inputs' => (object)['group_id' => $group, 'path_id' => $this->id]
        ];
        $db->query($q);
      }
      foreach ($this->roles as $role){
        $q = (object)[
          'command' => 'insert',
          'table' => 'path_roles',
          'inputs' => (object)['role_id' => $role, 'path_id' => $this->id]
        ];
        $db->query($q);
      }
      $this->message = $msg;
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

    if (count($this->groups) > 0){
      foreach ($this->groups as $group){
        if (in_array($group,$all_groups)){
          $access = true;
        }
      }
    }

    // Check for Role overrides (if unset, means everyone can access!)
    if (count($this->roles) > 0){
      //Reset access to false
      $access = false;
      foreach ($this->roles as $role){
        if (in_array($role,$user->roles)){
          $access = true;
        }
      }
    }
    return $access;
  }

  /**
   * Clears the variables
   *
   */
  public function clear(){
    $this->id = 0;
    $this->parent_id = null;
    $this->user_id = 0;
    $this->module_id = null;
    $this->url_code = null;
    $this->title = null;
    $this->app = null;
    $this->core = 0;
    $this->created = null;
    $this->activated = null;
    $this->deactivated = null;
  }

  /**
   * Deletes the record, then clears the object
   */
  public function delete(){
    if ($this->id > 5){
      $db = new Db();
      $q = (object)[
        'command' => 'delete',
        'table' => 'paths',
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $this->id, 'id' => 'id']
          ]
        ]
      ];
      $db->query($q);
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $this->error = 99;
      $this->message = 'You cannot delete the core path "' . $this->name . '".';
    }
  }
}

