<?php
namespace LWT\Modules\Test;
/**
 * Test Field Class
 * 
 * Testing for the Field and Xml classes
 *
 * @category Unit Testing
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Field extends Tester{
  
  public $time = '1988-06-06 15:00:00'; /**< Date test object made, not really needed */
  
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
      $status['message'] = "Success!";
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


  public function dumpxmldata($node){
    var_dump($node);
    $children = $node->childNodes;
    if (count($children)>0){
      foreach ($children as $child){
        core_test_dumpdata($child);
      }
    }

  }

  public function xmlobject(){
    echo "<pre>\n\nTest for XML Validation\n\n";

    echo "Testing a basic set of nodes\n\n";
    $input = <<< XML
    <p class="foo" style="font-family: Sans;" onclick="Ha, trying to break this!" >Hi there, I like having <strong>Bold Text</strong> and <em>Italic Text</em>. Does the DOM Document Show this properly?</p>
    <ul>
      <!-- This is a comment!!! -->
      <li class="bar" style="style">List item one</li>
      <li>List item two</li>
      <li>List Item three</li>
    </ul>
    <div>
      Hi, I shouldn't show up!!!
    </div>
XML;
    echo $input ."\nAfter scrubbing..\n\n";
    $elements = array('p', 'em', 'ul', 'li');
    $attributes = array ('class' => array(), 'style' => array('p'));
    
    $xml = new Xml($input, 'html', $elements, $attributes, true);
    $xml->scrub();
    echo $xml->markup;

    
    echo "\n\nTesting something a bit more complex\n\n";
    
    $input = <<< XML

  <svg>
    <style>
      svg {font-family: sans;}
      circle {fill-color: #ff0000;}
      .foo {stroke-color; #000000;}
    </style>
    <script>
      var foo = 78;
      bar = foo + 5;
      console.log(bar);
    </script>
    <g>
      <circle cx="0" cy="56" r="10" style="fill: #ff0000; stroke:none;" />
      <circle class="foo" cx="0" cy="56" r="10" style="fill: #ff0000; stroke:none;" />
      <path d="m 0,0 7,4 0,0 60,30 z" />
      <text d="bogus input" >Some Text</text>
      <script><![CDATA[
        var baz = 87;
      ]]></script>
    </g>
  </svg>

XML;
    echo $input ."\nAfter scrubbing..\n\n";
    $elements = array('svg', 'g', 'circle', 'path', 'text', 'style');
    $attributes = array ('cx' => array(), 'cy' => array(), 'r' => array(), 'style' => array(), 'd' => array('path'));

    $svg = new Xml($input, 'html', $elements, $attributes);
    $svg->scrub();
    echo $svg->markup;
    
    echo "\n\nA very simple text String\n\n";
    
    $input = "This is a basic String";
    
    echo $input ."\nAfter scrubbing..\n\n";

    $xml = new Xml($input, 'basic');
    $xml->scrub();
    echo $xml->markup;

    echo "\n\nA very basic text String\n\n";
    
    $input = "This is another <em>basic</em> String";
    
    echo $input ."\nAfter scrubbing..\n\n";

    $xml = new Xml($input, 'basic');
    $xml->scrub();
    echo $xml->markup;

    echo "\n\nHow about mal-formed XML?\n\n";
    
    $input = "This is a <strong> Broken <em>file with </strong> misnested</em> Strings";
    
    echo $input ."\nAfter scrubbing..\n\n";

    $xml = new Xml($input, 'basic');
    $xml->scrub();
    echo $xml->markup;

    echo "\n\nTesting XML with empty attributes\n\n";
    
    $input = <<< XML

  <svg>
    <style>
      svg {font-family: sans;}
      circle {fill-color: #ff0000;}
      .foo {stroke-color; #000000;}
    </style>
    <script>
      var foo = 78;
      bar = foo + 5;
      console.log(bar);
    </script>
    <g>
      <circle selected cx="0" cy="56" r="10" style="fill: #ff0000; stroke:none;" />
      <circle class="foo" cx="0" cy="56" r="10" style="fill: #ff0000; stroke:none;" />
      <path foo bar d="m 0,0 7,4 0,0 60,30 z" />
      <text d="bogus input" >Some Text</text>
      <script><![CDATA[
        var baz = 87;
      ]]></script>
    </g>
  </svg>

XML;
    echo $input ."\nAfter scrubbing..\n\n";
    $elements = array('svg', 'g', 'circle', 'path', 'text', 'style');
    $attributes = array ('cx' => array(), 'cy' => array(), 'r' => array(), 'style' => array(), 'd' => array('path'));

    $svg = new Xml($input, 'html', $elements, $attributes);
    $svg->scrub();
    echo $svg->markup;
    
    echo "\n\nTest Complete!\n</pre>";
    
  }

}
