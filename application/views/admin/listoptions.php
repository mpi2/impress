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
 * @param Parameter $parameter The Parameter object which may contain options
 * @param int $procedureId
 * @param int $pipelineId
 * @param string $flash
 * @param string $controller
 */
?>


<fieldset><legend>Options</legend>

<h2>Options for Parameter: <?php echo anchor($controller . '/parameter/' . $procedureId . '/' . $pipelineId, trim(e($parameter->getItemName()))); ?> 
<span class="parameterkey"><?php echo $parameter->getItemKey(); ?></span></h2>

<p><?php 
echo anchor(
    $controller . '/iu/model/paramoption/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
    'Create a new Option Item', 
    array('class'=>'admincreate')
);
?>
 | <?php echo anchor($controller . '/itemRelationship/paramoption/' . $pipelineId . '/' . $procedureId . '/' . $parameter->getId(), 'Manage Option Relationships', array('class'=>'adminimport')); ?>
 | <?php echo anchor($controller . '/recyclebin/paramoption/' . $pipelineId . '/' . $procedureId . '/' . $parameter->getId(), 'Option Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php echo $flash; ?>

<?php
$options = $parameter->getOptions();
if ( ! empty($options)):
?>
<table class="listitemstable">
<thead>
<tr>
<th>Id</th>
<th>Name</th>
<th>Description</th>
<th>Parent Option Name</th>
<th>Is Default Option</th>
<th>Is Active</th>
<th>Edit</th>
<th>Delete</th>
<th>Move 
<?php echo anchor($controller . '/resequence/paramoption/' . $parameter->getId(), '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
</th>
</tr>
</thead>
<tbody>
<?php
foreach ($options as $op) {
    if($op->isDeleted() && ! User::hasPermission(User::VIEW_DELETED))
        continue;
    echo '<tr>';
    echo '<td>' . $op->getId() . '</td>';
    echo '<td>' . e($op->getName()) . '</td>';
    echo '<td>' . e($op->getDescription()) . '</td>';
    echo '<td>' . (($op->getParent()->getName() != $op->getName()) ? e($op->getParent()->getName()) : '') . '</td>';
    echo '<td>' . tick_or_cross($op->isDefault()) . '</td>';
    if($op->isDeleted() && $op->isActive())
        echo '<td>-</td>';
    else
        echo '<td>' . tick_or_cross($op->isActive()) . '</td>';
    echo '<td>';
    echo anchor(
        $controller . '/iu/model/paramoption/row_id/' . $op->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(), 
        ' Edit', 
        array('class'=>'adminedit')
    );
    echo '</td>';
    if ($op->isDeleted()) {
        echo '<td>';
        echo anchor(
            $controller . '/undelete/paramoption/' . $op->getId() . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            ' Undelete', 
            array('class'=>'adminundelete')
        );
        echo '</td>';
    } else {
        echo '<td>';
        echo anchor(
            'delete/model/paramoption/item_id/' . $op->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
            ' Delete', 
            array('class'=>'admindelete')
        );
        echo '</td>';
    }
    echo '<td>';
    echo anchor($controller . '/move/up/paramoption/' . $op->getId() . '/' . $parameter->getId(), '<img border="0" src="' . base_url() . 'images/up.png' . '">');
    echo anchor($controller . '/move/dn/paramoption/' . $op->getId() . '/' . $parameter->getId(), '<img border="0" src="' . base_url() . 'images/dn.png' . '">');
    echo '</td></tr>';
}
?>
</tbody>
</table>
<?php 
endif;
?>
</fieldset>
