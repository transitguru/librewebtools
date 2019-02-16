<?php
namespace LWT\Modules\Test;
/**
 * Test Xml Class
 * 
 * Testing for the Xml class
 *
 * @category Unit Testing
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Xml extends Tester{

  /**
   * Tests Xml Object
   */
  public function run(){
    echo "\n\nTest for XML Validation\n\nTesting a basic set of nodes\n\n";

    $directory = DOC_ROOT . '/app/modules/test/config/';
    $input = file_get_contents($directory . 'test1.html');
    echo $input ."\nAfter scrubbing..\n\n";
    $elements = array('p', 'em', 'ul', 'li');
    $attributes = array ('class' => array(), 'style' => array('p'));
    
    $xml = new \LWT\Xml($input, 'html', $elements, $attributes, true);
    $xml->scrub();
    echo $xml->markup;

    
    echo "\n\nTesting something a bit more complex\n\n";
    
    $input = file_get_contents($directory . 'test2.html');
    echo $input ."\nAfter scrubbing..\n\n";
    $elements = array('svg', 'g', 'circle', 'path', 'text', 'style');
    $attributes = array ('cx' => array(), 'cy' => array(), 'r' => array(), 'style' => array(), 'd' => array('path'));

    $svg = new \LWT\Xml($input, 'html', $elements, $attributes);
    $svg->scrub();
    echo $svg->markup;
    
    echo "\n\nA very simple text String\n\n";
    
    $input = "This is a basic String";
    
    echo $input ."\nAfter scrubbing..\n\n";

    $xml = new \LWT\Xml($input, 'basic');
    $xml->scrub();
    echo $xml->markup;

    echo "\n\nA very basic text String\n\n";
    
    $input = "This is another <em>basic</em> String";
    
    echo $input ."\nAfter scrubbing..\n\n";

    $xml = new \LWT\Xml($input, 'basic');
    $xml->scrub();
    echo $xml->markup;

    echo "\n\nHow about mal-formed XML?\n\n";
    
    $input = "This is a <strong> Broken <em>file with </strong> misnested</em> Strings";
    
    echo $input ."\nAfter scrubbing..\n\n";

    $xml = new \LWT\Xml($input, 'basic');
    $xml->scrub();
    echo $xml->markup;

    echo "\n\nTesting XML with empty attributes\n\n";
    
    $input = file_get_contents($directory . 'test2.html');
    echo $input ."\nAfter scrubbing..\n\n";
    $elements = array('svg', 'g', 'circle', 'path', 'text', 'style');
    $attributes = array ('cx' => array(), 'cy' => array(), 'r' => array(), 'style' => array(), 'd' => array('path'));

    $svg = new \LWT\Xml($input, 'html', $elements, $attributes);
    $svg->scrub();
    echo $svg->markup;

    echo "\nTesting \"pretty\" flag\n";
    $input = file_get_contents($directory . 'test3.html');

    echo $input;

    echo "\nLeaving it ugly....\n"
    $svg = new \LWT\Xml($input, 'html', $elements, $attributes);
    $svg->scrub();
    echo $svg->markup;

    echo "\nMaking it pretty....\n"
    $svg->scrub(true);
    echo $svg->markup;
    
    echo "\n\nTest Complete!\n";
    exit;
  }
}

