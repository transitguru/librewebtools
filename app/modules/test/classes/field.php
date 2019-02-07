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
    $field = new Field($inputs)
    
    $field->validate();

    // Break the object!
    if($field->error != $error){
      $status['message'] = "Failure, found {$field->error}, expected {$error}";
      $status['error'] = 1;
    }
    else{
      $status['message'] = "Success, found {$field->error}, expected {$error}";
      $status['error'] = 0;
    }
    return $status;
  }

  /**
   * Batch-runs tests of the new field object
   * 
   */
  public function run(){
    $this->time = date('Y-m-d H:i:s');

    $terror = 0;
    $tnum = 0;
    $format_types = [
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
    $preg = ['/[0-9a-zA-Z]*/','/[0-9a-z]*/','/[a-z][0-9a-z]*/'];
    $date = ['Y-m-d H:i:s', 'm/d/Y H:i'];
    $ranges = [ 
      [0,4,1],
      [-2,3,0.5],
      [0,1,null],
    );
    $range_flags = [
      [true, true],
      [true, true, true],
    );
    
    //Build initial inputs object
    $defs = (object) [
      'label' => 'Label',
      'name' => 'form_name_for_html',
      'element' => 'text',
      'list' => [
        (object) ['name' => 'Pennsylvania', 'value', 'PA'],
        (object) ['name' => 'Ohio', 'value', 'OH'],
        (object) ['name' => 'West Virginia', 'value', 'WV'],
      ],
      'value' => '',
      'format' => 'preg:/[0-9a-zA-Z]*/',
      'required' => false,
      'min_chars' => 0,
      'max_chars' => 0,
      'trim' => true,
      'min' => null,
      'max' => null,
      'step' => null,
      'inc_min' => null,
      'inc_max' => null,
      'auto_step' => null
    ];

    //Test for empty string issues and numchars
    echo "<pre>\n\nTesting 10 series (input) errors\n\n";
    $error = 0;
    $tests = [];

    //Allowing empty values
    $tests[] = [$inputs, 0];
    $inputs->format = 'memo';
    $tests[] = [$inputs, 0];
    $inputs->format = 'text';
    $tests[] = [$inputs, 0];
    $inputs->format = 'oneline';
    $tests[] = [$inputs, 0];
    $inputs->format = 'nohtml';
    $tests[] = [$inputs, 0];
    $inputs->format = 'date:Y-m-d H:i:s';
    $tests[] = [$inputs, 0];
    $inputs->format = 'dec';
    $tests[] = [$inputs, 0];
    $inputs->format = 'int';
    $tests[] = [$inputs, 0];

    //Disallowing empty values
    $inputs->required = true;
    $inputs->format = 'preg:/[0-9a-zA-Z]*/';
    $tests[] = [$inputs, 11];
    $inputs->format = 'memo';
    $tests[] = [$inputs, 11];
    $inputs->format = 'text';
    $tests[] = [$inputs, 11];
    $inputs->format = 'oneline';
    $tests[] = [$inputs, 11];
    $inputs->format = 'nohtml';
    $tests[] = [$inputs, 11];
    $inputs->format = 'date:Y-m-d H:i:s';
    $tests[] = [$inputs, 11];
    $inputs->format = 'dec';
    $tests[] = [$inputs, 11];
    $inputs->format = 'int';
    $tests[] = [$inputs, 11];

    //Test for long/short strings
    $inputs->max_chars = 12;
    $inputs->format = 'preg:/[0-9a-zA-Z]*/';
    $inputs->value = 'Ab';
    $tests[] = [$inputs, 0];
    $inputs->format = 'memo';
    $inputs->value = 'This';
    $tests[] = [$inputs, 0];
    $inputs->format = 'text';
    $inputs->value = 'Testing this';
    $tests[] = [$inputs, 0];
    $inputs->format = 'date:Y-m-d H:i:s';
    $inputs->max_chars = 20;
    $inputs->value = '2013-01-01 00:00:00';
    $tests[] = [$inputs, 0];
    $inputs->max_chars = 2;
    $inputs->format = 'int';
    $inputs->value = 45;
    $tests[] = [$inputs, 0];
    $inputs->max_chars = 3;
    $inputs->format = 'dec';
    $inputs->value = 4.5;
    $tests[] = [$inputs, 0];
    $inputs->max_chars = 12;
    $inputs->format = 'preg:/[0-9a-zA-Z]*/';
    $inputs->value = 'Thisisalongstringthatshouldfail';
    $tests[] = [$inputs, 12];
    $inputs->format = 'memo';
    $inputs->value = 'This is another long string that should fail';
    $tests[] = [$inputs, 12];
    $inputs->format = 'text';
    $inputs->value = 'Testing this ';
    $tests[] = [$inputs, 12];
    $inputs->format = 'date:Y-m-d H:i:s';
    $inputs->value = '2013-01-01 00:00:00';
    $tests[] = [$inputs, 12];
    $inputs->max_chars = 2;
    $inputs->format = 'int';
    $inputs->value = 455;
    $tests[] = [$inputs, 12];
    $inputs->max_chars = 3;
    $inputs->format = 'dec';
    $inputs->value = 4.54;
    $tests[] = [$inputs, 12];


    $tests = array(
      array(array('', 'preg', $formats['preg'][0]),0),
      array(array('', 'memo', $formats['memo'][0]),0),
      array(array('', 'text', $formats['text'][0]),0),
      array(array('', 'date', $formats['date'][0]),0),
      array(array('', 'num', $formats['num'][0]),0),
      array(array('', 'num', $formats['num'][1]),0),
      array(array('', 'preg', $formats['preg'][0],  true, 12),11),
      array(array('', 'memo', $formats['memo'][0],  true, 12),11),
      array(array('', 'text', $formats['text'][0],  true, 12),11),
      array(array('', 'date', $formats['date'][0],  true, 12),11),
      array(array('', 'num', $formats['num'][0],  true, 12),11),
      array(array('', 'num', $formats['num'][1],  true, 12),11),
      array(array('Ab', 'preg', $formats['preg'][0],  true, 12),0),
      array(array('This', 'memo', $formats['memo'][0],  true, 12),0),
      array(array('Text', 'text', $formats['text'][0],  true, 12),0),
      array(array('2013-01-01 00:00:00', 'date', $formats['date'][0],  true, 22),0),
      array(array('23', 'num', $formats['num'][0],  true, 12),0),
      array(array('4.5', 'num', $formats['num'][1],  true, 12),0),
      array(array('Abdsfasdgfsdagadgd', 'preg', $formats['preg'][0],  true, 12),12),
      array(array('This is a Lot of text!!!!', 'memo', $formats['memo'][0],  true, 12),12),
      array(array('Text is a Lot of text', 'text', $formats['text'][0],  true, 12),12),
      array(array('2013-01-01 00:00:00', 'date', $formats['date'][0],  true, 4),12),
      array(array('2334543543', 'num', $formats['num'][0],  true, 4),12),
      array(array('4.54354364564', 'num', $formats['num'][1],  true, 4),12),
    );
    $num = count($tests);
    foreach ($tests as $test){
      $result = $this->validation($test[0],$test[1]);
      echo $result['message'] . "\n";
      $error += $result['error'];
    }
    echo "Tests ran with {$error}/{$num} errors.\n";
    $terror += $error;
    $tnum += $num;
    
    // Testing Regex errors
    echo "\n\nTesting 20 series (regex) errors\n\n";
    $error = 0;
    $tests = array(
      array(array('The_', 'preg', $formats['preg'][0]),21),
      array(array('foo', 'preg', $formats['preg'][0]),0),
      array(array('The&', 'preg', $formats['preg'][0]),21),
      array(array('2356.', 'preg', $formats['preg'][0]),0),
      array(array('Tjkhaeh', 'preg', $formats['preg'][0]),0),
      array(array('iwhekjdjfkj5t', 'preg', $formats['preg'][0]),0),
      array(array('iwhekjdjfkj5t', 'preg', $formats['preg'][1]),0),
      array(array('TheCaps32', 'preg', $formats['preg'][1]),21),
      array(array('thecaps32', 'preg', $formats['preg'][1]),0),
      array(array('a909098', 'preg', $formats['preg'][2]),0),
      array(array('A909098', 'preg', $formats['preg'][2]),21),
      array(array('9adfs09098', 'preg', $formats['preg'][2]),21),
    );
    $num = count($tests);
    foreach ($tests as $test){
      $result = $this->validation($test[0],$test[1]);
      echo $result['message'] . "\n";
      $error += $result['error'];
    }
    echo "Tests ran with {$error}/{$num} errors.\n";
    $terror += $error;
    $tnum += $num;

    // Testing string errors
    echo "\n\nTesting 40 series (string) errors\n\n";
    $error = 0;
    $tests = array(
      array(array('Password', 'text', $formats['text'][0]),0),
      array(array("Pass38y45e8uydkjh4\t", 'text', $formats['text'][0]),0),
      array(array("Pass38y45e8uydkjh4\tsdfsdd", 'text', $formats['text'][0]),41),
      array(array("Pass38y45e8uydkjh4\rjhkjhkj", 'text', $formats['text'][0]),41),
      array(array("Pass38y45e8uydkjh4\n", 'text', $formats['text'][0]),0),
      array(array("Pass38y45e8uydkjh4\ndsd", 'text', $formats['text'][0]),41),
      array(array("Pass38y45e8uydkjh4\n", 'text', $formats['text'][1]),0),
      array(array("Pass38y45e8uydkjh4\nsd", 'text', $formats['text'][1]),42),
      array(array("Pass38y45e8uydkjh4\rdsfds", 'text', $formats['text'][1]),42),
      array(array("Text with one\tline", 'text', $formats['text'][1]),0),
      array(array("Text with one line", 'text', $formats['text'][1]),0),
      array(array("foo@dev.null", 'text', $formats['text'][2]),0),
      array(array("foo@dev", 'text', $formats['text'][2]),43),
      array(array("foo.dev@null", 'text', $formats['text'][2]),43),
      array(array("someone.someone@example.com", 'text', $formats['text'][2]),0),
      array(array("someone-someone@example.com", 'text', $formats['text'][2]),0),
      array(array("someone+someone@example.com", 'text', $formats['text'][2]),0),
      array(array("someone_someone@example.com", 'text', $formats['text'][2]),0),
      array(array("some989yinmhg", 'text', $formats['text'][3]),0),
      array(array("%6&dd>", 'text', $formats['text'][3]),44),
      
    );
    $num = count($tests);
    foreach ($tests as $test){
      $result = $this->validation($test[0],$test[1]);
      echo $result['message'] . "\n";
      $error += $result['error'];
    }
    echo "Tests ran with {$error}/{$num} errors.\n";
    $terror += $error;
    $tnum += $num;

    // Testing date format errors
    echo "\n\nTesting 50 series (date) errors\n\n";
    $error = 0;
    $tests = array(
      array(array('2014-01-01 04:00:00', 'date', $formats['date'][0]),0),
      array(array('2014-01-01 04:00:00', 'date', $formats['date'][0]),0),
      array(array('2014-01-01 04:00:60', 'date', $formats['date'][0]),52),
      array(array('2014-01-01 24:00:00', 'date', $formats['date'][0]),52),
      array(array('2013-12-32 04:00:00', 'date', $formats['date'][0]),52),
      array(array('12/26/1981 17:13', 'date', $formats['date'][0]),51),
      array(array('01/26/1981 17:13', 'date', $formats['date'][0]),51),
      array(array('12/26/1981 17:13', 'date', $formats['date'][1]),0),
      array(array('2014-01-01 04:00:00', 'date', $formats['date'][1]),51),
      array(array('2014-01-01 04:00:00', 'date', $formats['date'][1]),51),
      array(array('12/26/1981 27:13', 'date', $formats['date'][1]),52),
      array(array('81/26/1981 17:13', 'date', $formats['date'][1]),52),
      
    );
    $num = count($tests);
    foreach ($tests as $test){
      $result = $this->validation($test[0],$test[1]);
      echo $result['message'] . "\n";
      $error += $result['error'];
    }
    echo "Tests ran with {$error}/{$num} errors.\n";
    $terror += $error;
    $tnum += $num;
    
    // Testing Number errors
    echo "\n\nTesting 60 series (number) errors\n\n";
    $error = 0;
    $tests = array(
      //Basic integer or float tests
      array(array('21', 'num', $formats['num'][0]),0),
      array(array('21.2', 'num', $formats['num'][0]),61),
      array(array('foo', 'num', $formats['num'][0]),61),
      array(array('21', 'num', $formats['num'][1]),0),
      array(array('21.2', 'num', $formats['num'][1]),0),
      array(array('foo', 'num', $formats['num'][1]),62),
      
      //Testing resolution
      array(array('0', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
      array(array('3', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
      array(array('2.5', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
      array(array('2.51', 'num', $formats['num'][1], true, null, false, array(0,3,0.5), array(false,false,false)),67),
      array(array('2.51', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
      array(array('3.2', 'num', $formats['num'][1], true, null, false, array(0,3.2,0.5)),0),
      array(array('0', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
      array(array('5', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
      array(array('2.5', 'num', $formats['num'][0], true, null, false, array(0,50,5)),61),
      array(array('25', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
      array(array('24', 'num', $formats['num'][0], true, null, false, array(0,50,5), array(false, false, false)),67),
      array(array('24', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
      
      //Testing ranges (integers)
      array(array('-1', 'num', $formats['num'][0], true, null, false, array(0,3,null)),64),
      array(array('0', 'num', $formats['num'][0], true, null, false, array(0,3,null)),0),
      array(array('3', 'num', $formats['num'][0], true, null, false, array(0,3,null)),0),
      array(array('4', 'num', $formats['num'][0], true, null, false, array(0,3,null)),66),
      array(array('-1', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(false, false, true)),63),
      array(array('0', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(false, false, true)),63),
      array(array('1', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(false, false, true)),0),
      array(array('2', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(false, false, true)),0),
      array(array('3', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(false, false, true)),65),
      array(array('4', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(false, false, true)),65),
      
      //Testing ranges (floats)
      array(array('-1', 'num', $formats['num'][1], true, null, false, array(0,3,null)),64),
      array(array('0', 'num', $formats['num'][1], true, null, false, array(0,3,null)),0),
      array(array('3', 'num', $formats['num'][1], true, null, false, array(0,3,null)),0),
      array(array('4', 'num', $formats['num'][1], true, null, false, array(0,3,null)),66),
      array(array('-1', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(false, false, true)),63),
      array(array('0', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(false, false, true)),63),
      array(array('0.00001', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(false, false, true)),0),
      array(array('2.99999', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(false, false, true)),0),
      array(array('3', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(false, false, true)),65),
      array(array('4', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(false, false, true)),65),
      

    );
    $num = count($tests);
    foreach ($tests as $test){
      $result = $this->validation($test[0],$test[1]);
      echo $result['message'] . "\n";
      $error += $result['error'];
    }
    echo "Tests ran with {$error}/{$num} errors.\n";
    $terror += $error;
    $tnum += $num;
    
    // Final Report
    echo "Total Error: {$terror}/{$tnum}\n\n</pre>";  
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
