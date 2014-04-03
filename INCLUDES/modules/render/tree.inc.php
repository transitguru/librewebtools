<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Functions that recursively render trees of some sort
 */

/**
 * Renders checklist "Tree" of groups
 * 
 * @param int $parent_id ID of parent group
 * @param array $user_groups groups that are already selected
 * @param string $prefix Prefix for checkbox names
 * 
 * @return void
 */

function lwt_admin_render_groupcheckbox($parent_id, $user_groups, $prefix = 'user'){
  $groups = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('parent_id' => $parent_id));
  if (count($groups)>0){
?>
  <ul>
<?php
    foreach ($groups as $group){
      if ($group['group_id']>0){
        $info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => $group['group_id']));
?>
    <li><input type="checkbox" id="group_<?php echo $info[0]['id']; ?>" name="<?php echo $prefix; ?>[groups][]" value="<?php echo $info[0]['id']; ?>" <?php if(array_key_exists($info[0]['id'], $user_groups)){echo 'checked';} ?> /><span class="hand" onclick="document.getElementById('group_<?php echo $info[0]['id'];?>').click();" ><?php echo $info[0]['name'];?></span>
<?php
        lwt_admin_render_groupcheckbox($info[0]['id'], $user_groups, $prefix);
      }
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
  return;
}

/**
 * Renders radio "Tree" of groups
 * 
 * @param int $parent_id ID of parent group
 * @param int $selected ID of selected Parent ID
 * @param array $children Simple array of children of group that is being checked
 * 
 * @return void
 */

function lwt_admin_render_groupradio($parent_id, $selected, $children){
  $groups = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('parent_id' => $parent_id));
  if (count($groups)>0){
?>
  <ul>
<?php
    foreach ($groups as $group){
      if ($group['group_id']>0){
        $info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => $group['group_id']));
?>
    <li><input type="radio" id="group_<?php echo $info[0]['id']; ?>" name="group[parent_id]" value="<?php echo $info[0]['id']; ?>" <?php if($info[0]['id'] == $selected){echo 'checked';} ?> <?php if(in_array($info[0]['id'], $children)){echo 'disabled';} ?> /><span class="hand<?php if(in_array($info[0]['id'], $children)){echo ' disabled';} ?>" onclick="document.getElementById('group_<?php echo $info[0]['id'];?>').click();" ><?php echo $info[0]['name'];?></span>
<?php
        lwt_admin_render_groupradio($info[0]['id'], $selected, $children);
      }
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
  return;
}


/**
 * Renders "Tree" structure of groups for understanding of relationships of groups
 * 
 * @param int $parent_id ID of group
 * 
 * @return void
 */
function lwt_admin_render_grouplinks($parent_id){
  $groups = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('parent_id' => $parent_id));
  if (count($groups)>0){
?>
  <ul>
<?php
    foreach ($groups as $group){
      if ($group['group_id']>0){
        $info = lwt_database_fetch_simple(DB_NAME, 'groups', NULL, array('id' => $group['group_id']));
?>
    <li><a href="javascript:;" onclick="ajaxPostLite('command=view&id=<?php echo $info[0]['id']; ?>&ajax=1','','adminarea','');" ><?php echo $info[0]['name'];?></a>
<?php
        lwt_admin_render_grouplinks($info[0]['id']);
      }
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
  return;  
}

/**
 * Renders radio "Tree" of content
 * 
 * @param int $parent_id ID of parent content node
 * @param int $selected ID of selected Parent ID
 * @param array $children Simple array of children of content that is being checked
 * 
 * @return void
 */

function lwt_admin_render_contentradio($parent_id, $selected, $children){
  $contents = lwt_database_fetch_simple(DB_NAME, 'content_hierarchy', NULL, array('parent_id' => $parent_id), NULL, array('url_code'));
  if (count($contents)>0){
?>
  <ul>
<?php
    foreach ($contents as $content){
      if ($content['content_id']>0){
        $info = lwt_database_fetch_simple(DB_NAME, 'content', array('id','title'), array('id' => $content['content_id']));
?>
    <li><input type="radio" id="content_<?php echo $info[0]['id']; ?>" name="content[parent_id]" value="<?php echo $info[0]['id']; ?>" <?php if($info[0]['id'] == $selected){echo 'checked';} ?> <?php if(in_array($info[0]['id'], $children)){echo 'disabled';} ?> /><span class="hand<?php if(in_array($info[0]['id'], $children)){echo ' disabled';} ?>" onclick="document.getElementById('content_<?php echo $info[0]['id'];?>').click();" ><?php echo $content['url_code'];?>/ <?php echo $info[0]['name'];?></span>
<?php
        lwt_admin_render_contentradio($info[0]['id'], $selected, $children);
      }
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
  return;
}


/**
 * Renders "Tree" structure of content for understanding of relationships of content
 * 
 * @param int $parent_id ID of content node
 * 
 * @return void
 */
function lwt_admin_render_contentlinks($parent_id){
  $contents = lwt_database_fetch_simple(DB_NAME, 'content_hierarchy', NULL, array('parent_id' => $parent_id), NULL, array('url_code'));
  if (count($contents)>0){
?>
  <ul>
<?php
    foreach ($contents as $content){
      if ($content['content_id']>0){
        $info = lwt_database_fetch_simple(DB_NAME, 'content', array('id','title'), array('id' => $content['content_id']));
?>
    <li><a href="javascript:;" onclick="ajaxPostLite('command=view&id=<?php echo $info[0]['id']; ?>&ajax=1','','adminarea','');" ><?php echo $content['url_code'];?>/ <?php echo $info[0]['title'];?></a>
<?php
        lwt_admin_render_contentlinks($info[0]['id']);
      }
?>
    </li>
<?php
    }
?>
  </ul>
<?php
  }
  return;  
}

