<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * This file includes testing functions, not usually to be run on deployment!
 */

/**
 * Prints and array into expandable links using the JS function toggle_hide()
 * 
 * @param array $array Array to be printed
 * @param string $prefix ID prefix to prevent multiple test prints from breaking
 * @param int $n Number to make each UL have unique identifier
 * @return int Running total of $n so that all IDs are unique
 */
function lwt_test_array_print($array, $prefix = 'foo', $n=0){
  if ($n >= 1){
    $hide = 'class="hide"';
  }
  else{
    $hide = '';
  }
  echo '<ul id="'.$prefix.'_'.$n.'" '.$hide.'>'."\n";
  foreach ($array as $key => $value){
    $n++;
    echo '<li><a href="javascript:;" onclick="toggle_hide(\''.$prefix.'_'.$n.'\');">'.$key.'</a> ';
    if (is_array($value)){
      $n = lwt_test_array_print($value, $prefix, $n);
    }
    else{
      echo $value;
    }
    echo "</li>\n";
  }
  echo "</ul>\n";
  return $n;
}

function lwt_test_showtime($begin, $end){
  $time = $end - $begin;
  echo $time * 1000 . 'ms';
}
