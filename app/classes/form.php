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
  public $method = 'post';   /**< HTTP method the form would use */
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
   *     'method' => 'post',
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
    if (isset($defs->method)){
      $this->method = $defs->method;
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
    $form->method = $this->method;
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
    $html = '';
    if (!is_null($this->title)){
      $html .= '<h3>' . $this->title . "</h3>\n";
    }
    if (!is_null($this->desc)){
      $html .= '<p>' . $this->desc . "</p>\n";
    }
    $html .= '<p>' . $this->message . "</p>\n";
    $html .= '<form action="' . $this->action . '" method="' . $this->method . '" ';
    if (count($this->onstar) > 0){
      foreach ($this->onstar as $key => $value){
        $html .= 'on' . $key . '="' . $value . '" ';
      }
    }
    if (count($this->datadash) > 0){
      foreach ($this->datadash as $key => $value){
        $html .= 'data-' . $key . '="' . $value . '" ';
      }
    }
    if (count($this->classes) > 0){
      $class = implode(' ' , $this->classes);
      $html .= 'class="' . $class . '" ';
    }
    if (count($this->styles) > 0){
      $style = '';
      foreach ($this->styles as $key => $value){
        $style .= $key . ':' . $value . ';';
      }
      $html .= 'style="' . $style . '" ';
    }
    if (count($this->fields) > 0){
      foreach ($this->fields as $f){
        if (!is_null($f->label)){
          $label = '<label for "' . $f->name . '">' . $f->label;
        }
        else{
          $label = '';
        }
        if ($f->error){
          $label .= " <strong>{$f->message}</strong></label>";
          $class = 'invalid ';
        }
        else{
          $label .= "</label>";
          $class = '';
        }
        if ($f->required){
          $class .= 'required';
        }
        else{
          $class .= '';
        }
        if ($f->max_chars > 0){
          $maxlength = "maxlength=\"{$f->max_chars}\"";
        }
        else{
          $maxlength = '';
        }
        if ($f->element == 'button'){
          $html .= "$label<input class=\"{$class}\" type=\"button\" value=\"{$f->value}\" name=\"{$f->name}\" {$maxlength} />\n";
        }
        elseif($f->element == 'text'){
          $html .= "$label<input class=\"{$class}\" type=\"text\" value=\"{$f->value}\" name=\"{$f->name}\" {$maxlength} />\n";
        }
        elseif($f->element == 'textarea'){
          $html .= "$label<textarea class=\"{$class}\" name=\"{$f->name}\" {$maxlength} >{$f->value}</textarea>\n";
        }
        elseif($f->element == 'select' && is_array($f->list) && count($f->list)>0){
          $html .= "$label<select class=\"{$class}\" name=\"{$f->name}\">\n";
          foreach ($f->list as $items){
            $html .= "  <option value=\"{$items['value']}\" >{$items['name']}</option>\n";
          }
          $html .= "</select>\n";
        }
      }
    }
    return $html;
  }
}

