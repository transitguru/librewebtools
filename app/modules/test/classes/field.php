<?php
namespace LWT\Modules\Test;
/**
 * Test Field Class
 *
 * Testing for the Field Xml class
 *
 * @category Unit Testing
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Field extends Tester{

  /**
   * Tests Field::validate()
   *
   *  0 = no error
   * 11 = Empty value
   * 12 = String too long
   * 13 = String too short
   * 21 = Does not match regex
   * 41 = Line breaks/tabs in password
   * 42 = Line breaks in oneline input
   * 43 = Invalid email address
   * 44 = Special characters in input
   * 51 = Does not match date format
   * 52 = Date format good, but date itself is invalid
   * 61 = Not an integer
   * 62 = Not a number
   * 63 = Value less than or equal to minimum
   * 64 = Value less than minimum
   * 65 = Value greater than or equal to maximum
   * 66 = Value greater than maximum
   * 67 = Value does not match resolution (too precise)
   *
   * @param Object $inputs Field definitions that correspond with inputs for Field::validate()
   * @param int $error Expected error number that should be returned
   *
   *
   */
  private function validation($inputs, $error=0){
    // try making the object
    $field = new \LWT\Field($inputs);

    $field->validate();

    // Break the object!
    if($field->error != $error){
      $status['message'] = "Failure, found {$field->error}, expected {$error}";
      $status['error'] = 1;
    }
    else{
      $status['message'] = "Success! Found and expected {$field->error}";
      $status['error'] = 0;
    }
    return $status;
  }

  /**
   * Batch-runs tests of the new field object
   *
   */
  public function run(){
    // Find out data directory
    $directory = DOC_ROOT . '/app/modules/test/config/';
    $this->time = date('Y-m-d H:i:s');
    $json = file_get_contents($directory . 'field.json');
    $object = json_decode($json); /**< Unpacked JSON object of all tests */

    $terror = 0;
    $tnum = 0;
    foreach($object->test_series as $series){
      echo "\n\nTesting {$series->code} series ({$series->type}) errors\n\n";
      $error = 0;
      $num = count($series->tests);
      foreach($series->tests as $test){
        $inputs = $test->inputs;
        if (isset($inputs->value_src) && is_file($directory . '/' . $inputs->value_src)){
          $inputs->value = file_get_contents($directory . '/' . $inputs->value_src);
        }
        $result = $this->validation($inputs,$test->error);
        echo $result['message'] . "\n";
        $error += $result['error'];
      }
      echo "Tests ran with {$error}/{$num} errors.\n";
      $terror += $error;
      $tnum += $num;
    }

    // Final Report
    echo "Total Error: {$terror}/{$tnum}\n\n";
    exit;
  }
}

