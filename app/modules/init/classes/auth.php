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
    // Load the applicable forms
    $directory = DOC_ROOT . '/app/modules/init/config/';
    $form_doc = file_get_contents($directory . 'forms.json');
    $forms = json_decode($form_doc)->auth;
    $this->form = (object)[];

    // Process the path
    $begin = mb_strlen($this->path->root);
    if (mb_strlen($this->inputs->uri) > $begin){
      $this->pathstring = mb_substr($this->inputs->uri, $begin);
    }
    else{
      $this->pathstring = '';
    }

    // Do something based on path
    if ($this->pathstring == 'login'){
      $this->form = new \LWT\Form($forms->login);
      if (isset($this->inputs->post->user) && isset($this->inputs->post->pass)){
        $this->session->login($this->inputs->post->user,$this->inputs->post->pass);
      }
      elseif (fnmatch('application/json*', $this->inputs->content_type)){
        $json = $this->form->export_json();
        echo $json;
        exit;
      }
    }
    elseif ($this->pathstring == 'logout'){
      $this->session->logout();
    }
    else{
      http_response_code(404);
      if (fnmatch('application/json*', $this->inputs->content_type)){
        header('Pragma: ');
        header('Cache-Control: ');
        header('Content-Type: application/json');
        $payload = (object)[
          'status' => 'Not Found',
          'code' => 404
        ];
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
      }
    }
  }

  public function render(){
    if ($this->pathstring == 'login'){
      $html = $this->form->export_html();
      echo $html;
    }
    else{
      $this->render_404();
    }
  }
}

