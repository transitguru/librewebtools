<?php

/**
 * coreTheme Class
 * 
 * This object renders the approprate theme template for the page chosen
 *
 * @category Request Handling
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreTheme{
  public $id = null;
  public $core = 1;
  public $code = 'core';
  public $name = 'Default Core';
  public $enabled = 1;
  /**
   * Construct the theme
   * $param int $id ID for the theme
   */
  public function __construct($id = null){
    if (!is_null($id) && $id > 0){
      $db = new coreDb(DB_NAME);
      $db->fetch('themes', null, array('id' => $id));
      if ($db->affected_rows > 0){
        $this->id = $id;
        $this->core = $db->output[0]['core'];
        $this->code = $db->output[0]['code'];
        $this->enabled = $db->output[0]['enabled'];
        $this->name = $db->output[0]['name'];
      }
      else{
        $this->id = null;
      }
    }
    elseif(!is_null($id) && $id > 0){
      $this->clear();
      $this->id = $id;
    }
    else{
      $this->clear();
    }
  }
  
  /**
   * Clears values to defaults (for writing)
   */
  public function clear(){
    $this->core = 0;
    $this->code = '';
    $this->name = '';
    $this->enabled = 1;
    $this->id = null;
  }
  
  /**
   * Load the theme's template for rendering
   * @param corePage $page Page information to be rendered in the template
   */
  public function template($page){
    if ($this->core == 1){
      $dir = 'core';
    }
    else{
      $dir = 'custom';
    }
    require_once (DOC_ROOT . '/' . $dir . '/themes/' . $this->code . '/template.php');
  }	

  /**
   * Writes the data to the database
   *
   */
  public function write(){
    $db = new coreDb(DB_NAME);
    $inputs['core'] = $this->core;
    $inputs['code'] = $this->code;
    $inputs['name'] = $this->name;
    $inputs['enabled'] = $this->enabled;
    if ($this->id >= 0){
      $db->write('themes', $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $db->write('themes', $inputs); 
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
      $db = new coreDb(DB_NAME);
      $db->write_raw("DELETE FROM `roles` WHERE `id`={$this->id}");
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }

}

