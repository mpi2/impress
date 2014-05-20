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
 * @param array $sectionTitles
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Section Titles</legend>
<h2>Protocol Section Titles</h2>
<p><?php echo anchor($controller . '/iu/model/sectiontitle', 'Create a new title', array('class'=>'admincreate')); ?></p>

<?php echo $flash; ?>

<table class="listitemstable" >
<thead>
<tr>
<th>Id</th>
<th>Title</th>
<th>Edit</th>
<th>Delete</th>
<th>Move 
<?php echo anchor($controller . '/resequence/sectiontitle', '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
</th>
</tr>
</thead>
<tbody>
<?php
//@todo
foreach ($sectionTitles as $i) {
    echo '<tr>';
    echo '<td>' . $i['id'] . '</td>';
    echo '<td>' . e($i['title']) . '</td>';
    echo '<td>' . anchor($controller . '/iu/model/sectiontitle/row_id/' . $i['id'], 'Edit', array('class'=>'adminedit')) . '</td>';
    echo '<td>' . anchor('delete/model/sectiontitle/item_id/' . $i['id'], 'Delete', array('class'=>'admindelete')) . '</td>';
    echo '<td>';
    echo anchor($controller . '/move/up/sectiontitle/' . $i['id'], '<img border="0" src="' . base_url() . 'images/up.png' . '">');
    echo anchor($controller . '/move/dn/sectiontitle/' . $i['id'], '<img border="0" src="' . base_url() . 'images/dn.png' . '">');
    echo '</td>';
    echo "</tr>\n";
}
?>
</tbody>
</table>
</fieldset>
