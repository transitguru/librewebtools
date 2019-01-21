<?php
namespace LWT;
/**
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

  
  public function __construct($id = 0){
    if ($id>=0){
      $this->table = 'groups';
      $db = new Db();
      $db->fetch($this->table, null, array('id' => $id));
      if ($db->affected_rows == 1){
        $this->id = $db->output[0]['id'];
        $this->parent_id = $db->output[0]['parent_id'];
        $this->name = $db->output[0]['name'];
        $this->sortorder = $db->output[0]['sortorder'];
        $this->created = $db->output[0]['created'];
        $this->desc = $db->output[0]['desc'];
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
    $db = new Db();
    $inputs['name'] = $this->name;
    $inputs['parent_id'] = $this->parent_id;
    $inputs['sortorder'] = $this->sortorder;
    $inputs['desc'] = $this->desc;
    if ($this->id >= 0){
      $db->write($this->table, $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $inputs['created'] = date('Y-m-d H:i:s');
      $db->write($this->table, $inputs); 
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
      $db->write_raw("DELETE FROM `{$this->table}` WHERE `id`={$this->id}");
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }
}
