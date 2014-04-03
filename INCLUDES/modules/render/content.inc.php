<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders content editing pages that are usually only seen by an admin
 */

/**
 * Processes any AJAX request for the Content application
 * 
 * @param boolean $wrapper Optional field designated that it was called from the wrapper 
 * 
 * @return void
 * 
 */

function lwt_ajax_admin_content($wrapper = false){
  if (!$wrapper){
    header('Cache-Control: no-cache');
  }
  if (isset($_POST['ajax']) && $_POST['ajax'] == 1){
    
    //Define Navigation levels if navigating
    $nullify = FALSE;
    for($i=0; $i <= 10; $i++){
      if ($nullify){
        $_SESSION['admin']['navigate'][$i] = NULL;
      }
      if(isset($_POST['navigate'][$i]) && $_POST['navigate'][$i] != ''){
        if($i==0){
          $_SESSION['admin']['navigate'] = array();
        }
        $_SESSION['admin']['navigate'][$i] = $_POST['navigate'][$i];
        $nullify = TRUE;
      }
    }
    
    //Process write events
    if (isset($_POST['command']) && $_POST['command'] == 'write'){
      if (isset($_POST['content']) && count($_POST['content'])>0){
        $inputs = array();
        if ($_POST['content']['id'] == -1){
          $where = NULL;
        }
        else{
          $where = array('id' => $_POST['content']['id']);
        }
        $inputs['preprocess_call'] = $_POST['content']['preprocess_call'];
        $inputs['title'] = $_POST['content']['title'];
        $inputs['function_call'] = $_POST['content']['function_call'];
        $inputs['summary'] = $_POST['content']['summary'];
        $inputs['content'] = $_POST['content']['content'];
        
        $result = lwt_database_write(DB_NAME, 'content', $inputs, $where);
        if ($result['error']){
          $_POST['id'] = $_POST['content']['id'];
          $message = $result['message'];
        }
        elseif($_POST['content']['id'] == -1){
          $id = $result['insert_id'];
        }
        else{
          $id = $_POST['content']['id'];
        }
        // Apply content hierarchy
        
        if (!$result['error']){
          if ($_POST['content']['id'] == -1){
            $parent_id = 0;
            $url_code = 'newpage';
            if (isset($_POST['content']['parent_id']) && $_POST['content']['parent_id'] != ''){
              $parent_id = $_POST['content']['parent_id'];
            }
            if (isset($_POST['content']['url_code']) && $_POST['content']['url_code'] != ''){
              $url_code = $_POST['content']['url_code'];
            }
            $hierarchy = array(
              'content_id' => $id,
              'parent_id' => $parent_id,
              'url_code' => $url_code,
            );
            $result = lwt_database_write(DB_NAME, 'content_hierarchy', $hierarchy, NULL);
          }
          else{
            $hierarchy = array(
              'parent_id' => $_POST['content']['parent_id'],
              'url_code' => $_POST['content']['url_code'],
            );
            $children = array();
            $children = lwt_process_get_contentchildren($id,$children);
            $parent = lwt_database_fetch_simple(DB_NAME, 'content_hierarchy', NULL, array('content_id' => $id));
            $selected = $parent[0]['parent_id'];
            if (in_array($parent_id, $children) || $parent_id === $selected){
              unset($hierarchy['parent_id']);
            }
            if ($id >0){
              $result = lwt_database_write(DB_NAME, 'content_hierarchy', $hierarchy, array('content_id' => $id));
            }
          }
          
          // Add roles
          $content_roles = lwt_database_fetch_simple(DB_NAME, 'role_access', NULL, array('content_id' => $id), NULL, NULL, 'role_id');
          $chosen_roles = $_POST['content']['roles'];
          $roles = lwt_database_fetch_simple(DB_NAME, 'roles');
          foreach ($roles as $role){
            // Add new
            if (in_array($role['id'],$chosen_roles) && !array_key_exists($role['id'],$content_roles)){
              $sql = "INSERT INTO `role_access` (`content_id`,`role_id`) VALUES ({$id},{$role['id']})";
              $result = lwt_database_write_raw(DB_NAME, $sql);
            }
            // Delete old
            elseif(!in_array($role['id'],$chosen_roles) && array_key_exists($role['id'],$content_roles)){
              $sql = "DELETE FROM `role_access` WHERE `content_id`={$id} AND `role_id`={$role['id']}";
              $result = lwt_database_write_raw(DB_NAME, $sql);
            }
            
            if ($result['error']){
              $message = $result['message'];
            }
          }

          //Add groups
          $content_groups = lwt_database_fetch_simple(DB_NAME, 'group_access', NULL, array('content_id' => $id), NULL, NULL, 'group_id');
          $chosen_groups = $_POST['content']['groups'];
          $groups = lwt_database_fetch_simple(DB_NAME, 'groups');
          foreach ($groups as $group){
            // Add new
            if (in_array($group['id'],$chosen_groups) && !array_key_exists($group['id'],$content_groups)){
              $sql = "INSERT INTO `group_access` (`content_id`,`group_id`) VALUES ({$id},{$group['id']})";
              $result = lwt_database_write_raw(DB_NAME, $sql);
            }
            // Delete old
            elseif(!in_array($group['id'],$chosen_groups) && array_key_exists($group['id'],$content_groups)){
              $sql = "DELETE FROM `group_access` WHERE `content_id`={$id} AND `group_id`={$group['id']}";
              $result = lwt_database_write_raw(DB_NAME, $sql);
            }
          }
        }
        if ($result['error']){
          $_POST['id'] = $_POST['content']['id'];
          $message = $result['message'];
        }
        
      }
    }
    
    //Process delete events
    if (isset($_POST['command']) && $_POST['command'] == 'delete'){
      if (isset($_POST['content']) && count($_POST['content'])>0){
        if ($_POST['content']['id']>0){
          $children = array();
          $children = lwt_process_get_contentchildren($_POST['content']['id'],$children);
          if (count($children)>1){
            $result['error'] = true;
            $result['message'] = '<span class="error">You cannot remove content with nested content!</span>';
          }
          else{
            $sql = "DELETE FROM `content` WHERE `id`={$_POST['content']['id']}";
            $result = lwt_database_write_raw(DB_NAME, $sql);
          }
        }
        else{
          $result['error'] = true;
          $result['message'] = '<span class="error">You cannot remove the homepage!</span>';
        }
        if ($result['error']){
          $_POST['id'] = $_POST['content']['id'];
        }
      }
      $_POST['command'] = 'write';
    }

    
    //Render the navigation area if navigating or no error
    if (isset($_POST['command']) && ($_POST['command'] == 'navigate' || ($_POST['command'] == 'write' && !$result['error']))){
      $contents = lwt_database_fetch_simple(DB_NAME, 'content', array('id','title'));
      $num = count($contents);
?>
        <h2><a href="javascript:;" onclick="ajaxPostLite('command=navigate&navigate[0]=contents&ajax=1','','adminarea','');">Content (<?php echo $num; ?>)</a></h2>
<?php
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'contents'){
?>
          <ul>
<?php
$info = lwt_database_fetch_simple(DB_NAME, 'content', array('id','title'), array('id' => 0));
?>
            <li><a href="javascript:;" onclick="ajaxPostLite('command=view&id=<?php echo $info[0]['id']; ?>&ajax=1','','adminarea','');" >/ <?php echo $info[0]['title'];?></a>
<?php
        lwt_admin_render_contentlinks(0);
?>
            </li>
            <li><a href="javascript:;" onclick="hideTooltip(event);ajaxPostLite('command=view&id=-1&ajax=1','','adminarea','');" onmousemove="showTooltip(event,'Add New Content');" onmouseout="hideTooltip(event);">[+]</a></li>
          </ul>
<?php
      }
    }
    
    //Render editing interfaces if requested or error upon write
    elseif (isset($_POST['command']) && ($_POST['command'] == 'view' || ($_POST['command'] == 'write' && $result['error']))){
      // Content editing area
      if (isset($_SESSION['admin']['navigate'][0]) && $_SESSION['admin']['navigate'][0] === 'contents'){
        if ($_POST['id'] == -1 && !isset($inputs)){
          $content = array(
            'id' => -1,
            'preprocess_call' => '',
            'title' => '',
            'function_call' => '',
            'summary' => '',
            'content' => '',
          );
        }
        elseif($_POST['id'] == -1){
          $content['id'] = $_POST['id'];
          $content['preprocess_call'] = $inputs['preprocess_call'];
          $content['title'] = $inputs['title'];
          $content['fuction_call'] = $inputs['function_call'];
          $content['summary'] = $inputs['summary'];
          $content['content'] = $inputs['content'];
        }
        else{
          $contents = lwt_database_fetch_simple(DB_NAME, 'content', NULL, array('id' => $_POST['id']));
          $content = $contents[0];
        }
        if ($result['error']){
          $message = $result['message'];
        }
        else{
          $message = '<span class="warning">Please enter data in the form</span>';
        }
        echo $message;
?>
      <form action="" enctype="multipart/form-data" method="post" id="poster" onsubmit="event.preventDefault(); ajaxPost(this,'adminarea','');">
        <h2>Content information</h2>
        <input type="hidden" name="command" value="write" />
        <input type="hidden" name="ajax" value="1" />
        <input type="hidden" name="content[id]" value="<?php echo $content['id']; ?>" />
        <label for="content[title]">Title</label><input type="text" name="content[title]" value="<?php echo $content['title']; ?>" /><br />
        <label for="content[preprocess_call]">Pre-Process Function</label><input type="text" name="content[preprocess_call]" value="<?php echo $content['preprocess_call']; ?>" /><br />
        <label for="content[function_call]">Function Call</label><input type="text" name="content[function_call]" value="<?php echo $content['function_call']; ?>" /><br />
        <label for="content[summary]">Summary</label><br class="clear" /><textarea style="width: 90%; height:200px;" name="content[summary]"><?php echo htmlentities($content['summary']); ?></textarea><br />
        <label for="content[content]">Body Text</label><br class="clear" /><textarea style="width: 90%; height:500px;" name="content[content]"><?php echo htmlentities($content['content']); ?></textarea><br class="clear" />
        <h2>Hierarchy within Site</h2>
        <label for="content[parent_id]">Select Parent</label><br class="clear" />
<?php
        $children = array();
        $children = lwt_process_get_contentchildren($content['id'],$children);
        $parent = lwt_database_fetch_simple(DB_NAME, 'content_hierarchy', NULL, array('content_id' => $content['id']));
        $selected = $parent[0]['parent_id'];
        $info = lwt_database_fetch_simple(DB_NAME, 'content', array('id', 'title'), array('id' => $content['content_id']));
?>
        <ul>
        <li><input type="radio" id="content_<?php echo $info[0]['id']; ?>" name="content[parent_id]" value="<?php echo $info[0]['id']; ?>" <?php if($info[0]['id'] == $selected){echo 'checked';} ?> <?php if(in_array($info[0]['id'], $children)){echo 'disabled';} ?> /><span class="hand<?php if(in_array($info[0]['id'], $children)){echo ' disabled';} ?>" onclick="document.getElementById('content_<?php echo $info[0]['id'];?>').click();" >/: <?php echo $info[0]['name'];?></span>
<?php
        lwt_admin_render_contentradio(0, $selected, $children);
?>
        </ul>
        <label for="content[url_code]">URL Alias (no slashes)</label><input type="text" name="content[url_code]" value="<?php echo $parent[0]['url_code']; ?>" /><br class="clear" />
        <h2>Roles</h2>
        <ul>
<?php
        $roles = lwt_database_fetch_simple(DB_NAME, 'roles', NULL, NULL, NULL, array('sortorder', 'id'));
        $content_roles = lwt_database_fetch_simple(DB_NAME, 'role_access', NULL, array('content_id' => $content['id']), NULL, NULL, 'role_id');
        foreach ($roles as $role){
?>
          <li><input type="checkbox" id="role_<?php echo $content['id'];?>" name="content[roles][]" value="<?php echo $role['id']; ?>" <?php if(array_key_exists($role['id'], $content_roles)){echo 'checked';} ?> /><span class="hand" onclick="document.getElementById('role_<?php echo $role['id'];?>').click();" ><?php echo $role['name']; ?></span></li>
<?php
        }
?>
        </ul>
        <h2>Groups</h2>
        <ul>
<?php
        $content_groups = lwt_database_fetch_simple(DB_NAME, 'group_access', NULL, array('content_id' => $content['id']), NULL, NULL, 'group_id');
        $info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => 0));
?>
          <li><input type="checkbox" id="group_<?php echo $info[0]['id']; ?>" name="content[groups][]" value="<?php echo $info[0]['id']; ?>" <?php if(array_key_exists($info[0]['id'], $content_groups)){echo 'checked';} ?> /><span class="hand" onclick="document.getElementById('group_<?php echo $info[0]['id'];?>').click();" ><?php echo $info[0]['name'];?></span>
        <?php lwt_admin_render_groupcheckbox(0, $content_groups, 'content'); ?>
          </li>
        </ul>
        <input type="submit" name="submit" value="Update" />
      </form>
      <button onclick="event.preventDefault();ajaxPostLite('command=view&id=<?php echo $content['id']; ?>&ajax=1','','adminarea','');">Reset form</button>
      <button onclick="event.preventDefault();ajaxPostLite('command=navigate&navigate[0]=contents&ajax=1','','adminarea','');">Cancel</button>
<?php
        if ($group['id'] > 0){
?>
      <button class="right" onclick="event.preventDefault();ajaxPostLite('command=delete&content[id]=<?php echo $content['id']; ?>&ajax=1','','adminarea','');">Delete</button>
<?php
        }
      }
    }
    
    //exit if this was not a wrapper function
    if (!$wrapper){
      exit;
    }
  }
}

/**
 * Renders the Admin content page when loading the site within wrapper
 * 
 * 
 * @return boolean Successful rendering of page
 * 
 */

function lwt_render_admin_content(){
  //Reset admin navigation if POST not set
  if (!isset($_POST) || count($_POST)==0){
    $_SESSION['admin']['navigate'] = array();
    $_POST['ajax'] = 1;
    $_POST['command'] = 'navigate';
  }
  
  //Render application in preparation for making ajax content
?>
  <div id="adminarea">
<?php lwt_ajax_admin_content(true); ?>
  </div>
<?php
  return TRUE;
}

