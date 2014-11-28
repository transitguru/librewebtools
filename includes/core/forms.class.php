<?php

/**
 * @file
 * Classes for forms rendering and validation
 *
 *
 */

/**
 * Form object
 */
class Form{
  
}


/**
 * Field object
 *
 */
class Field{
  public $value = '';
  public $message = '';
  public $error = 0;
  private $type;
  private $format;
  private $required;
  private $chars;
  private $notrim;
  private $min = null;
  private $max = null;
  private $step = null;
  private $min_flag = false;
  private $max_flag = false;
  private $step_flag = false;
  
  public function __construct($value, $type, $format, $required=false, $chars=null, $notrim=false){
    $this->value = $value;
    $this->type = $type;
    $this->format = $format;
    $this->required = $required;
    $this->chars = $chars;
    $this->notrim = $notrim;
  }
  public function setType($type){
    $this->type = $type;
  }
  public function setFormat($format){
    $this->format = $format;
  }
  public function setRequired($required){
    if(is_bool($required)){
      $this->required = $required;
    }
  }
  public function setChars($chars){
    if (is_numeric($chars)){
      $this->chars = (int)$chars;
    }
  }
  public function setNotrim($notrim){
    if(is_bool($notrim)){
      $this->notrim = $notrim;
    }
  }
  public function setRange($min=null, $max=null, $step=null){
    if(is_numeric($min)){
      $this->min = $min;
    }
    if(is_numeric($max)){
      $this->max = $max;
    }
    if(is_numeric($step)){
      $this->step = $step;
    }
  }
  public function setBounds($min=null, $max=null, $step=null){
    if(is_bool($min)){
      $this->min = $min;
    }
    if(is_bool($max)){
      $this->max = $max;
    }
    if(is_bool($step)){
      $this->step = $step;
    }
  }
  public function validate(){
    $output = core_validate_inputs($this->value, $this->type, $this->format, $this->required, $this->chars, $this->notrim, array($this->min, $this->max, $this->step), array($this->min_flag, $this->max_flag, $this->step_flag));
    $this->value = $output['value'];
    $this->message = $output['message'];
    $this->error = $output['error'];
  }
  
  public function render($name, $type, $list = array()){
    if ($type == 'button'){
      echo '<input type="button" value="' . $this->value . '" name="' . $name . '"/>';
    }
    elseif($type == 'text'){
      echo '<input type="text" value="' . $this->value . '" name="' . $name . '"/>';
    }
    elseif($type == 'textarea'){
      echo '<textarea name="' . $name . '">'  . $this->value . '</textarea>';    
    }
    elseif($type == 'select' && is_array($list) && count($list)>0){
      echo '<select>';
      foreach ($list as $items){
        echo '<option value="' . $items['value']. '" >' . $items['name'] . '</option>';
      }
      echo '</select>';
    }
  }


}
