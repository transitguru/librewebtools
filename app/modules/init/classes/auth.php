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
    if (fnmatch('application/json*', $this->inputs->content_type)){
      // Load the forms
      $directory = DOC_ROOT . '/app/modules/init/config/';
      $form_doc = file_get_contents($directory . 'forms.json');
      $forms = json_decode($form_doc)->forms;

      // Process the path
      $begin = mb_strlen($this->path->root);
      if (mb_strlen($this->inputs->uri) > $begin){
        $pathstring = mb_substr($this->inputs->uri, $begin);
      }
      else{
        $pathstring = '';
      }

      // Do something based on path
      if ($pathstring == 'login'){
        if (isset($this->inputs->post->user) && isset($this->inputs->post->pass)){
          $this->session->login($this->inputs->post->user,$this->inputs->post->pass);
        }
        else{
          $form = new \LWT\Form($forms->login);
          $json = $form->export_json();
          echo $json;
        }
      }
      elseif ($pathstring == 'logout'){
        $this->session->logout();
      }
      else{
        header('Pragma: ');
        header('Cache-Control: ');
        header('Content-Type: application/json');
        $payload = (object)[
          'status' => 'Not Found',
          'code' => 404
        ];
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
      }
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

