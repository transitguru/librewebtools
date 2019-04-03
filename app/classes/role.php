<?php
namespace LWT;
/**
 * @file
 * Role Class
 *
 * allows for loading and editing of role information
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Role{
  public $id = 0;         /**< Role ID (0 is for unauthenticated role)*/
  public $name = '';      /**< Role Name */
  public $sortorder = 0;  /**< Sort order, small number "floats to top" */
  public $created = '';   /**< Date that the role was created */
  public $desc = '';      /**< Description */
  public $error = 0;      /**< Error number */
  public $message = '';   /**< Message for error reporting */
  public $name_unique = true;  /**< Flag to show if the name is unique */
  public $name_message = '';   /**< Message for error in name unique */

  /**
   * Constructs role based on role ID in database, or creates new empty role
   *
   * @param int $id Optional role ID to lookup in the database, or create new
   */
  public function __construct($id = -1){
    if ($id>=0){
      // Lookup role by ID
      $db = new Db();
      $q = (object)[
        'command' => 'select',
        'table' => 'roles',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $id, 'id' => 'id']
          ]
        ]
      ];
      $db->query($q);
      if ($db->affected_rows == 1){
        $this->id = (int) $db->output[0]->id;
        $this->name = $db->output[0]->name;
        $this->sortorder = (int) $db->output[0]->sortorder;
        $this->created = $db->output[0]->created;
        $this->desc = $db->output[0]->desc;
        $this->error = 0;
        $this->message = '';
      }
      else{
        $this->clear();
        $this->error = 1;
        $this->message = 'Role not found';
      }
    }
    else{
      // Ensure it is empty
      $this->clear();
      $this->id = $id;
    }
  }

  /**
   * Lists all roles in an array
   *
   * @return array $list All roles as an array of objects
   */
  public function list(){
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'roles',
      'fields' => [],
    ];
    $db->query($q);
    $list = [];
    foreach($db->output as $record){
      $list[]= (object)[
        'id' => (int) $record->id,
        'sortorder' => (int) $record->sortorder,
        'name' => $record->name,
        'created' => $record->created,
        'desc' => $record->desc,
      ];
    }
    return $list;
  }

  /**
   * Clears the variables
   *
   */
  public function clear(){
    $this->id = 0;
    $this->name = '';
    $this->sortorder = 0;
    $this->created = '';
    $this->desc = '';
    $this->error = 0;
    $this->message = '';
  }

  /**
   * Writes the data to the database
   *
   */
  public function write(){
    $db = new Db();
    $q = (object)[
      'table' => 'roles',
      'inputs' => (object)[
        'name' => $this->name,
        'sortorder' => $this->sortorder,
        'desc' => $this->desc,
      ]
    ];

    /** Query object for testing for duplicate keys */
    $t = (object)[
      'table' => 'roles',
      'command' => 'select',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $this->name, 'id' => 'name', 'cs' => false],
          (object)['type' => '<>', 'value' => $this->id, 'id' => 'id']
        ]
      ]
    ];
    $db->query($t);
    if ($db->affected_rows > 0){
      $this->error = 99;
      $this->message = 'The marked values below are already taken';
      foreach($db->output as $field){
        if($this->name == $field->name){
          $this->name_unique = false;
          $this->name_message = 'The name "' . $this->name . '" is already taken by another role.';
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
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $q->command = 'insert';
      $q->inputs->created = date('Y-m-d H:i:s');
      $db->query($q);
      $this->error = $db->error;
      $this->message = $db->message;
      if (!$db->error){
        $this->id = $db->insert_id;
      }
    }
  }

  /**
   * Deletes the record, then clears the object
   */
  public function delete(){
    if ($this->id >= 0){
      $db = new Db();
      $q = (object)[
        'command' => 'delete',
        'table' => 'roles',
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
  }
}

