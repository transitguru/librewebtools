<?php
namespace LWT;
/**
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
  
  /**
   * Constructs role based on role ID in database, or creates new empty role
   *
   * @param int $id Optional role ID to lookup in the database, or create new
   */
  public function __construct($id = -1){
    if ($id>=0){
      // Lookup role by ID
      $db = new Db();
      $db->fetch('roles', null, array('id' => $id));
      if ($db->affected_rows == 1){
        $this->id = $db->output[0]['id'];
        $this->name = $db->output[0]['name'];
        $this->sortorder = $db->output[0]['sortorder'];
        $this->created = $db->output[0]['created'];
        $this->desc = $db->output[0]['desc'];
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
    $inputs['name'] = $this->name;
    $inputs['sortorder'] = $this->sortorder;
    $inputs['desc'] = $this->desc;
    if ($this->id >= 0){
      $db->write('roles', $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $inputs['created'] = date('Y-m-d H:i:s');
      $db->write('roles', $inputs); 
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
      $db->write_raw("DELETE FROM `roles` WHERE `id`={$this->id}");
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }

}
