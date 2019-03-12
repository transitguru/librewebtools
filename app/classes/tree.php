<?php
namespace LWT;
/**
 * @file
 * Tree Class
 *
 * provides a way to get tree information (there are inherited classes)
 *
 * @category Data Abstraction
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2019
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Tree{
  public $table = '';  /**< DB Table name, must be set by inherited object */
  public $id = 0;

  /**
   * Provides IDs for items that are traversable in the tree
   *
   * See the theoretical tree below to get an understanding on how it works:
   *
   *                              0
   *                             / \
   *                            1   2
   *                           / \   \
   *                          3   4   8
   *                         / \   \
   *                        5   6   7
   *
   * Say the input $id is 3 and an empty array. As designed, the
   * method would return [3,5,6,1,0] because it first gets all descendents of
   * the subject tree id number, then gets direct ancestors. No siblings or
   * ancestor's siblings (a.k.a. "aunts/uncles") would be returned.
   *
   * The current assumptions are the following:
   *  - the 'id' is the ID, and the 'parent_id' is Parent ID in the database
   *  - Both fields are in the same table, and both fields are any INT() type
   *  - the root ID of the tree is 0
   *  - the parent ID of the root item is NULL
   *  - the key for parent ID is named 'parent_id'
   *
   * @param array $ids Array of ids already found from previous iterations
   *
   * @return array All IDs that are above or below the item in question
   */
  public function traverse($ids = array()){
    $id = $this->id;
    if ($id == NULL){
      return $ids;
    }

    //find children
    $ids = $this->children($id, $ids);

    //find parents until we reach root
    $search = $id;
    $loop = true;
    $db = new Db();
    while($loop){
      $q = (object)[
        'command' => 'select',
        'table' => $this->table,
        'fields' => [],
        'where' => (object)[
          'type' => 'and', 'items' => [
            (object)['type' => '=', 'value' => $search, 'id' => 'id']
          ]
        ]
      ];
      $db->query($q);
      if ($db->output[0]->parent_id == 0){
        $loop = false;
        $ids[0] = 0;
      }
      else{
        $ids[$search] = $search = $db->output[0]->parent_id;
      }
    }
    return $ids;
  }

  /**
   * Finds children to the group IDs for a given parent (including the parent)
   *
   * @param int $parent Parent ID to find the children
   * @param array $ids Array of IDs that are available to keep appending
   * @return array Array of IDs (this gets appended to the input)
   */
  public function children($parent, $ids){
    $ids[$parent] = $parent;
    $db = new Db();
    $q = (object)[
      'command' => 'select',
      'table' => $this->table,
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object)['type' => '=', 'value' => $parent, 'id' => 'parent_id']
        ]
      ]
    ];
    $db->query($q);
    if ($db->affected_rows > 0){
      foreach ($db->output as $child){
        $ids = $this->children($child->id,$ids);
      }
    }
    return $ids;
  }
}

