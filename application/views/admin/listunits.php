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
 * @param array $units
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Parameter Units</legend>
<h2>Parameter Units</h2>
<p><?php echo anchor($controller . '/iu/model/unit', 'Create a new Parameter Unit', array('class'=>'admincreate')); ?></p>
<?php echo $flash; ?>
<table class="listitemstable">
<tr>
<th>Id</th>
<th>Unit</th>
<th>Frequency</th>
<th>Edit</th>
<th>Delete</th>
</tr>
<?php
foreach ($units as $unit) {
    echo "<tr>\n";
    echo "<td>" . $unit[UnitModel::PRIMARY_KEY] . "</td>\n";
    echo "<td>" . e($unit['unit']) . "</td>\n";
    echo "<td>" . $unit['freq'] . "</td>\n";
    //Record 0 is required in the table; its' unit is NULL for parameters with no unit required so delete and edit links not displayed
    if ($unit[UnitModel::PRIMARY_KEY] == 0) {
        echo "<td></td><td></td>\n";
    } else {
        echo "<td>" . anchor($controller . '/iu/model/unit/row_id/' . $unit[UnitModel::PRIMARY_KEY], 'Edit', array('class'=>'adminedit')) . "</td>\n";
        echo "<td>" . anchor('delete/model/unit/item_id/' . $unit[UnitModel::PRIMARY_KEY], 'Delete', array('class'=>'admindelete')) . "</td>\n";
    }
    echo "</tr>\n";
}
?>
</table>
</fieldset>
