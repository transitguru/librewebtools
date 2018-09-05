<?php

/**
 * coreModule Class
 * 
 * This object keeps track of the modules
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class coreModule{
  public $id = null; /**< Module's ID in the database */
  public $type = 'theme'; /**< Module type ('theme' or 'module') */
  public $core = 1;  /**< Set to 1 if it is a core module, 0 if Custom */
  public $code = 'core'; /**< Name of module's directory */
  public $name = 'Default Core'; /**< Human Readable name of module */
  public $enabled = 1;  /**< Determines if the module is enabled */
  public $required = 1; /**< Determines if a module is required to be enabled */
  public $javascripts = array(); /**< Array of javascripts loaded from modules and themes */
  public $stylesheets = array(); /**< Array of stylesheets loaded from modules and themes */
  /**
   * Construct the theme
   * $param int $id ID for the theme
   */
  public function __construct($id = null){
    if (!is_null($id) && $id > 0){
      $db = new coreDb();
      $db->fetch('modules', null, array('id' => $id));
      if ($db->affected_rows > 0){
        $this->id = $id;
        $this->core = $db->output[0]['core'];
        $this->code = $db->output[0]['code'];
        $this->enabled = $db->output[0]['enabled'];
        $this->name = $db->output[0]['name'];
        $this->type = $db->output[0]['type'];
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
    $this->type = 'theme';
    $this->id = null;
  }
  
  /**
   * Load the theme's template for rendering
   * @param corePage $page Page information to be rendered in the template
   */
  public function loadTheme($page){
    if (is_null($page->page_id) || $page->page_id < 0 || is_null($this->id)){
      $this->core = 1;
      $this->code = 'core';
      $this->type = 'theme';
    }
    if ($this->core == 1 && $this->type == 'theme'){
      $dir = 'core';
    }
    elseif($this->type == 'theme'){
      $dir = 'custom';
    }
    $files = scandir(DOC_ROOT . '/' . $dir . '/themes/' . $this->code);
    foreach ($files as $file){
      if(is_file(DOC_ROOT . '/' . $dir . '/themes/' . $this->code . '/' . $file) && fnmatch('*.js', $file)){
        $this->javascripts[] = "{$dir}/themes/{$this->code}/{$file}";
      }
      elseif(is_file(DOC_ROOT . '/' . $dir . '/themes/' . $this->code . '/' . $file) && fnmatch('*.css', $file)){
        $this->stylesheets[] = "{$dir}/themes/{$this->code}/{$file}";
      }
    }
    $file = DOC_ROOT . '/' . $dir . '/themes/' . $this->code . '/template.php';
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
      $dir = 'core';
    }
    else{
      $core = 0;
      $dir = 'custom';
    }
    $db = new coreDb();
    $db->fetch('modules', null, array('type' =>'module', 'core' => $core), null, array('code'));
    if ($db->affected_rows > 0 ){
      foreach ($db->output as $module){
        $code = $module['code'];
        $PATH = DOC_ROOT . '/' . $dir . '/modules/' . $code;
        if (is_dir($PATH)){
          $files = scandir($PATH);
          foreach ($files as $file){
            if (is_file($PATH . '/' . $file) && fnmatch('*.php', $file)){
              require_once($PATH . '/' . $file);
            }
            elseif(is_file($PATH . '/' . $file) && fnmatch('*.js', $file)){
              $this->javascripts[] = "{$dir}/modules/{$code}/{$file}";
            }
            elseif(is_file($PATH . '/' . $file) && fnmatch('*.css', $file)){
              $this->stylesheets[] = "{$dir}/modules/{$code}/{$file}";
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
    $db = new coreDb();
    $db->fetch('pages', array('url_code', 'parent_id'), array('ajax_call' => 'core_send_scripts'));
    $path = $db->output[0]['url_code']; 
    while ($db->output[0]['parent_id'] != 0){
      $db->fetch('pages', array('url_code', 'parent_id'), array('id' => $db->output[0]['parent_id']));
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
    $db = new coreDb();
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
      $db = new coreDb();
      $db->write_raw("DELETE FROM `modules` WHERE `id`={$this->id}");
      if (!$db->error){
        $this->clear();
      }
      $this->error = $db->error;
      $this->message = $db->message;
    }
  }

}

