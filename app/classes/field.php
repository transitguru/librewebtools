<?php
namespace LWT;
/**
 * @file
 * Field Class
 * 
 * creates, collectes, and validates user inputs for data fields
 *
 * @category Processing and Validation
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Field{
  public $label = null;      /**< Human friendly name for form Label */
  public $name = '';         /**< Name for form element */
  public $element = '';      /**< Type of form element */
  public $list = array();    /**< Array of list items to put in list type elements */
  public $value = '';        /**< value to be validated */
  public $message = '';      /**< message to be emitted based on validation */
  public $error = 0;         /**< int error number based on validation */
  
  private $element_types = ['button','select','text','textarea'];
  private $format_types = [
    'preg',
    'memo',
    'svghtml',
    'html',
    'basicsvg',
    'basichtml',
    'simple',
    'nohtml',
    'text',
    'email',
    'password',
    'oneline',
    'nowacky',
    'int',
    'dec',
    'date',
  ];

  /**
   * Qualitative name of format type
   *
   * 'preg:' test against the regular expression shown after the colon
   * 'date:' test against the date format shown after the colon
   * 'memo' longer, multi-line text with no filtering
   * 'svghtml' Allows nearly all SVG + HTML tags
   * 'html' Allows nearly all HTML tags, but excludes SVG
   * 'basichtml' Allows a few HTML tags and attributes
   * 'simple' Allows no HTML attributes and barely any tags
   * 'nohtml' Does not allow HTML
   * 'text' oneline text with no filtering
   * 'email' format for an email address
   * 'password' No tabs, or any type of return character
   * 'oneline' Same as password
   * 'nowacky' No special characters allowed
   * 'int' Integer numbers only
   * 'dec' Allows both integers and decimal numbers
   *
   */
  private $format;
  private $required = false; /**< Determines if the field is required */
  private $min_chars=0;      /**< Minumum number of characters */
  private $max_chars=0;      /**< Maximum number of characters, zero means no limit */
  private $trim = true;      /**< Determines if automatic trimming is enabled */
  private $min = null;       /**< Minumum numeric value, null if no limit */
  private $max = null;       /**< Maximum numeric value, null if no limit */
  private $step = null;      /**< Minumum "precision", null if no limit */
  private $inc_min = true;   /**< Set to false for 'greater than' */
  private $inc_max = true;   /**< Set to false for 'less than' */
  private $auto_step = true; /**< set to false to throw error instead of 'auto rounding' */
  
  /**
   * Initializes new Field
   *
   * @param Object $defs Definitions for the Field in the form of the object below
   *
   * @code
   *   $defs = (object) [
   *     'label' => 'Human-friendly label for field',
   *     'name' => 'form_name_for_html',
   *     'element' => 'text',
   *     'list' => [
   *       (object) ['name' => 'Pennsylvania', 'value', 'PA'],
   *       (object) ['name' => 'Ohio', 'value', 'OH'],
   *       (object) ['name' => 'West Virginia', 'value', 'WV'],
   *     ],
   *     'value' => 'some_value_to_test',
   *     'format' => 'nowacky',
   *     'required' => false,
   *     'min_chars' => 0,
   *     'max_chars' => 0,
   *     'trim' => true,
   *     'min' => 0,
   *     'max' => 2990,
   *     'step' => 0.5,
   *     'inc_min' => true,
   *     'inc_max' => true,
   *     'auto_step' => true
   *   ];
   * @endcode
   */

  public function __construct($defs){
    $this->update_defs($defs);
  }

  /**
   * Updates definitions for the Field
   *
   * @param Object $defs Definitions for the Field in the form of the object in constructor above
   *
   */
  public function update_defs($defs){
    if (isset($defs->label)){
      $this->label = $defs->label;
    }
    if (isset($defs->name)){
      $this->name = $defs->name;
    }
    if (isset($defs->element) && in_array($defs->element, $this->element_types)){
      $this->element = $defs->element;
    }
    if (isset($defs->list) && is_array($defs->list)){
      $this->list = [];
      foreach($defs->list as $items){
        if (is_object($items) && isset($items->name) && isset($items->value)){
          $this->list[] = $items;
        }
      }
    }
    if (isset($defs->value)){
      $this->value = $defs->value;
    }
    if (isset($defs->format) && is_string($defs->format)){
      $length = mb_strpos($defs->format, ':');
      if ($length >0){
        $test = mb_substr($defs->format,0,$length);
      }
      else{
        $test = $defs->format;
      }
      if (in_array($test, $this->format_types)){
        $this->format = $defs->format;
      }
    }
    if (isset($defs->required) && is_bool($defs->required)){
      $this->required = $defs->required;
    }
    if (isset($defs->min_chars) && is_int($defs->min_chars)){
      $this->min_chars = $defs->min_chars;
    }
    if (isset($defs->max_chars) && is_int($defs->max_chars)){
      $this->max_chars = $defs->max_chars;
    }
    if (isset($defs->trim) && is_bool($defs->trim)){
      $this->trim = $defs->trim;
    }
    if (isset($defs->min) && is_numeric($defs->min)){
      $this->min = $defs->min;
    }
    if (isset($defs->max) && is_numeric($defs->max)){
      $this->max = $defs->max;
    }
    if (isset($defs->step) && is_numeric($defs->step)){
      $this->step = $defs->step;
    }
    if (isset($defs->inc_min) && is_bool($defs->inc_min)){
      $this->inc_min = $defs->inc_min;
    }
    if (isset($defs->inc_max) && is_bool($defs->inc_max)){
      $this->inc_max = $defs->inc_max;
    }
    if (isset($defs->auto_step) && is_bool($defs->auto_step)){
      $this->auto_step = $defs->auto_step;
    }
  }


  /**
   * Tests the value for validity for database import and application safety
   * 
   * Error Numbers:
   *  0 = no error
   * 11 = Empty value
   * 12 = String too long
   * 13 = String too short
   * 21 = Does not match regex
   * 41 = Line breaks/tabs in password
   * 42 = Line breaks in oneline input
   * 43 = Invalid email address
   * 44 = Special characters in input
   * 51 = Cannot make time with date format
   * 52 = Date format good, but date itself is invalid
   * 61 = Not an integer
   * 62 = Not a number
   * 63 = Value less than or equal to minimum
   * 64 = Value less than minimum
   * 65 = Value greater than or equal to maximum
   * 66 = Value greater than maximum
   * 67 = Value does not match resolution (too precise)
   */  
  public function validate(){
    //handle trimming
    if ($this->trim){
      $this->value = trim($this->value);
    }
    
    //Handle empty inputs
    if ($this->required && $this->value === ''){
      $this->error = 11;
      $this->message = 'Required: Please enter a value.';
      return;
    }
    elseif ($this->value === ''){
      $this->error = 0;
      $this->message = "";
      return;
    }
    
    //Handle too many characters
    if ($this->max_chars > 0 && mb_strlen($this->value) > $this->max_chars){
      $this->error = 12;
      $this->message = "Invalid: Please enter a value with no more than {$this->max_chars} characters";
      return;
    }
    // Handle too few characters
    if ($this->min_chars > 0 && ($this->max_chars >= $this->min_chars || $this->max_chars == 0) && mb_strlen($this->value) < $this->min_chars){
      $this->error = 13;
      $this->message = "Invalid: Please enter a value with no less than {$this->min_chars} characters";
      return;
    }
    
    //Formats that have a colon separator
    $length = mb_strpos($this->format, ':');
    if ($length >0){
      $type = mb_substr($this->format, 0, $length);
      $format = mb_substr($this->format, $length+1);

      //Regular expression
      if ($type == 'preg'){
        $matches = array();
        preg_match($format, $this->value, $matches);
        if (count($matches)==0 || $matches[0] != $this->value){
          $this->error = 21;
          $this->message = 'Invalid: Value does not match the pattern expected.';
          return;
        }
      }

      //Check time against $format (which uses PHP date() format string)
      elseif ($type == 'date'){
        if(date_create_from_format($format, $this->value)){
          $date = date_create_from_format($format, $this->value);
          $formatted = date_format($date, $format);
          if ($formatted != $this->value){
            $this->error = 52;
            $this->message = 'Invalid: Value is not a valid date.';
            return;
          }
        }
        else{
          $this->error = 51;
          $this->message = 'Invalid: Date format is wrong.';
          return;
        }
      }

      //Format was wrong
      else{
        $format = $type;
      }
    }
    else{
      $format = $this->format;
    }
    $qualifier = '';

    //Check for Memo Types
    if ($format == 'memo'){
      // Allow everything (this is dangerous, unless this is HTML encoded somewhere else)
      
    }
    elseif($format =='svghtml'){
      // Allow most HTML and SVG, but no scripts
      $qualifier = 'html+svg';
    }
    elseif($format=='html'){
      // Allow most HTML, but no SVG and no scripts
      $qualifier = 'html';
    }
    elseif($format=='basicsvg'){
      // Allow basic HTML/SVG, but no raster images
      $qualifier = 'basic+svg';
    }
    elseif($format=='basichtml'){
      // Allow basic HTML, no images, no SVG
      $qualifier = 'basic';
    }
    elseif($format=='simple'){
      // Allow the nearly no tags, and definitely no links or styling
      $qualifier = 'simple';
    }
    elseif($format=='nohtml'){
      $this->value = htmlspecialchars($this->value);
    }
    
    // Check for text types
    elseif ($format=='password'){
      //Allow nearly everything for a oneline password
      if(fnmatch("*\t*",$this->value) || fnmatch("*\r*",$this->value) || fnmatch("*\n*",$this->value)){
        $this->error = 41;
        $this->message = 'Invalid: Please remove line breaks (hard returns) or tabs.';
        return;
      }
    }
    elseif($format=='oneline' || $format=='text'){
      //$input = core_validate_descript($input);
      //Allow only one line text, do not allow CR or LF
      if(fnmatch("*\r*",$this->value) || fnmatch("*\n*",$this->value)){
        $this->error = 42;
        $this->message = 'Invalid: Please remove line breaks (hard returns).';
        return;
      }
    }
    elseif($format=='email'){
      //Email formatting only
      preg_match('/([\w\-\.%+-]+\@[\w\-]+\.[\w\-]+)/',$this->value, $matches);
      if (count($matches)==0 || $matches[0] != $this->value){
        $this->error = 43;
        $this->message = 'Invalid: Not a valid email address.';
        return;
      }
    }
    elseif($format=='nowacky'){
      //No special characters
      preg_match('/[\w-]*/', $this->value, $matches);
      if (count($matches)==0 || $matches[0] != $this->value){
        $this->error = 44;
        $this->message = 'Invalid: Special characters exist, please only use numbers, letters, hyphens, and underscores.';
        return;
      }
    }

    // Check for Numeric types
    elseif ($format == 'num' || $format == 'int'){
      if ($format=='int'){
        //Integer Numbers
        $this->value = str_replace(',','',$this->value);
        $this->value = str_replace(' ','',$this->value);
        if (!is_numeric($this->value) || fmod($this->value,1) != 0){
          $this->error = 61;
          $this->message = 'Invalid: Value is not an integer.';
          return;
        }
      }
      elseif($format=='dec'){
        //Decimal Numbers
        $this->value = str_replace(',','',$this->value);
        $this->value = str_replace(" ","",$this->value);
        if (!is_numeric($this->value)){
          $this->error = 62;
          $this->message = 'Invalid: Value is not a number.';
          return;
        }
      }
      
      
      //swap min and max if they are transposed
      if (!is_null($this->max) && !is_null($this->min) && $this->min > $this->max){
        $temp = $this->max;
        $this->max = $this->min;
        $this->min = $temp;
      }
      if (!$this->inc_min && !is_null($this->min) && $this->value <= $this->min){
        $this->error = 63;
        $this->message = "Out of Range: Must be greater than {$this->min}.";
        return;
      }
      elseif(!is_null($this->min) && $this->value < $this->min){
        $this->error = 64;
        $this->message = "Out of Range: Must be at least {$this->min}.";
        return;
      }
      if (!$this->inc_max && !is_null($this->max) && $this->value >= $this->max){
        $this->error = 65;
        $this->message = "Out of Range: Must be less than {$this->max}.";
        return;
      }
      elseif(!is_null($this->max) && $this->value > $this->max){
        $this->error = 66;
        $this->message = "Out of Range: Must at most {$this->max}.";
        return;
      }
      
      //Check for step (or autoround)
      if (!is_null($this->step)){
        if (!$this->auto_step && fmod($this->value - $this->min , $this->step) != 0 && $this->value != $this->max){
          $this->error = 67;
          $this->message = "Too precise: Must be a value from {$this->min} every {$this->step}.";
          return;
        }
        elseif(fmod($this->value - $this->min , $this->step) != 0 && $this->value != $this->max){
          $offset = $this->value - $this->min;
          $steps = $offset/$this->step;
          if (fmod($steps, 1) < 0.5){
            $this->value = floor($steps) * $this->step + $this->min;
          }
          else{
            $this->value = ceil($steps) * $this->step + $this->min;
          }
        }
      }
    }
    
    //Scrub HTML if requested from a format above
    if (!is_null($qualifier) && $qualifier != ''){
      $xml = new Xml($this->value, $qualifier);
      $xml->scrub();
      $this->value = $xml->markup;
    }

    //successful output
    $this->error = 0;
    $this->message = '';
    return;
  }
  
  /**
   * Rendering function (maybe moved to a form object)
   */
  public function render(){
    if (!is_null($this->label)){
      $label = '<label for "' . $this->name . '">' . $this->label;
    }
    else{
      $label = '';
    }
    if ($this->error){
      $label .= " <strong>{$this->message}</strong></label>";
      $class = 'invalid ';
    }
    else{
      $label .= "</label>";
      $class = '';
    }
    if ($this->required){
      $class .= 'required';
    }
    else{
      $class .= '';
    }
    if ($this->max_chars > 0){
      $maxlength = "maxlength=\"{$this->max_chars}\"";
    }
    else{
      $maxlength = '';
    }
    if ($this->element == 'button'){
      echo "$label<input class=\"{$class}\" type=\"button\" value=\"{$this->value}\" name=\"{$this->name}\" {$maxlength} />\n";
    }
    elseif($this->element == 'text'){
      echo "$label<input class=\"{$class}\" type=\"text\" value=\"{$this->value}\" name=\"{$this->name}\" {$maxlength} />\n";
    }
    elseif($this->element == 'textarea'){
      echo "$label<textarea class=\"{$class}\" name=\"{$this->name}\" {$maxlength} >{$this->value}</textarea>\n";    
    }
    elseif($this->element == 'select' && is_array($this->list) && count($this->list)>0){
      echo "$label<select class=\"{$class}\" name=\"{$this->name}\">\n";
      foreach ($this->list as $items){
        echo "  <option value=\"{$items['value']}\" >{$items['name']}</option>\n";
      }
      echo "</select>\n";
    }
  }
}

