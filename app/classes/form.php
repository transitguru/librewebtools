<?php
namespace LWT;
/**
 * @file
 * Form Class
 * 
 * Combines user data fields into forms
 *
 * @category Processing and Validation
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Form{
  public $title = null;      /**< Human friendly name for form title */
  public $name = '';         /**< Name for form element */
  public $desc = '';         /**< Text description for form introducing form */
  public $action = '';       /**< Location where form submits */
  public $onstar = [];       /**< Object of on* javascript actions (omit 'on' in key)*/
  public $datadash = [];     /**< Object of data-* attributes (omit 'data-' in key) */
  public $fields = [];       /**< Array of Field objects */
  public $classes = [];      /**< Array of CSS classes */
  public $styles = [];       /**< Object of CSS styles */

  public $message = '';      /**< message to be emitted based on validation */
  public $error = 0;         /**< int error number based on validation */

  /**
   * Initializes new Form
   *
   * @param Object $defs Definitions for the Form as presented in the object below
   *
   * @code
   *   $defs = (object) [
   *     'title' => 'Human-friendly name for field',
   *     'name' => 'form_name_for_html',
   *     'desc' => 'Some text that would be shown to user of form.',
   *     'action' => '/',
   *     'onstar' => (object) [
   *       'blur' => 'somefunction()',
   *       'keyup' => 'someotherfunction()',
   *     ],
   *     'datadash' => (object) [
   *       'lwt-attribute' => 'some_value',
   *       'lwt-maplocation' => 'some_other_value',
   *     ],
   *     'classes' => ['awesome','success'],
   *     'styles' => (object) [
   *       'color' => 'blue',
   *       'font-color' => '#ffffff'
   *     ],
   *     'fields' => [
   *       (object) ['name' => 'element1', 'value' => ''], //See Field object
   *       (object) ['name' => 'element2', 'value' => ''], //See Field object
   *       (object) ['name' => 'element3', 'value' => ''], //See Field object
   *     ],
   *   ];
   * @endcode
   */

  public function __construct($defs){
    $this->update_defs($defs);
  }

  /**
   * Updates definitions for the Form
   *
   * @param Object $defs Definitions for the Form as shown in constructor above
   */
  public function update_defs($defs){
    $this->error = 0;
    $this->message = '';
    if (isset($defs->title)){
      $this->title = $defs->title;
    }
    if (isset($defs->name)){
      $this->name = $defs->name;
    }
    if (isset($defs->desc)){
      $this->desc = $defs->desc;
    }
    if (isset($defs->action)){
      $this->action = $defs->action;
    }
    if (isset($defs->onstar) && is_object($defs->onstar)){
      $this->onstar = $defs->onstar;
    }
    if (isset($defs->datadash) && is_object($defs->datadash)){
      $this->datadash = $defs->datadash;
    }
    if (isset($defs->classes) && is_array($defs->classes)){
      $this->classes = $defs->classes;
    }
    if (isset($defs->styles) && is_object($defs->styles)){
      $this->styles = $defs->styles;
    }
    if (isset($defs->fields) && is_array($defs->fields)){
      $this->fields = [];
      foreach ($defs->fields as $obj){
        $field = new Field($obj);
      }
      if ($field->error == 0){
        $this->fields[] = $field;
      }
      else{
        $this->error = 99;
        $this->message = 'Some fields were not imported properly!';
      }
    }
  }

  /**
   * Batch tests the values in the form for validity
   */
  public function validate(){
    if (isset($this->fields) && is_array($this->fields)){
      $this->error = 0;
      $this->message = '';
      foreach ($this->field as $i => $field){
        $this->field[$i]->validate();
        if ($this->field[$i]->error != 0){
          $this->error = 11;
          $this->message = 'There are some errors, see highlighted fields';
        }
      }
    }
    else{
      $this->error = 10;
      $this->message = 'Nothing to do, no fields available for validation!';
    }
  }

  /**
   * Creates object ready for building HTML, JSON, or whatever
   *
   * @return Object $form data for JSON or HTML conversion
   */
  public function build(){
    $form->title = $this->$title;
    $form->name = $this->name;
    $form->desc = $this->desc;
    $form->action = $this->action;
    if (isset($this->onstar) && is_object($this->onstar)){
      $form->onstar = $this->onstar;
    }
    if (isset($this->datadash) && is_object($this->datadash)){
      $form->datadash = $this->datadash;
    }
    if (isset($this->classes) && is_array($this->classes)){
      $form->classes = $this->classes;
    }
    if (isset($defs->styles) && is_object($this->styles)){
      $this->styles = $this->styles;
    }
    if (isset($this->fields) && is_array($this->fields)){
      $this->error = 0;
      $this->message = '';
      $form->fields = [];
      foreach ($this->field as $field){
        $obj = $field->build();
        $form->fields[] = $obj;
      }
    }
    else{
      $this->error = 90;
      $this->message = 'No fields exist in this form!';
    }
  }

  /**
   * Creates JSON string of Form object
   *
   * @return String $json JSON encoded representation of Form
   */
  public function export_json(){
    $object = $this->build();
    $json = json_encode($object, JSON_UNESCAPED_SLASHES);
    return $json;
  }

  /**
   * Creates HTML string of Form object
   *
   * @return String $html HTML representation of Form
   */
  public function export_html(){

  }
}

