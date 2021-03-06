<?php
namespace LWT;
/**
 * @file
 * Field Class
 *
 * Creates, collects, and validates user inputs for data fields
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
  public $autocomplete= 'on';/**< Autocomplete behavior on form element */
  public $autofocus = false; /**< Whether to allow autofocus on form element */
  public $disabled = false;  /**< Disables an element */
  public $onstar = [];       /**< Object of on* javascript actions (omit 'on' in key)*/
  public $datadash = [];     /**< Object of data-* attributes (omit 'data-' in key) */
  public $classes = [];      /**< Array of CSS classes */
  public $styles = [];       /**< Object of CSS styles */
  public $tabindex = 0;      /**< If non-zero, special rules for tab index */
  public $list = [];         /**< Array of list items to put in list type elements */
  public $default = null;    /**< Default value if used as a simple checkbox */
  public $value = null;      /**< value to be validated */
  public $multiple = false;  /**< set to true to accept an array of values */

  /**
   * Valid format types that can be referenced in a Field object
   */
  private $format_types = [
    'preg',       /**< Test against regular expression shown after the colon */
    'date',       /**< test against the date format shown after the colon */
    'memo',       /**< longer, multi-line text with no filtering */
    'svghtml',    /**< Allows nearly all SVG + HTML tags */
    'html',       /**< Allows nearly all HTML tags, but excludes SVG */
    'basichtml',  /**< Allows a few HTML tags and attributes */
    'simple',     /**< Allows no HTML attributes and barely any tags */
    'nohtml',     /**< Does not allow HTML */
    'text',       /**< oneline text with no filtering */
    'email',      /**< format for an email address */
    'password',   /**< No tabs, or any type of return character */
    'oneline',    /**< No return characters (tabs are allowed) */
    'nowacky',    /**< No special characters allowed */
    'int',        /**< Integer numbers only */
    'dec',        /**< Allows both integers and decimal numbers */
  ];

  public $format;           /**< Qualitative name of format (see format_types) */
  public $required = false; /**< Determines if the field is required */
  public $min_chars=0;      /**< Minumum number of characters */
  public $max_chars=0;      /**< Maximum number of characters, zero means no limit */
  public $trim = true;      /**< Determines if automatic trimming is enabled */
  public $min = null;       /**< Minumum numeric value, null if no limit */
  public $max = null;       /**< Maximum numeric value, null if no limit */
  public $step = null;      /**< Minumum "precision", null if no limit */
  public $inc_min = true;   /**< Set to false for 'greater than' */
  public $inc_max = true;   /**< Set to false for 'less than' */
  public $auto_step = true; /**< set to false to throw error instead of 'auto rounding' */

  public $message = '';      /**< message to be emitted based on validation */
  public $error = 0;         /**< int error number based on validation */

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
   *     'autocomplete' => 'on',
   *     'autofocus' => false,
   *     'disabled' => false,
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
   *     'tabindex' => 0,
   *     'list' => [
   *       (object) ['name' => 'Pennsylvania', 'value' => 'PA'],
   *       (object) ['name' => 'Ohio', 'value' => 'OH'],
   *       (object) ['name' => 'West Virginia', 'value' => 'WV'],
   *     ],
   *     'default' => 'some_default_for_checkbox',
   *     'value' => 'some_value_to_test',
   *     'multiple' => false,
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
   */
  public function update_defs($defs){
    $this->error = 0;
    $this->message = '';
    if (isset($defs->label)){
      $this->label = $defs->label;
    }
    if (isset($defs->name)){
      $this->name = $defs->name;
    }
    if (isset($defs->element)){
      $this->element = $defs->element;
    }
    if (isset($defs->autocomplete)){
      $this->autocomplete = $defs->autocomplete;
    }
    if (isset($defs->autofocus)){
      $this->autofocus = $defs->autofocus;
    }
    if (isset($defs->disabled)){
      $this->disabled = $defs->disabled;
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
    if (isset($defs->list) && is_array($defs->list)){
      $this->list = [];
      foreach($defs->list as $items){
        if (is_object($items) && isset($items->name)){
          $this->list[] = $items;
        }
      }
    }
    if (isset($defs->default)){
      $this->default = $defs->default;
    }
    if (isset($defs->value)){
      $this->value = $defs->value;
    }
    if (isset($defs->multiple) && $defs->multiple === true){
      $this->multiple = true;
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
   *  1 = Object or Array found, single value expected
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
    if ($this->multiple === true){
      $values = [];
      if (is_array($this->value) && count($this->value)>0){
        foreach($this->value as $value){
          $values[] = $this->check($value);
        }
      }
      $this->value = $values;
    }
    elseif(!is_array($this->value) && !is_object($this->value)){
      $this->value = $this->check($this->value);
    }
    else{
      $this->error = 1;
      $this->message = 'Invalid: Too many values, please only use one value';
    }
  }

  /**
   * Helper to check values of fields
   *
   * @param mixed $value Value being checked
   */
  private function check($value){
    //Individual values cannot be objects or arrays (yet)
    if(is_array($value) || is_object($value)){
      $this->error = 2;
      $this->message = 'Invalid: Cannot have a list within a list.';
      return $value;
    }

    //handle trimming
    if ($this->trim && !is_null($value)){
      $value = trim($value);
    }

    //Handle empty inputs
    if ($value === '' || $value == null){
      if ($this->required){
        $this->error = 11;
        $this->message = 'Required: Please enter a value.';
        return $value;
      }
      else{
        $this->error = 0;
        $this->message = "";
        return null;
      }
    }

    //Handle too many characters
    if ($this->max_chars > 0 && mb_strlen($value) > $this->max_chars){
      $this->error = 12;
      $this->message = "Invalid: Please enter a value with no more than {$this->max_chars} characters";
      return $value;
    }
    // Handle too few characters
    if ($this->min_chars > 0 && mb_strlen($value) < $this->min_chars){
      $this->error = 13;
      $this->message = "Invalid: Please enter a value with no less than {$this->min_chars} characters";
      return $value;
    }

    //Formats that have a colon separator
    $length = mb_strpos($this->format, ':');
    if ($length >0){
      $type = mb_substr($this->format, 0, $length);
      $format = mb_substr($this->format, $length+1);

      //Regular expression
      if ($type == 'preg'){
        $matches = [];
        preg_match($format, $value, $matches);
        if (count($matches)==0 || $matches[0] != $value){
          $this->error = 21;
          $this->message = 'Invalid: Value does not match the pattern expected.';
          return $value;
        }
      }

      //Check time against $format (which uses PHP date() format string)
      elseif ($type == 'date'){
        if(date_create_from_format($format, $value)){
          $date = date_create_from_format($format, $value);
          $formatted = date_format($date, $format);
          if ($formatted != $value){
            $this->error = 52;
            $this->message = 'Invalid: Value is not a valid date.';
            return $value;
          }
        }
        else{
          $this->error = 51;
          $this->message = 'Invalid: Date format is wrong.';
          return $value;
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
      $value = htmlspecialchars($value);
    }

    // Check for text types
    elseif ($format=='password'){
      //Allow nearly everything for a oneline password
      if(fnmatch("*\t*",$value) || fnmatch("*\r*",$value) || fnmatch("*\n*",$value)){
        $this->error = 41;
        $this->message = 'Invalid: Please remove line breaks (hard returns) or tabs.';
        return $value;
      }
    }
    elseif($format=='oneline' || $format=='text'){
      //Allow only one line text, do not allow CR or LF
      if(fnmatch("*\r*",$value) || fnmatch("*\n*",$value)){
        $this->error = 42;
        $this->message = 'Invalid: Please remove line breaks (hard returns).';
        return $value;
      }
    }
    elseif($format=='email'){
      //Email formatting only
      preg_match('/([\w\-\.%+-]+\@[\w\-]+\.[\w\-]+)/',$value, $matches);
      if (count($matches)==0 || $matches[0] != $value){
        $this->error = 43;
        $this->message = 'Invalid: Not a valid email address.';
        return $value;
      }
    }
    elseif($format=='nowacky'){
      //No special characters
      preg_match('/[\w-]*/', $value, $matches);
      if (count($matches)==0 || $matches[0] != $value){
        $this->error = 44;
        $this->message = 'Invalid: Special characters exist, please only use numbers, letters, hyphens, and underscores.';
        return $value;
      }
    }

    // Check for Numeric types
    elseif ($format == 'dec' || $format == 'int'){
      if ($format=='int'){
        //Integer Numbers
        $value = str_replace(',','',$value);
        $value = str_replace(' ','',$value);
        if (!is_numeric($value) || fmod($value,1) != 0){
          $this->error = 61;
          $this->message = 'Invalid: Value is not an integer.';
          $value;
        }
      }
      elseif($format=='dec'){
        //Decimal Numbers
        $value = str_replace(',','',$value);
        $value = str_replace(" ","",$value);
        if (!is_numeric($value)){
          $this->error = 62;
          $this->message = 'Invalid: Value is not a number.';
          return $value;
        }
      }


      //swap min and max if they are transposed
      if (!is_null($this->max) && !is_null($this->min) && $this->min > $this->max){
        $temp = $this->max;
        $this->max = $this->min;
        $this->min = $temp;
      }
      if (!$this->inc_min && !is_null($this->min) && $value <= $this->min){
        $this->error = 63;
        $this->message = "Out of Range: Must be greater than {$this->min}.";
        return $value;
      }
      elseif(!is_null($this->min) && $value < $this->min){
        $this->error = 64;
        $this->message = "Out of Range: Must be at least {$this->min}.";
        return $value;
      }
      if (!$this->inc_max && !is_null($this->max) && $value >= $this->max){
        $this->error = 65;
        $this->message = "Out of Range: Must be less than {$this->max}.";
        return $value;
      }
      elseif(!is_null($this->max) && $value > $this->max){
        $this->error = 66;
        $this->message = "Out of Range: Must at most {$this->max}.";
        return $value;
      }

      //Check for step (or autoround)
      if (!is_null($this->step)){
        if (!$this->auto_step && fmod($value - $this->min , $this->step) != 0 && $value != $this->max){
          $this->error = 67;
          $this->message = "Too precise: Must be a value from {$this->min} every {$this->step}.";
          return $value;
        }
        elseif(fmod($value - $this->min , $this->step) != 0 && $value != $this->max){
          $offset = $value - $this->min;
          $steps = $offset/$this->step;
          if (fmod($steps, 1) < 0.5){
            $value = floor($steps) * $this->step + $this->min;
          }
          else{
            $value = ceil($steps) * $this->step + $this->min;
          }
        }
      }
    }

    //Scrub HTML if requested from a format above
    if (!is_null($qualifier) && $qualifier != ''){
      $xml = new Xml($value, $qualifier);
      $xml->scrub();
      $value = $xml->markup;
    }

    //successful output
    $this->error = 0;
    $this->message = '';
    if ($format=='int' && !is_null($value)){
      return (int) $value;
    }
    elseif ($format=='dec' && !is_null($value)){
      return (double) $value;
    }
    else{
      return $value;
    }
  }

  /**
   * Builds field object to put into Form object for later JSON or HTML output
   *
   * @return Object $form data for later JSON or HTML conversion in Form object
   */
  public function build(){
    $form = (object)[];
    $form->label = $this->label;
    $form->name = $this->name;
    $form->element = $this->element;
    $form->autocomplete = $this->autocomplete;
    if ($this->autofocus == true){
      $form->autofocus = true;
    }
    if ($this->disabled == true){
      $form->disabled = true;
    }
    if ($this->tabindex != 0){
      $form->tabindex = $this->tabindex;
    }
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
    if (count($this->list)>0){
      $form->list = $this->list;
    }
    $form->default = $this->default;
    $form->value = $this->value;
    $form->multiple = $this->multiple;
    $form->message = $this->message;
    $form->error = $this->error;
    $form->format = $this->format;
    if ($this->required == true){
      $form->required = true;
    }
    if ($this->min_chars > 0){
      $form->min_chars = $this->min_chars;
    }
    if ($this->max_chars > 0){
      $form->max_chars = $this->max_chars;
    }
    $form->trim = $this->trim;
    if ($this->min > 0){
      $form->min = $this->min;
    }
    if ($this->max > 0){
      $form->max = $this->max;
    }
    if ($this->step !== null){
      $form->step = $this->step;
    }
    $form->inc_min = $this->inc_min;
    $form->inc_max = $this->inc_max;
    $form->auto_step = $this->auto_step;

    return $form;
  }
}

