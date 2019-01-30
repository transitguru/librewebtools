<?php
namespace LWT\Modules;
/**
 * @file
 * LibreWebTools Init module Class
 *
 * Interfaces with user input to handle the several purposes that the Init
 * Module must serve for the LibreWebTools application.
 *
 * @category   Modules
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Init{
  public function __construct($ajax = false, $uri='/', $method='get', $user_input = array(), $session=array()){
    echo "<p>You have successfully constructed the path, see dump below</p><pre>";
    $everything = (object)[];
    $everything->ajax = $ajax;
    $everything->uri = $uri;
    $everything->method = $method;
    $everything->user_input = $user_input;
    $everything->session = $session;
    var_dump($everything);
    echo "</pre>";
  }
}
