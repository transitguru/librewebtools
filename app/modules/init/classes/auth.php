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
    $this->form = null;

    // Process the path
    $begin = mb_strlen($this->path->root) + 1;
    if (mb_strlen($this->inputs->uri) > $begin){
      $this->pathstring = mb_substr($this->inputs->uri, $begin);
    }
    else{
      $this->pathstring = '';
    }

    //Forward request to login if not proper path for unlogged user
    if (!in_array($this->pathstring, ['login', 'forgot']) &&
        !fnmatch('reset/*', $this->pathstring) && $this->session->user_id <= 0){
      header('Location: ' . BASE_URI . $this->path->root . '/login');
      exit;
    }

    // Route request based on path
    if ($this->pathstring == 'login'){
      $this->form = new \LWT\Form($forms->login);
      if (isset($this->inputs->post->user) && isset($this->inputs->post->pass)){
        $success = $this->session->login($this->inputs->post->user,$this->inputs->post->pass);
        if($success == true){
          header('Location: ' . BASE_URI . $this->path->root);
          exit;
        }
        else{
          $this->form->message = 'Invalid username or password';
          $this->form->error = 1;
          $this->form->fields->user->error = 99;
          $this->form->fields->user->message = 'Invalid: please re-enter';
          $this->form->fields->pass->error = 99;
          $this->form->fields->pass->message = 'Invalid: please re-enter';
        }
      }
      else{
        $this->form->message = 'Please enter your username and password.';
        $this->form->status = 'warning';
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
    elseif ($this->pathstring == 'forgot'){
      $this->form = new \LWT\Form($forms->forgot);
      if (isset($this->inputs->post->submit)){
        $this->form->fill($this->inputs->post);
        $this->form->validate();
        if ($this->form->error == 0){
          $user_obj = new \LWT\User($this->session->user_id);
          $mail = $user_obj->resetpassword($this->inputs->post->email);
          if ($mail->status == 0){
            $resetpath =  BASE_URI . $this->path->root . '/reset/' . $mail->reset_code;
            $headers = "From: LibreWebTools <noreply@" . HOSTNAME . ">\r\n";
            $headers .= "Content-Type: text/plain; charset=utf-8";
            $message = "Username: " . $mail->login . "\r\n";
            $message .= "Please visit the following url to reset your password:\r\n";
            $message .= PROTOCOL . '://' . HOSTNAME . $resetpath . "\r\n";
            mail($mail->email, "Password Reset", $message, $headers);
          }
          $this->form->message = 'Email was sent to the address submitted.';
          $this->form->status = 'warning';
        }
      }
      else{
        $this->form->message = 'Please enter your email address to recover your password.';
        $this->form->status = 'warning';
      }
    }
    elseif (fnmatch('reset/*', $this->pathstring)){
      $reset_code = mb_substr($this->pathstring, 6);
      $user_obj = new \LWT\User(0);
      $uid = $user_obj->find($reset_code);
      if ($uid > 0){
        $this->form = new \LWT\Form($forms->reset);
        $this->form->message = 'Fill out the fields to set your password.';
        $this->form->status = 'warning';
        if (isset($this->inputs->post->submit) && $this->inputs->post->submit == 'Update'){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
          if ($this->form->error == 0){
            $user_obj = new \LWT\User($uid);
            $login = $user_obj->login;
            if ($this->inputs->post->new == $this->inputs->post->confirm){
              $user_obj->setpassword($this->inputs->post->new);
              header('Location: ' . BASE_URI . $this->path->root . '/profile');
              exit;
            }
            else{
              $this->form->message = 'Passwords do not match';
              $this->form->status = 'error';
            }
          }
        }
      }
    }
    elseif ($this->pathstring == 'password'){
      $this->form = new \LWT\Form($forms->password);
      $this->form->message = 'Fill out the fields to change your password.';
      $this->form->status = 'warning';
      if (isset($this->inputs->post->submit) && $this->inputs->post->submit == 'Update'){
        $this->form->fill($this->inputs->post);
        $this->form->validate();
        if ($this->form->error == 0){
          $user_obj = new \LWT\User($this->session->user_id);
          $login = $user_obj->login;
          $success = $this->session->login($login,$this->inputs->post->current, true);
          if ($success == true){
            if ($this->inputs->post->new == $this->inputs->post->confirm){
              $user_obj->setpassword($this->inputs->post->new);
              $this->form->message = 'Password was successfully changed';
              $this->form->status = 'success';
            }
            else{
              $this->form->message = 'Passwords do not match';
              $this->form->status = 'error';
            }
          }
          else{
            $this->form->message = 'Current password is incorrect';
            $this->form->status = 'error';
          }
        }
      }
    }
    elseif ($this->pathstring == 'profile'){
      $this->form = new \LWT\Form($forms->profile);
      $user_obj = new \LWT\User($this->session->user_id);
      $this->form->fields->login->value = $user_obj->login;
      $this->form->fields->firstname->value = $user_obj->firstname;
      $this->form->fields->lastname->value = $user_obj->lastname;
      $this->form->fields->email->value = $user_obj->email;

      if (isset($this->inputs->post->submit)){
        if ($this->inputs->post->submit == 'Update'){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
          if ($this->form->error == 0){
            $user_obj->login = $this->form->fields->login->value;
            $user_obj->firstname = $this->form->fields->firstname->value;
            $user_obj->lastname = $this->form->fields->lastname->value;
            $user_obj->email = $this->form->fields->email->value;
            $user_obj->write();
            $this->form->error = $user_obj->error;
            $this->form->message = $user_obj->message;
            if ($user_obj->email_unique == false){
              $this->form->fields->email->error = 99;
              $this->form->fields->email->message = $user_obj->email_message;
            }
            if ($user_obj->login_unique == false){
              $this->form->fields->login->error = 99;
              $this->form->fields->login->message = $user_obj->login_message;
            }
          }
        }
        elseif ($this->inputs->post->submit == 'Cancel'){
          $this->form->message = 'No changes were made.';
          $this->form->status = 'warning';
        }
      }
      else{
        $this->form->message = 'Update your profile by editing the fields below.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'logout'){
      $this->session->logout();
      header('Location: ' . BASE_URI . '/');
      exit;
    }
    elseif ($this->pathstring == ''){
      $this->form = new \LWT\Form($forms->nav);
    }
    else{
      http_response_code(404);
    }
    if (fnmatch('application/json*', $this->inputs->content_type)){
      header('Pragma: ');
      header('Cache-Control: ');
      header('Content-Type: application/json');
      if(is_object($this->form)){
        $json = $this->form->export_json();
      }
      else{
        http_response_code(404);
        $json = '{"status":"Not Found","code":404}';
      }
      echo $json;
      exit;
    }
  }

  /**
   * Runs while inside the template, usually rendering some HTML
   */
  public function render(){
    echo '<a href="' . BASE_URI . $this->path->root . '/profile">Profile</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/password">Password</a>&nbsp;&nbsp;' . "\n";
    echo '<a href="' . BASE_URI . $this->path->root . '/logout">Logout</a>&nbsp;&nbsp;' . "\n";
    $user_obj = new \LWT\User($this->session->user_id);
    $admin_path = \LWT\Path::findapp("\\LWT\\Modules\\Init\\Admin");
    $admin_area = new \LWT\Path(-1);
    $admin_area->request($admin_path, $user_obj);
    if (mb_strlen($admin_area->root) > 1 && isset($admin_area->http_status) && $admin_area->http_status == 200){
      echo '<a href="' . BASE_URI . $admin_area->root . '">Administration</a>&nbsp;&nbsp;' . "\n";
    }
    if (in_array($this->pathstring,$this->valid_paths)){
      if (is_object($this->form)){
        $html = $this->form->export_html();
        echo $html;
      }
      else{
        echo "No form found";
      }
    }
    elseif(fnmatch('reset/*',$this->pathstring)){
      if (is_object($this->form)){
        $html = $this->form->export_html();
        echo $html;
      }
      else{
        echo "The reset code is incorrect";
      }
    }
    elseif($this->pathstring == ''){
      echo '<h3>User Authentication Module</h3><p>Please select an option above</p>';
    }
    else{
      echo "<h3>User Authentication Module<h3>\n";
      echo "<p>The form is not found, please try an option above</p>";
    }
  }
}

