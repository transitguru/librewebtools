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
  public $path = ''; /**< Unique name of file */
  public $size = 0; /**< Size of file in bytes */
  public $mimetype = ''; /**< Mimetype as stored in the database */
  public $uploaded = ''; /**< Upload date */
  public $title = ''; /**< User applied title to file */
  public $caption = ''; /**< User applied caption to file */

  /**
   * Creates the file
   */
  function __construct($id = -1){
    if ($id>0){
      // Lookup file by ID
      $db = new Db();
      $db->fetch('files', null, array('id' => $id));
      if ($db->affected_rows == 1){
        $this->id = $db->output[0]['id'];
        $this->user_id = $db->output[0]['user_id'];
        $this->basename = $db->output[0]['basename'];
        $this->path = $db->output[0]['path'];
        $this->size = $db->output[0]['size'];
        $this->mimetype = $db->output[0]['mimetype'];
        $this->uploaded = $db->output[0]['uploaded'];
        $this->title = $db->output[0]['title'];
        $this->caption = $db->output[0]['caption'];
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
    $this->user_id = 1;
    $this->basename = '';
    $this->path = '';
    $this->size = 0;
    $this->mimetype = '';
    $this->uploaded = '';
    $this->title = '';
    $this->caption = '';
    $this->error = 0;
    $this->message = '';
  }
  
  /**
   * Writes the data to the database
   *
   */
  public function write(){
    $db = new Db();
    $inputs['user_id'] = $this->user_id;
    $inputs['basename'] = $this->basename;
    $inputs['path'] = $this->path;
    $inputs['size'] = $this->size;
    $inputs['mimetype'] = $this->mimetype;
    $inputs['title'] = $this->title;
    $inputs['caption'] = $this->caption;
    if ($this->id >= 0){
      $db->write('files', $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $inputs['uploaded'] = date('Y-m-d H:i:s');
      $db->write('files', $inputs); 
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
      $db->write_raw("DELETE FROM `files` WHERE `id`={$this->id}");
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }
}

