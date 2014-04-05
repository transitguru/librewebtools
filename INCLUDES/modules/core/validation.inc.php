<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transiguru.info>
 * 
 * Form validation functions are in this script
 */

/**
 * Tests user input for validity for database import and application safety
 * 
 * @param string $input Untested user input from user
 * @param string $type Qualitative name of format type
 * @param string $format Qualitative format type OR Regular expression test (only when $type=='preg')
 * $param int $chars Optional: Limit number of characters
 * @param array $range Optional: [0] or ['min'], [1] or ['max'], [2] or ['step']  (only when $type=='num' and $format=='int' or $format=='dec')
 * $param array $range_flags Optional: [0] or ['min'], [1] or ['max'] (booleans set to true if not including the end value)
 * @param boolean $required Optional: set to true if the value is required
 * @param boolean $notrim Optional: set to true if you want to turn off automatic whitespace trimming
 * 
 * @return array Output result: ['success'] boolean true if good, ['value'] sanitized value, ['message'] error message 
 */

function testText($input, $type, $format,  $chars=NULL, $range=array(null, null, null), $range_flags=array(false, false), $required=false, $notrim=false){
  //handle trimming
  if (!$notrim){
    $output['value'] = $input = trim($input);
  }
  
  //Handle empty inputs
  if ($required and $input == ""){
    $output["success"] = false;
    $output["value"] = $input;
    $output["message"] = "Required: Please enter a value.";
    return $output;
  }
  elseif ($input == ""){
    $output["success"] = true;
    $output["value"] = $input;
    $output["message"] = "";
    return $output;
  }
  
  //Handle too many characters
  if (!is_null($chars) && is_numeric($chars) && strlen($input)>$chars){
    $output["success"] = false;
    $output["value"] = $input;
    $output["message"] = "Invalid: Please enter a value with less than {$chars} characters";
    return $output;
  }
  
  //Check for all type cases
  if ($type=='preg'){
    // Do regular expression
    preg_match($format, $input, $matches);
    if ($matches[0]!=$input){
      $output["success"] = false;
      $output["value"] = $input;
      $output["message"] = "Invalid: Value does not match the pattern expected.";
      return $output;
    }
  }
  elseif ($type=='memo'){
    if ($format=='all'){
      //Allow almost anything (including scripts)
      
    }
    elseif($format=='noscript'){
      //Don't allow javascript tags, allow any other tags
      
    }
    elseif($format=='somehtml'){
      //Allow a limited set of HTML tags
      
    }
    elseif($format=='nohtml'){
      //Don't allow any HTML tags
      
    }
    elseif($format=='htmlencode'){
      //Encode all html entities for preformatting text
    }
  }
  elseif ($type=='text'){
    if ($format=='password'){
      //Allow nearly everything for a oneline password
      if(fnmatch("*\t*",$input) || fnmatch("*\r*",$input) || fnmatch("*\n*",$input)){
        $output["success"] = false;
        $output["value"] = $input;
        $output["message"] = "Invalid: Please remove line breaks (hard returns) or tabs.";
        return $output;
      }
    }
    elseif($format=='oneline'){
      //Allow only one line text, do not allow CR or LF
      if(fnmatch("*\r*",$input) || fnmatch("*\n*",$input)){
        $output["success"] = false;
        $output["value"] = $input;
        $output["message"] = "Invalid: Please remove line breaks (hard returns).";
        return $output;
      }
    }
    elseif($format=='email'){
      //Email formatting only
      preg_match('/([\w\-]+\@[\w\-]+\.[\w\-]+)/',$input, $matches);
      if ($matches[0] != $input){
        $output["success"] = false;
        $output["value"] = $input;
        $output["message"] = "Invalid: Not a valid email address.";
        return $output;
      }
    }
    elseif($format=='nowacky'){
      //No special characters
      preg_match('/[\w-]*/', $input, $matches);
      if ($matches[0] != $input){
        $output["success"] = false;
        $output["value"] = $input;
        $output["message"] = "Invalid: Special characters exist, please only use numbers, letters, hyphens, and underscores.";
        return $output;
      }      
    }
    elseif($format=='multiline'){
      //Allow multiline text
      
    }
  }
  elseif ($type=='date'){
    //Check time against $format (which uses PHP date() format string)
    if (!date_create_from_format($format, $string)){
      $output["success"] = false;
      $output["value"] = $input;
      $output["message"] = "Invalid: Value is not a valid date.";
      return $output;
    }
    else{
      $date = date_create_from_format($format, $string);
      $output['value'] = $input = date_format($date, $format);
    }
  }
  elseif ($type=='num'){
    if ($format=='int'){
      //Integer Numbers
      $input = str_replace(",","",$input);
      $input = str_replace(" ","",$input);
      if (!is_numeric($input)){
        $output["success"] = false;
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
        $output["success"] = false;
        $output["value"] = $input;
        $output["message"] = "Invalid: Value is not a number.";
        return $output;
      }
    }
    //Make sure the number is within the range
  }
  
  //successful output
  $output["success"] = true;
  $output["value"] = $input;
  $output["message"] = "";
  return $output;
}

