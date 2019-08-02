<?php
namespace LWT;
/**
 * @file
 * Group Class
 *
 * displays and modifies group information
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Group extends Tree{
  public $table = 'groups'; /**< Table name in Database */
  public $id=0;             /**< Group ID */
  public $parent_id=null;   /**< Parent ID for group hierarchy*/
  public $name = '';        /**< Role Name */
  public $sortorder = 0;    /**< Sort order, small number "floats to top" */
  public $created = '';     /**< Date that the group was created */
  public $desc = '';        /**< Description */
  public $error = 0;        /**< Error number */
  public $message = '';     /**< Message for error reporting */
  public $name_unique = true;       /**< Flag to show if the name is unique */
  public $parent_id_unique = true;  /**< Flag to show if the parent_id is unique */
  public $name_message = '';        /**< Message for error in name unique */
  public $parent_id_message = '';   /**< Message for error in parent_id unique */


  public function __construct($id = 0){
    if ($id>=0){
      $this->table = 'groups';
      $db = new Db();
      $q = (object)[
        'command' => 'select',
        'table' => $this->table,
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
        if (is_null($db->output[0]->parent_id)){
          $this->parent_id = null;
        }
        else{
          $this->parent_id = (int) $db->output[0]->parent_id;
        }
        $this->name = $db->output[0]->name;
        $this->sortorder = (int) $db->output[0]->sortorder;
        $this->created = $db->output[0]->created;
        $this->desc = $db->output[0]->desc;
        $this->error = 0;
        $this->message = '';
      }
      else{
        $this->clear();
        $this->id = -1;
        $this->error = 1;
        $this->message = 'Group not found';
      }
    }
    else{
      // Ensure it is empty
      $this->clear();
      $this->id = $id;
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
      'table' => 'groups',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $parent_id, 'id' => 'parent_id']
        ]
      ],
      'sort' => [
        (object) ['id' => 'sortorder'],
        (object) ['id' => 'name'],
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
          'sortorder' => (int) $record->sortorder,
          'name' => $record->name,
          'created' => $record->created,
          'desc' => $record->desc,
          'children' => $children,
        ];
      }
    }
    return $list;
  }

  /**
   * Clears the variables
   *
   */
  public function clear(){
    $this->id = 0;
    $this->table = 'groups';
    $this->parent_id = null;
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
    if($this->id === $this->parent_id){
      $this->error = 99;
      $this->parent_id_unique = false;
      $this->parent_id_message = 'The parent cannot equal group (' . $this->name . ').';
      return;
    }
    $db = new Db();
    if ($this->id == 0){
      $this->parent_id = null;
    }
    $q = (object)[
      'table' => $this->table,
      'inputs' => (object)[
        'name' => $this->name,
        'parent_id' => $this->parent_id,
        'sortorder' => $this->sortorder,
        'desc' => $this->desc,
      ],
    ];

    /** Query object for testing for duplicate keys */
    $t = (object)[
      'table' => 'groups',
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
          $this->name_message = 'The name "' . $this->name . '" is already taken by another group.';
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
        $this->id = (int) $db->insert_id;
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
        'table' => $this->table,
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

