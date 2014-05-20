<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 *
 * Copyright 2014 Medical Research Council Harwell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

/**
 * @param array $proctypes
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Procedure Types</legend>
<h2>Procedure Types</h2>
<p><?php echo anchor($controller . '/iu/model/proceduretype', 'Create a new Procedure Type', array('class'=>'admincreate')); ?></p>
<?php echo $flash; ?>
<table class="listitemstable">
<thead>
<tr>
<th>Id</th>
<th>Type</th>
<th>Key</th>
<th>Number</th>
<th>Edit</th>
<th>Delete</th>
</tr>
</thead>
<tbody>
<?php
foreach ($proctypes as $p) {
    echo "<tr>\n";
    echo "<td>" . $p['id'] . "</td>\n";
    echo "<td>" . e($p['type']) . "</td>\n";
    echo "<td>" . $p['key'] . "</td>\n";
    echo "<td>" . $p['num'] . "</td>\n";
    echo "<td>" . anchor($controller . '/iu/model/proceduretype/row_id/' . $p['id'], 'Edit', array('class'=>'adminedit')) . "</td>\n";
    echo "<td>" . anchor('delete/model/proceduretype/item_id/' . $p['id'], 'Delete', array('class'=>'admindelete')) . "</td>\n";
    echo "</tr>\n";
}
?>
</tbody>
</table>
</fieldset>
