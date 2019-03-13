<?php
namespace LWT\Modules\Test;
/**
 * Test Db Class
 *
 * Testing for the Db class
 *
 * @category Unit Testing
 * @package LibreWebTools
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * @copyright Copyright (c) 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @version Release: @package_version@
 */
class Db extends Tester{
  public function run(){
    echo "starting tests for Db Class\n";
    $db = new \LWT\Db();
    $q = (object)[
      'command' => 'select',
      'table' => 'test',
      'fields' => [],
      'where' => (object)[
        'type' => 'and', 'items' => [
          (object) ['type' => '%', 'value' => 'Mi%', 'id' => 'name', 'cs' => false],
          (object) [
            'type' => 'or', 'items' => [
              (object) ['type' => '>', 'value' => 14, 'id' => 'id'],
              (object) ['type' => '<', 'value' => 7, 'id' => 'id']
            ]
          ],
          (object) ['type' => '<>', 'value' => null, 'id' => 'name']
        ]
      ]
    ];
    $sql = $db->build_sql($q);
    echo "$sql\n";
    $db->fetch_raw($sql);
    var_dump($db);
    $q->sort = [
      (object) ['id' => 'name', 'cs' => false],
      (object) ['id' => 'id', 'dir' => 'd' ]
    ];
    $sql = $db->build_sql($q);
    echo "$sql\n";
    $q->group = [
      (object) ['id' => 'id'],
      (object) ['id' => 'name', 'cs' => false],
    ];
    $sql = $db->build_sql($q);
    echo "$sql\n";
    $q->fields = ['id', 'name'];
    $sql = $db->build_sql($q);
    echo "$sql\n";
    $q->fields = [(object) ['id' => 'id'],(object)['id' => 'name']];
    $sql = $db->build_sql($q);
    echo "$sql\n";

    $q = (object)[
      'command' => 'insert',
      'table' => 'test',
      'inputs' => (object)['id' => 99, 'name' => 'Hello There']
    ];
    $sql = $db->build_sql($q);
    echo "$sql\n";
    $q->command = 'update';
    $q->where = (object)[
      'type' => 'and', 'items' => [
        (object) ['type' => '=', 'id' => 'id', 'value' => 9]
      ]
    ];
    $sql = $db->build_sql($q);
    echo "$sql\n";
    exit;
  }
}
