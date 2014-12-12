<?php

/**
 * coreFile Class
 * 
 * This object processes files
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreFile{

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
      $db = new coreDb();
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
   * Processes File Downloads
   */
  public function download(){
    // Stop output buffering
    ob_clean();
    
    // Don't Cache the result
    header('Cache-Control: no-cache');
    
    //This is the only information that gets sent back!
    $included = $_SERVER['DOCUMENT_ROOT']."/files/core/".$this->path;
    $size = filesize($included);
    $type = mime_content_type($included);
    header('Pragma: ');         // leave blank to avoid IE errors
    header('Cache-Control: ');  // leave blank to avoid IE errors
    header('Content-Length: ' . $size);
    // This next line forces a download so you don't have to right click...
    header('Content-Disposition: attachment; filename="'.basename($included).'"');
    header('Content-Type: ' .$type);
    sleep(0); // gives browser a second to digest headers
    readfile($included);
    exit;
  }

  /**
   * Writes the data to the database
   *
   */
  public function write(){
    $db = new coreDb();
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
      $db = new coreDb();
      $db->write_raw("DELETE FROM `files` WHERE `id`={$this->id}");
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }

}
