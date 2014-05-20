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
 * @param array $glossary
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Glossary Items</legend>
<h2>Glossary Items</h2>
<p><?php echo anchor($controller . '/iu/model/glossary', 'Create a new Glossary Item', array('class'=>'admincreate')); ?></p>
<?php echo $flash; ?>
<table class="listitemstable">
<thead>
<tr>
<th>Id</th>
<th>Term</th>
<th>Definition</th>
<th>Edit</th>
<th>Delete</th>
</tr>
</thead>
<tbody>
<?php
foreach ($glossary as $p) {
    if($p['deleted'] == 1 && ! User::hasPermission(User::VIEW_DELETED))
        continue;
    echo "<tr>\n";
    echo "<td valign='top'>" . $p['glossary_id'] . "</td>\n";
    echo "<td valign='top'>" . e($p['term']) . "</td>\n";
    echo "<td>" . dexss(strip_tags($p['definition'])) . "</td>\n";
    echo "<td>" . anchor($controller . '/iu/model/glossary/row_id/' . $p['glossary_id'], 'Edit', array('class'=>'adminedit')) . "</td>\n";
    echo "<td>";
    if($p['deleted'] == 0)
        echo anchor('delete/model/glossary/item_id/' . $p['glossary_id'], 'Delete', array('class'=>'admindelete'));
    else
        echo anchor($controller . '/undelete/glossary/' . $p['glossary_id'], 'Undelete', array('class'=>'adminundelete'));
    echo "</td>\n";
    echo "</tr>\n";
}
?>
</tbody>
</table>
</fieldset>
