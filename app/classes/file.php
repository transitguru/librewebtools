<?php
namespace LWT;
/**
 * @file
 * File Class
 *
 * This object processes files
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class File{

  public $id = 0; /**< File id as found in the database */
  public $user_id = 1; /**< User ID that uploaded the file */
  public $basename = ''; /**< Basename of original file */
  public $name = ''; /**< Unique name of file */
  public $size = 0; /**< Size of file in bytes */
  public $mimetype = ''; /**< Mimetype as stored in the database */
  public $uploaded = ''; /**< Upload date */
  public $title = ''; /**< User applied title to file */
  public $caption = ''; /**< User applied caption to file */
  public $name_unique = true;       /**< Flag to show if the path name is unique */
  public $name_message = '';        /**< Message for error in path name unique */

  /**
   * Creates the file
   */
  function __construct($id = -1){
    if ($id>0){
      // Lookup file by ID
      $db = new Db();
      $q = (object)[
        'command' => 'select',
        'table' => 'files',
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
        $this->user_id = (int) $db->output[0]->user_id;
        $this->basename = $db->output[0]->basename;
        $this->name = $db->output[0]->name;
        $this->size = (int) $db->output[0]->size;
        $this->mimetype = $db->output[0]->mimetype;
        $this->uploaded = $db->output[0]->uploaded;
        $this->title = $db->output[0]->title;
        $this->caption = $db->output[0]->caption;
        $this->error = 0;
        $this->message = '';
      }
      else{
        $this->clear();
        $this->error = 1;
        $this->message = 'File not found';
      }
    }
    else{
      // Ensure it is empty
      $this->clear();
      $this->id = $id;
    }
  }

  /**
   * Clears the variables
   *
   */
  public function clear(){
    $this->id = 0;
    $this->user_id = 1;
    $this->basename = '';
    $this->name = '';
    $this->size = 0;
    $this->mimetype = '';
    $this->uploaded = '';
    $this->title = '';
    $this->caption = '';
    $this->error = 0;
    $this->message = '';
  }

  /**
   * Lists all files in an array
   *
   * @return array $list All modules as an array of objects
   */
  public function list(){
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'files',
      'fields' => [],
      'sort' => [
        (object) ['id' => 'name'],
      ]
    ];
    $db->query($q);
    $list = [];
    foreach($db->output as $record){
      $list[]= (object)[
        'id' => (int) $record->id,
        'user_id' => (int) $record->user_id,
        'basename' => $record->basename,
        'name' => $record->name,
        'size' => (int) $record->size,
        'mimetype' => $record->mimetype,
        'uploaded' => $record->uploaded,
        'title' => $record->title,
        'caption' => $record->caption,
      ];
    }
    return $list;
  }

  /**
   * Writes the data to the database
   *
   */
  public function write(){
    $db = new Db();
    $q = (object)[
      'table' => 'files',
      'inputs' => (object)[
        'user_id' => $this->user_id,
        'basename' => $this->basename,
        'name' => $this->name,
        'size' => $this->size,
        'mimetype' => $this->mimetype,
        'title' => $this->title,
        'caption' => $this->caption,
      ]
    ];

    /** Query object for testing for duplicate keys */
    $t = (object)[
      'table' => 'files',
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
          $this->name_message = 'The name "' . $this->name . '" is already taken by another module.';
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
      $q->inputs->uploaded = date('Y-m-d H:i:s');
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
        'table' => 'files',
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

