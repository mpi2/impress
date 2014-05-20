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
 * @param array $procweeks
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Procedure Weeks</legend>
<h2>Procedure Weeks</h2>
<p><?php echo anchor($controller . '/iu/model/procedureweek', 'Create a new Procedure Week', array('class'=>'admincreate')); ?></p>
<?php echo $flash; ?>
<table class="listitemstable">
<thead>
<tr>
<th>Id</th>
<th>Week Label</th>
<th>Week Number</th>
<th>Week Stage</th>
<th>Edit</th>
<th>Delete</th>
<th>Move
<?php echo anchor($controller . '/resequence/procedureweek', '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
</th>
</tr>
</thead>
<tbody>
<?php
foreach ($procweeks as $p) {
    echo "<tr>\n";
    echo "<td>" . $p->getId() . "</td>\n";
    echo "<td>" . e($p->getLabel()) . "</td>\n";
    echo "<td>" . $p->getWeekNumber() . "</td>\n";
    echo "<td>" . $p->getStageLabel() . "</td>\n";
    echo "<td>" . anchor($controller . '/iu/model/procedureweek/row_id/' . $p->getId(), 'Edit', array('class'=>'adminedit')) . "</td>\n";
    echo "<td>" . anchor('delete/model/procedureweek/item_id/' . $p->getId(), 'Delete', array('class'=>'admindelete')) . "</td>\n";
    echo "<td>" . anchor($controller . '/move/up/procedureweek/' . $p->getId(), '<img border="0" src="' . base_url() . 'images/up.png">') . "\n";
    echo anchor($controller . '/move/dn/procedureweek/' . $p->getId(), '<img border="0" src="' . base_url() . 'images/dn.png' . '">') . "</td>\n";
    echo "</tr>\n";
}
?>
</tbody>
</table>
</fieldset>
