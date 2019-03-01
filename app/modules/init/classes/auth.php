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
  /**
   * list of allowable paths that end up rendering or outputting a form
   */
  public $valid_paths = ['login', 'profile', 'password', 'forgot'];

  /**
   * Runs prior to any HTML output
   */
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
        $this->form->message = 'Invalid username or password';
        $this->form->error = 1;
      }
      if (fnmatch('application/json*', $this->inputs->content_type)){
        header('Pragma: ');
        header('Cache-Control: ');
        header('Content-Type: application/json');
        $json = $this->form->export_json();
        echo $json;
        exit;
      }
    }
    elseif ($this->pathstring == 'profile'){
      $this->form = new \LWT\Form($forms->profile);
      if (isset($this->inputs->post->submit)){
        if ($this->inputs->post->submit == 'Update'){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
        }
        elseif ($this->inputs->post->submit == 'Cancel'){
          $this->form->message = 'Cancelled...';
        }
      }
      if (fnmatch('application/json*', $this->inputs->content_type)){
        header('Pragma: ');
        header('Cache-Control: ');
        header('Content-Type: application/json');
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

  /**
   * Runs while inside the template, usually rendering some HTML
   */
  public function render(){
    if (in_array($this->pathstring,$this->valid_paths)){
      if (is_object($this->form)){
        $html = $this->form->export_html();
        echo $html;
      }
      else{
        echo "No form found";
      }
    }
    elseif($this->pathstring == ''){
      echo '<h3>User Authentication Module</h3><p>Variable dump appears below</p>';
      echo "\n<pre>\n\n";
      var_dump($this);
      echo "\n\n</pre>\n";
    }
    else{
      $this->render_404();
    }
  }
}

