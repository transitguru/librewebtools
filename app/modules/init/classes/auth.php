<?php
namespace LWT\Modules\Init;
/**
 * @file
 * LibreWebTools Auth Class
 *
 * Authentication and profile management
 *
 * @category   Authentication
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Auth Extends \LWT\Subapp{
  public function ajax(){
    $this->auth(true);
  }

  public function render(){
    $this->auth(false);
  }

  private function auth($ajax = false){
    if($ajax == true){
      echo "This is ajax!";
    }
    else{
      echo "<h1>Inside the wrapper</h1>";
    }
    echo "<p>You have successfully constructed the path, see dump below</p><pre>\n";
    $everything = (object)[];
    $everything->uri = $this->uri;
    $everything->method = $this->method;
    $everything->user_input = $this->inputs;
    $everything->session = $this->session;
    var_dump($everything);
    echo "</pre>";
  }
}

