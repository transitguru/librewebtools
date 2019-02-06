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
    if (fnmatch('application/json*', $this->inputs->content_type) || fnmatch('text/json*', $this->inputs->content_type)){
      header('Pragma: ');
      header('Cache-Control: ');
      header('Content-Type: application/json');
      $payload = (object)[
        'status' => 'success',
        'code' => 200,
        'var_dump' => (object)[
          'user_input' => $this->inputs,
          'session' => $this->session,
        ],
      ];
      echo json_encode($payload, JSON_UNESCAPED_SLASHES);
      exit;
    }
  }

  public function render(){
    $this->auth(false);
  }

  private function auth($ajax = false){
    echo "<p>You have successfully constructed the path, see dump below</p><pre>\n";
    $everything = (object)[];
    $everything->user_input = $this->inputs;
    $everything->session = $this->session;
    var_dump($everything);
    echo "</pre>";
  }
}

