<?php
namespace LWT\Modules\Test;
/**
 * @file
 * LibreWebTools Test Class
 *
 * Test class used in calling test suites via the JSON object received via post
 *
 * @category   Unit Testing
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Test Extends \LWT\Subapp{
  public function ajax(){
    //This function will use properly formed JSON to call the appropriate test
    $namespace = "\\LWT\\Modules\\Test\\";
    if (fnmatch('application/json*', $this->inputs->content_type) || fnmatch('text/json*', $this->inputs->content_type)){
      if (isset($this->inputs->post->command) && class_exists($namespace . $this->inputs->post->command)){
        $class = $namespace . $this->inputs->post->command;
        $test = new $class($this->path, $this->inputs, $this->session);
      }
      else{
        $test = new Tester($this->path, $this->inputs, $this->session);
      }
      $test->run();
    }
  }

  public function render(){
    echo "<p>Testing GUI is not yet enabled!</p>";
  }
}
