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
  public $user_input = array(); /**< Object of user input (URI, POST, FILES, GET) */
  public $session = array(); /**< Object of user session */

  /**
   * Construct the theme
   * @param int $id ID for the theme
   */
  public function __construct($id = null, $user_input = array(), $session = array()){
    if (!is_null($id) && $id > 0){
      $db = new Db();
      $q = (object)[
        'command' => 'select',
        'table' => 'modules',
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $id, 'id' => 'id']
          ]
        ]
      ];
      $this->user_input = $user_input;
      $this->session = $session;
      $db->query($q);
      if ($db->affected_rows > 0){
        $this->id = (int) $id;
        $this->core = (int) $db->output[0]->core;
        $this->code = $db->output[0]->code;
        $this->enabled = (int) $db->output[0]->enabled;
        $this->name = $db->output[0]->name;
        $this->required = (int) $db->output[0]->required;
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
    $this->loadMods(1);
    $this->loadMods(0);
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
    $file = DOC_ROOT . '/app/' . $dir . '/' . $this->code . '/template.php';
    $sub_app = new \LWT\Subapp($path, $this->user_input, $this->session);
    if (!is_null($path->app) && $path->app !== '' && class_exists($path->app)){
      $sub_app = new $path->app($path, $this->user_input, $this->session);
    }

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
    $q = (object)[
      'command' => 'select',
      'table' => 'modules',
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $core, 'id' => 'core']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0 ){
      foreach ($db->output as $module){
        $code = $module->code;
        $PATH = DOC_ROOT . '/app/' . $dir . '/' . $code;
        $include = $PATH . '/bootstrap.php';
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
        if (is_file($include)){
          require_once ($include);
        }
      }
    }
  }

  /**
   * Load all Javascripts and CSS in the template
   */
  public function loadScripts(){
    $path = Path::findapp('\\LWT\\Modules\\Init\\Script');
    if (is_string($path)){
      if (count($this->javascripts)>0){
        foreach ($this->javascripts as $script){
          echo '    <script type="application/javascript" src="' . BASE_URI;
          echo '/' . $path . '/' . $script . '"></script>' . "\n";
        }
      }
      if (count($this->stylesheets)>0){
        foreach ($this->stylesheets as $sheet){
          echo '    <link rel="stylesheet" type="text/css" href="' . BASE_URI;
          echo '/' . $path . '/' . $sheet . '" />' . "\n";
        }
      }
    }
  }

  /**
   * Writes the data to the database
   */
  public function write(){
    $db = new Db();
    $q = (object)[
      'inputs' => (object)[
        'core' => $this->core,
        'code' => $this->code,
        'name' => $this->name,
        'type' => $this->type,
        'enabled' => $this->enabled,
        'required' => $this->required,
      ]
    ];
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
        'table' => 'modules',
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)[ 'type' => '=', 'value' => $this->id, 'id' => 'id']
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

