<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transiguru.info>
 * 
 * Form validation 
 */

/**
 * Tests user input for validity for database import and application safety
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
 * @param string $input Untested user input from user
 * @param string $type Qualitative name of format type
 * @param string $format Qualitative format type OR Regular expression test (only when $type=='preg')
 * @param boolean $required Optional: set to true if the value is required
 * $param int $chars Optional: Limit number of characters
 * @param boolean $notrim Optional: set to true if you want to turn off automatic whitespace trimming
 * @param array $range Optional: [0] or ['min'], [1] or ['max'], [2] or ['step']  (only when $type=='num' and $format=='int' or $format=='dec')
 * $param array $range_flags Optional: [0] or ['min'], [1] or ['max'], [2] or ['step'] (booleans set to true if not including the end value or turn off autorounding for [2] || ['step'])
 * 
 * @return array Output result: ['error'] int error code (0 is good), ['value'] sanitized value, ['message'] error message 
 */
function core_validate_inputs($input, $type, $format, $required=false, $chars=NULL, $notrim=false, $range=array(null, null, null), $range_flags=array(false, false, false)){
  //handle trimming
  if (!$notrim){
    $input = trim($input);
  }
  
  //Handle empty inputs
  if ($required and $input == ""){
    $output["error"] = 11;
    $output["value"] = $input;
    $output["message"] = "Required: Please enter a value.";
    return $output;
  }
  elseif ($input == ""){
    $output["error"] = 0;
    $output["value"] = $input;
    $output["message"] = "";
    return $output;
  }
  
  //Handle too many characters
  if (!is_null($chars) && is_numeric($chars) && strlen($input)>$chars){
    $output["error"] = 12;
    $output["value"] = $input;
    $output["message"] = "Invalid: Please enter a value with less than {$chars} characters";
    return $output;
  }
  
  //Check for all type cases
  if ($type=='preg'){
    // Do regular expression
    preg_match($format, $input, $matches);
    if (count($matches)==0 || $matches[0]!=$input){
      $output["error"] = 21;
      $output["value"] = $input;
      $output["message"] = "Invalid: Value does not match the pattern expected.";
      return $output;
    }
  }
  elseif ($type=='memo'){
    if ($format=='all'){
      // Allow everything (this is dangerous, unless this is HTML encoded somewhere else)
      
    }
    elseif($format=='noscript'){
      // Automatically remove script tags and such
      $input = core_validate_descript($html);
    }
    elseif($format=='somehtml'){
      // First, remove script tags and attributes
      $input = core_validate_descript($html);
      
      // Then get whitelisted tags, convert remaining to spans?
    }
    elseif($format=='nohtml'){
      // Encode all tags to prevent them from being tags?
      $input = htmlspecialchars($input);
    }
  }
  elseif ($type=='text'){
    if ($format=='password'){
      //Allow nearly everything for a oneline password
      if(fnmatch("*\t*",$input) || fnmatch("*\r*",$input) || fnmatch("*\n*",$input)){
        $output["error"] = 41;
        $output["value"] = $input;
        $output["message"] = "Invalid: Please remove line breaks (hard returns) or tabs.";
        return $output;
      }
    }
    elseif($format=='oneline'){
      //$input = core_validate_descript($input);
      //Allow only one line text, do not allow CR or LF
      if(fnmatch("*\r*",$input) || fnmatch("*\n*",$input)){
        $output["error"] = 42;
        $output["value"] = $input;
        $output["message"] = "Invalid: Please remove line breaks (hard returns).";
        return $output;
      }
    }
    elseif($format=='email'){
      //Email formatting only
      preg_match('/([\w\-\.%+-]+\@[\w\-]+\.[\w\-]+)/',$input, $matches);
      if (count($matches)==0 || $matches[0] != $input){
        $output["error"] = 43;
        $output["value"] = $input;
        $output["message"] = "Invalid: Not a valid email address.";
        return $output;
      }
    }
    elseif($format=='nowacky'){
      //No special characters
      preg_match('/[\w-]*/', $input, $matches);
      if (count($matches)==0 || $matches[0] != $input){
        $output["error"] = 44;
        $output["value"] = $input;
        $output["message"] = "Invalid: Special characters exist, please only use numbers, letters, hyphens, and underscores.";
        return $output;
      }      
    }
  }
  elseif ($type=='date'){
    //Check time against $format (which uses PHP date() format string)
    if(date_create_from_format($format, $input)){
      $date = date_create_from_format($format, $input);
      $formatted = date_format($date, $format);
      if ($formatted != $input){
        $output["error"] = 52;
        $output["value"] = $input;
        $output["message"] = "Invalid: Value is not a valid date.";
        return $output;
      }
    }
    else{
      $output["error"] = 51;
      $output["value"] = $input;
      $output["message"] = "Invalid: Date format is wrong.";
      return $output;
    }
  }
  elseif ($type=='num'){
    if ($format=='int'){
      //Integer Numbers
      $input = str_replace(",","",$input);
      $input = str_replace(" ","",$input);
      if (!is_numeric($input) || fmod($input,1) != 0){
        $output["error"] = 61;
        $output["value"] = $input;
        $output["message"] = "Invalid: Value is not an integer.";
        return $output;
      }
    }
    elseif($format=='dec'){
      //Decimal Numbers
      $input = str_replace(",","",$input);
      $input = str_replace(" ","",$input);
      if (!is_numeric($input)){
        $output["error"] = 62;
        $output["value"] = $input;
        $output["message"] = "Invalid: Value is not a number.";
        return $output;
      }
    }
    //Collect Ranges
    if (isset($range[0]) && is_numeric($range[0])){
      $min = $range[0];
    }
    elseif (isset($range['min']) && is_numeric($range['min'])){
      $min = $range['min'];
    }
    else{
      $min = NULL;
    }
    if (isset($range[1]) && is_numeric($range[1])){
      $max = $range[1];
    }
    elseif (isset($range['max']) && is_numeric($range['max'])){
      $max = $range['max'];
    }
    else{
      $max = NULL;
    }
    if (isset($range[2]) && is_numeric($range[2])){
      $step = $range[2];
    }
    elseif (isset($range['step']) && is_numeric($range['step'])){
      $step = $range['step'];
    }
    else{
      $step = NULL;
    }
    
    //swap min and max if they are transposed
    if (!is_null($max) && !is_null($min) && $min > $max){
      $temp = $max;
      $max = $min;
      $min = $temp;
    }
    if (((isset($range_flags[0]) && $range_flags[0] == TRUE) || (isset($range_flags['min']) && $range_flags['min']==TRUE)) && !is_null($min) && $input <= $min){
      $output["error"] = 63;
      $output["value"] = $input;
      $output["message"] = "Out of Range: Must be greater than {$min}.";
      return $output;
    }
    elseif(!is_null($min) && $input < $min){
      $output["error"] = 64;
      $output["value"] = $input;
      $output["message"] = "Out of Range: Must be at least {$min}.";
      return $output;
    }
    if (((isset($range_flags[1]) && $range_flags[1] == TRUE) || (isset($range_flags['max']) && $range_flags['max']==TRUE)) && !is_null($max) && $input >= $max){
      $output["error"] = 65;
      $output["value"] = $input;
      $output["message"] = "Out of Range: Must be less than {$max}.";
      return $output;
    }
    elseif(!is_null($max) && $input > $max){
      $output["error"] = 66;
      $output["value"] = $input;
      $output["message"] = "Out of Range: Must at most {$max}.";
      return $output;
    }
    
    //Check for step (or autoround)
    if (!is_null($step)){
      if (((isset($range_flags[2]) && $range_flags[2] == TRUE) || (isset($range_flags['step']) && $range_flags['step'] == TRUE)) && fmod($input - $min , $step) != 0 && $input != $max){
        $output["error"] = 67;
        $output["value"] = $input;
        $output["message"] = "Too precise: Must be a value from {$min} every {$step}.";
        return $output;
      }
      elseif(fmod($input - $min , $step) != 0 && $input != $max){
        $offset = $input - $min;
        $steps = $offset/$step;
        if (fmod($steps, 1) < 0.5){
          $input = floor($steps) + $min;
        }
        else{
          $input = ceil($steps) + $min;
        }
      }
    }
  }
  
  //successful output
  $output["error"] = 0;
  $output["value"] = $input;
  $output["message"] = "";
  return $output;
}


/**
 * Gets children nodes from a DOM element (used for validation scripts)
 * 
 * @param object $node HTML node in question
 * @param array $elements Array of elements to continue to collect
 * 
 * @return array $elements Array of elements that continue to be appended
 * 
 */
function core_validate_dom_children($node, $elements){
  $children = $node->childNodes;
  if (count($children)>0){
    foreach ($children as $child){
      $elements[] = $child;
      $elements = core_validate_dom_children($child, $elements);
    }
  }
  return $elements;
}

/**
 * Removes scripting from HTML input
 * 
 * @param string $html String, expected to be an html document
 * 
 * @return string $cleaned Clean HTML that should be safe
 * 
 */
function core_validate_descript($html){
  $dom = new DOMDocument;
  $dom->loadHTML($html);
      
  //Figure out what was added to later remove again!
  $compare = $dom->saveHTML();
  $chars = strlen($html);
  $tchar = strlen($compare);
  $ltrim = stristr($compare, $html, true);
  $lchar = strlen($ltrim);
  $rtrim = substr($compare,$lchar+$chars);

  //Delete Script tags
  $domNodeList = $dom->getElementsByTagName('script');
  if (count($domNodeList)>0){
    foreach ( $domNodeList as $domElement ) {
      $domElemsToRemove[] = $domElement;
    }
    foreach( $domElemsToRemove as $domElement ){
      $domElement->parentNode->removeChild($domElement);
    }
  }
  
  //Delete on* events  
  $events = array(
    'onload',
    'onunload',
    'onclick',
    'ondblclick',
    'onmousedown',
    'onmouseup',
    'onmouseover',
    'onmousemove',
    'onmouseout',
    'onfocus',
    'onblur',
    'onkeypress',
    'onkeydown',
    'onkeyup',
    'onsubmit',
    'onreset',
    'onselect',
    'onchange',  
  );
  $elements = array();
  $elements = core_validate_dom_children($dom,$elements);
  
  foreach($elements as $element){
    if (get_class($element) == 'DOMElement'){
      foreach ($events as $event){
        $element->removeAttribute($event);
      }
    }
  }
  
  $clean = $dom->saveHTML(); 
  $clean = substr($clean,$lchar);
  $clean = stristr($clean, $rtrim,TRUE);

  return $clean;
}

