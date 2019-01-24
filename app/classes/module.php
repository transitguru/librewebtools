<?php
namespace LWT;
/**
 * @file
 * Module Class
 * 
 * This object keeps track of the modules
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Module{
  public $id = null; /**< Module's ID in the database */
  public $core = 1;  /**< Set to 1 if it is a core module, 0 if Custom */
  public $code = 'init'; /**< Name of module's directory */
  public $name = 'Init'; /**< Human Readable name of module */
  public $enabled = 1;  /**< Determines if the module is enabled */
  public $required = 1; /**< Determines if a module is required to be enabled */
  public $javascripts = array(); /**< Array of javascripts loaded from modules and themes */
  public $stylesheets = array(); /**< Array of stylesheets loaded from modules and themes */
  public $user_input = (object)[]; /**< Object of user input (POST, FILES, GET) */
  /**
   * Construct the theme
   * @param int $id ID for the theme
   */
  public function __construct($id = null, $user_input = array()){
    if (!is_null($id) && $id > 0){
      $db = new Db();
      $this->user_input = $user_input;
      $db->fetch('modules', null, array('id' => $id));
      if ($db->affected_rows > 0){
        $this->id = $id;
        $this->core = $db->output[0]['core'];
        $this->code = $db->output[0]['code'];
        $this->enabled = $db->output[0]['enabled'];
        $this->name = $db->output[0]['name'];
        $this->required = $db->output[0]['required'];
      }
      else{
        $this->id = null;
      }
    }
    elseif(!is_null($id) && $id < 0){
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
    $this->required = 1;
    $this->id = null;
    $this->user_input = (object)[];
  }
  
  /**
   * Load the module's template and related code for rendering
   * @param Path $path Path information to be rendered in the template
   */
  public function loadTemplate($path){
    if (is_null($path->path_id) || $path->path_id < 0 || is_null($this->id)){
      $this->core = 1;
      $this->code = 'init';
    }
    if ($this->core == 1){
      $dir = 'modules';
    }
    else{
      $dir = 'custom';
    }
    $file = $PATH . '/template.php';
    if (is_file($file)){
      require_once ($file);
    }
  }
  
  /**
   * Load all enabled modules
   * @param int $core Whether loading core (1) or custom (0) modules
   */
  public function loadMods($core = 1){
    if ($core == 1){
      $dir = 'modules';
    }
    else{
      $core = 0;
      $dir = 'custom';
    }
    $db = new Db();
    $db->fetch('modules', null, array('core' => $core), null, array('code'));
    if ($db->affected_rows > 0 ){
      foreach ($db->output as $module){
        $code = $module['code'];
        $PATH = DOC_ROOT . '/app/' . $dir . '/' . $code;
        $file = $PATH . '/bootstrap.php';
        if (is_file($file)){
          require_once ($file);
        }
        if (is_dir($PATH)){
          $files = scandir($PATH);
          foreach ($files as $file){
            if(is_file($PATH . '/' . $file) && fnmatch('*.js', $file)){
              $this->javascripts[] = "{$dir}/{$code}/{$file}";
            }
            elseif(is_file($PATH . '/' . $file) && fnmatch('*.css', $file)){
              $this->stylesheets[] = "{$dir}/{$code}/{$file}";
            }
          }
        }
      }
    }
  }
  
  /**
   * Load all Javascripts and CSS in the template
   */
  public function loadScripts(){
    $db = new Db();
    $db->fetch('paths', array('url_code', 'parent_id'), array('ajax_call' => 'core_send_scripts'));
    $path = $db->output[0]['url_code']; 
    while ($db->output[0]['parent_id'] != 0){
      $db->fetch('paths', array('url_code', 'parent_id'), array('id' => $db->output[0]['parent_id']));
      $path = $db->output[0]['url_code'] . '/' . $path; 
    }
    if (count($this->javascripts)>0){
      foreach ($this->javascripts as $script){
        echo "    <script type=\"application/javascript\" src=\"/{$path}/{$script}\"></script>\n";
      }
    }
    if (count($this->stylesheets)>0){
      foreach ($this->stylesheets as $sheet){
        echo "    <link rel=\"stylesheet\" type=\"text/css\" href=\"/{$path}/{$sheet}\" />\n";
      }
    }
  }

  /**
   * Writes the data to the database
   *
   */
  public function write(){
    $db = new Db();
    $inputs['core'] = $this->core;
    $inputs['code'] = $this->code;
    $inputs['name'] = $this->name;
    $inputs['type'] = $this->type;
    $inputs['enabled'] = $this->enabled;
    $inputs['required'] = $this->required;
    if ($this->id >= 0){
      $db->write('modules', $inputs, array('id' => $this->id));
      $this->error = $db->error;
      $this->message = $db->message;
    }
    else{
      $db->write('modules', $inputs); 
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
      $db->delete('modules', ['id' => $this->id]);
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }
}

