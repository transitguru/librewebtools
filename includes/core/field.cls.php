<?php

/**
 * @file
 * Field class to use for creating, collecting, and validating user fields
 *
 */

class Field{
  public $value = '';        /**< value to be validated */
  public $message = '';      /**< message to be emitted based on validation */
  public $error = 0;         /**< int error number based on validation */
  
  /**
   * Qualitative name of format type
   *
   * 'preg' test against a regular expression
   * 'memo' longer, multi-line text
   * 'text' oneline text
   * 'num'  numeric values
   * 'date' test against any date format
   *
   */
  private $type;
  /**
   * Qualitative format type or regular expression
   *
   * Any regular expression where $type=='preg'
   * 'all', 'svghtml', 'html', 'basicsvg', 'basichtml', 'simple', 'nohtml' where $type=='memo'
   * 'email', 'password', 'oneline', 'nowacky' where $type=='text'
   * 'int', 'dec' where $type=='num'
   * Any string date format where $type=='date'
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
   * @param string $input Untested user input from user
   * @param string $type Qualitative name of format type
   * @param string $format Qualitative format type OR Regular expression test (only when $type=='preg')
   * @param boolean $required Optional: set to true if the value is required
   * $param int $max_chars Optional: Limit number of characters
   * @param boolean $trim Optional: set to true if you want to enable auto trimming
   */
  public function __construct($value, $type, $format, $required=false, $max_chars=0, $trim=true){
    $this->value = $value;
    $this->type = $type;
    $this->format = $format;
    $this->required = $required;
    $this->max_chars = $max_chars;
    $this->trim = $trim;
  }
  /**
   * Sets value of Field
   * @param string $input Untested user input from user
   */ 
  public function setValue($value){
    $this->value = $value;
  }
  
  /**
   * Sets type of input
   * @param string $type Qualitative name of format type
   */ 
  public function setType($type){
    $this->type = $type;
  }
  
  /**
   * Sets format of input
   * @param string $format Qualitative format type OR Regular expression test (only when $type=='preg')
   */ 
  public function setFormat($format){
    $this->format = $format;
  }
  /**
   * Sets whether the field is required
   * @param boolean $required Optional: set to true if the value is required
   */
  public function setRequired($required=false){
    if(is_bool($required)){
      $this->required = $required;
    }
  }
  
  /**
   * Sets maximum, and optionally minimum number of characters
   * $param int $max_chars Optional: Limit number of characters
   * $param int $min_chars Optional: Lower limit for number of characters
   */
  public function setChars($max_chars=0, $min_chars=0){
    if (is_numeric($max_chars) && $max_chars >= 0){
      $this->max_chars = (int)$max_chars;
    }
    if (is_numeric($min_chars) && $min_chars >= 0){
      $this->min_chars = (int)$min_chars;
    }
  }
  /**
   * Sets whether the field will autotrim
   * @param boolean $required Optional: set to true if the value is required
   */ 
  public function setTrim($trim){
    if(is_bool($trim = true)){
      $this->trim = $trim;
    }
  }
  
 /**
   * Sets the range and step values for numeric inputs
   * @param mixed $min Optional: minumum value accepted for input (float or int)
   * @param mixed $max Optional: maximum value accepted for input (float or int)
   * @param mixed $step Optional: step interval value accepted for input (float or int)   
   */  
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

 /**
   * Sets the range and step values for numeric inputs
   * @param boolean $min Optional: set to false to not include the value
   * @param boolean $max Optional: set to false to not include the value
   * @param boolean $step Optional: set to true to auto "round" to step
   */    
  public function setBounds($min=true, $max=true, $step=true){
    if(is_bool($min)){
      $this->inc_min = $min;
    }
    if(is_bool($max)){
      $this->inc_max = $max;
    }
    if(is_bool($step)){
      $this->auto_step = $step;
    }
  }


  /**
   * Tests the value for validity for database import and application safety
   * 
   * Error Numbers:
   *  0 = no error
   * 11 = Empty value
   * 12 = String too long
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
   * 
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
    if ($this->max_chars > 0 && strlen($this->value) > $this->max_chars){
      $this->error = 12;
      $this->message = "Invalid: Please enter a value with no more than {$this->max_chars} characters";
      return;
    }
    // Handle too few characters
    if ($this->min_chars > 0 && $this->max_chars >= $this->min_chars && strlen($this->value) < $this->min_chars){
      $this->error = 13;
      $this->message = "Invalid: Please enter a value with no less than {$this->min_chars} characters";
      return;
    }
    
    //Check for all type cases
    if ($this->type=='preg'){
      // Do regular expression
      $matches = array();
      preg_match($this->format, $this->value, $matches);
      if (count($matches)==0 || $matches[0] != $this->value){
        $this->error = 21;
        $this->message = 'Invalid: Value does not match the pattern expected.';
        return;
      }
    }
    elseif ($this->type == 'memo'){
      // TODO: better determine qualitative filters for HTML
      if ($this->format === 'all'){
        // Allow everything (this is dangerous, unless this is HTML encoded somewhere else)
        
      }
      elseif($this->format !== 'nohtml'){
        if($this->format==='svghtml'){
          // Allow most HTML and SVG, but no scripts
          $qualifier = 'html+svg';
        }
        elseif($this->format=='html'){
          // Allow most HTML, but no SVG and no scripts
          $qualifier = 'html';
        }
        elseif($this->format=='basicsvg'){
          // Allow basic HTML/SVG, but no raster images
          $qualifier = 'basic+svg';
        }
        elseif($this->format=='basichtml'){
          // Allow basic HTML, no images, no SVG
          $qualifier = 'basic';
        }
        else{
          // Allow the nearly no tags, and definitely no links or styling
          $qualifier = 'simple';
        }
        $xml = new XML($input, $qualifier);
        $xml->scrub();
        $this->value = $xml->markup;

      }
      else{
        //Just HTMLencode everything!
        $this->value = htmlspecialchars($this->value);
      }
      
    }
    elseif ($this->type=='text'){
      if ($this->format=='password'){
        //Allow nearly everything for a oneline password
        if(fnmatch("*\t*",$this->value) || fnmatch("*\r*",$this->value) || fnmatch("*\n*",$this->value)){
          $this->error = 41;
          $this->message = 'Invalid: Please remove line breaks (hard returns) or tabs.';
          return;
        }
      }
      elseif($this->format=='oneline'){
        //$input = core_validate_descript($input);
        //Allow only one line text, do not allow CR or LF
        if(fnmatch("*\r*",$this->value) || fnmatch("*\n*",$this->value)){
          $this->error = 42;
          $this->message = 'Invalid: Please remove line breaks (hard returns).';
          return;
        }
      }
      elseif($this->format=='email'){
        //Email formatting only
        preg_match('/([\w\-\.%+-]+\@[\w\-]+\.[\w\-]+)/',$this->value, $matches);
        if (count($matches)==0 || $matches[0] != $this->value){
          $this->error = 43;
          $this->message = 'Invalid: Not a valid email address.';
          return;
        }
      }
      elseif($this->format=='nowacky'){
        //No special characters
        preg_match('/[\w-]*/', $this->value, $matches);
        if (count($matches)==0 || $matches[0] != $this->value){
          $this->error = 44;
          $this->message = 'Invalid: Special characters exist, please only use numbers, letters, hyphens, and underscores.';
          return;
        }      
      }
    }
    elseif ($this->type=='date'){
      //Check time against $format (which uses PHP date() format string)
      if(date_create_from_format($this->format, $this->value)){
        $date = date_create_from_format($this->format, $this->value);
        $formatted = date_format($date, $this->format);
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
    elseif ($this->type=='num'){
      if ($this->format=='int'){
        //Integer Numbers
        $this->value = str_replace(',','',$this->value);
        $this->value = str_replace(' ','',$this->value);
        if (!is_numeric($this->value) || fmod($this->value,1) != 0){
          $this->error = 61;
          $this->message = 'Invalid: Value is not an integer.';
          return;
        }
      }
      elseif($this->format=='dec'){
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
            $this->value = floor($steps) + $this->min;
          }
          else{
            $this->value = ceil($steps) + $this->min;
          }
        }
      }
    }
    
    //successful output
    $this->error = 0;
    $this->message = '';
    return;
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
