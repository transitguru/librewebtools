<?php
namespace LWT\Modules\Init;
/**
 * @file
 * LibreWebTools Auth Class
 *
 * Administration and site management
 *
 * @category   Administration
 * @package    LibreWebTools
 * @author     Michael Sypolt <msypolt@transitguru.limited>
 * @copyright  Copyright (c) 2014 - 2019
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    @package_version@
 */
Class Admin Extends \LWT\Subapp{
  /**
   * list of allowable paths that end up rendering or outputting a form
   */
  public $valid_paths = ['user','role','group','path','module','menu','file'];

  /**
   * Runs prior to any HTML output
   */
  public function ajax(){
    // Load the applicable forms
    $directory = DOC_ROOT . '/app/modules/init/config/';
    $form_doc = file_get_contents($directory . 'forms.json');
    $forms = json_decode($form_doc)->admin;
    $this->form = null;

    // Process the path
    $begin = mb_strlen($this->path->root);
    if (mb_strlen($this->inputs->uri) > $begin){
      $this->pathstring = mb_substr($this->inputs->uri, $begin);
    }
    else{
      $this->pathstring = '';
    }

    // Route request based on path
    if ($this->pathstring == 'role'){
      if(isset($this->inputs->post->id) && 
          (!isset($this->inputs->post->submit) || $this->inputs->post->submit != 'Close')){
        $role_id = (int) $this->inputs->post->id;
        $role_obj = new \LWT\Role($role_id);
        $this->form = new \LWT\Form($forms->role);
        if ($role_id == -1){
          $this->form->fields->submit1->value = 'Create';
        }
        $this->form->fields->id->value = $role_obj->id;
        $this->form->fields->name->value = $role_obj->name;
        $this->form->fields->sortorder->value = $role_obj->sortorder;
        $this->form->fields->desc->value = $role_obj->desc;
      }
      else{
        $role_obj = new \LWT\Role(-1);
        $list = $role_obj->list();
        $items = [];
        foreach($list as $r){
          $items[]= (object)[
            'name' => $r->name,
            'value' => $r->id,
          ];
        }
        $items[]= (object)[
          'name' => '(new role)',
          'value' => -1,
        ];
        $defs = (object)[
          'title' => 'User Roles Navigation',
          'desc' => 'Manage roles for major user permissions classes.',
          'name' => 'form_role_nav',
          'fields' => (object)[
            'id' => (object)['name' => 'id', 'element' => 'select',
              'label' => 'Select Role', 'format' => 'text', 'list' => $items,
              'value' => 'Navigate'],
            'submit1' => (object)['name' => 'submit', 'element' => 'submit',
              'label' => '', 'format' => 'text', 'value' => 'Navigate'],
          ],
        ];
        $this->form = new \LWT\Form($defs);
      }
      if (isset($this->inputs->post->submit)){
        if(in_array($this->inputs->post->submit, ['Create','Update'])){
          $this->form->fill($this->inputs->post);
          $this->form->validate();
          if ($this->form->error == 0){
            $role_obj->name = $this->form->fields->name->value;
            $role_obj->sortorder = $this->form->fields->sortorder->value;
            $role_obj->desc = $this->form->fields->desc->value;
            $role_obj->write();
            $this->form->error = $role_obj->error;
            $this->form->message = $role_obj->message;
            if ($role_obj->name_unique == false){
              $this->form->fields->name->error = 99;
              $this->form->fields->name->message = $role_obj->name_message;
            }
            if($this->inputs->post->submit == 'Create'){
              $this->form->fields->id->value = $role_obj->id;
              $this->form->fields->submit1->value = 'Update';
            }
          }
        }
        elseif($this->inputs->post->submit == 'Delete'){
          $this->form->message = 'Are you sure you want to delete this Role?';
          $this->form->status = 'warning';
          $this->form->fields->submit1->value = 'Yes, Delete';
          $this->form->fields->submit2->value = 'No';
        }
        elseif($this->inputs->post->submit == 'Yes, Delete'){
          $role_obj->delete();
          $this->form->message = 'Role deleted successfully';
          $this->form->status = 'success';
          foreach ($this->form->fields as $key => $field){
            if ($key != 'submit3'){
              unset($this->form->fields->{$key});
            }
          }
        }
      }
      else{
        $this->form->message = 'Select a role to begin editing.';
        $this->form->status = 'warning';
      }
    }
    elseif ($this->pathstring == 'group'){
      $this->form = new \LWT\Form($forms->group);
    }
    elseif ($this->pathstring == 'user'){
      $this->form = new \LWT\Form($forms->user);
    }
    elseif ($this->pathstring == 'path'){
      $this->form = new \LWT\Form($forms->path);
    }
    elseif ($this->pathstring == 'module'){
      $this->form = new \LWT\Form($forms->module);
    }
    elseif ($this->pathstring == 'menu'){
      $this->form = new \LWT\Form($forms->menu);
    }
    elseif ($this->pathstring == 'file'){
      $this->form = new \LWT\Form($forms->file);
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
    echo '<a href="' . BASE_URI . $this->path->root . 'user">Users</a>&nbsp;&nbsp;';
    echo '<a href="' . BASE_URI . $this->path->root . 'role">Roles</a>&nbsp;&nbsp;';
    echo '<a href="' . BASE_URI . $this->path->root . 'group">Groups</a>&nbsp;&nbsp;';
    echo '<a href="' . BASE_URI . $this->path->root . 'path">Paths</a>&nbsp;&nbsp;';
    echo '<a href="' . BASE_URI . $this->path->root . 'module">Modules</a>&nbsp;&nbsp;';
    echo '<a href="' . BASE_URI . $this->path->root . 'menu">Menus</a>&nbsp;&nbsp;';
    echo '<a href="' . BASE_URI . $this->path->root . 'file">Files</a>&nbsp;&nbsp;';
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
      echo '<h3>Administration Module</h3><p>Please select an option above</p>';
    }
    else{
      $this->render_404();
    }
  }
}

