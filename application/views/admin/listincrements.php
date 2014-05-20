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
 * @param Parameter $parameter The Parameter object which may contain increments
 * @param int $procedureId
 * @param int $pipelineId
 * @param string flash
 * @param string $controller
 */
?>

<fieldset><legend>Increments</legend>

<h2>Increments for Parameter: <?php echo anchor($controller . '/parameter/' . $procedureId . '/' . $pipelineId, e($parameter->getItemName())); ?> 
<span class="parameterkey"><?php echo $parameter->getItemKey(); ?></span></h2>

<p><?php
echo anchor(
    $controller . '/iu/model/paramincrement/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
    'Create a new Increment Item',
    array('class'=>'admincreate')
); ?>
 | <?php echo anchor($controller . '/recyclebin/paramincrement/' . $pipelineId . '/' . $procedureId . '/' . $parameter->getId(), 'Increment Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php echo $flash; ?>

<?php
$incs = $parameter->getIncrements();
if ( ! empty($incs)):
?>
<table class="listitemstable">
<thead>
<tr>
<th>Id</th>
<th>Increment&nbsp;String</th>
<th>Increment&nbsp;Min.</th>
<th>Unit</th>
<th>Type</th>
<th>Active</th>
<th>Edit</th>
<th>Delete</th>
<th>Move 
<?php echo anchor($controller . '/resequence/paramincrement/' . $parameter->getId(), '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
</th>
</tr>
</thead>
<tbody>
<?php
foreach ($incs as $inc) {
    if($inc->isDeleted() && ! User::hasPermission(User::VIEW_DELETED))
        continue;
    echo '<tr>';
    echo '<td>' . $inc->getId() . '</td>';
    echo '<td>' . e($inc->getIncrementString()) . '</td>';
    echo '<td>' . $inc->getIncrementMin() . '</td>';
    echo '<td>' . ucfirst($inc->getIncrementUnit()) . '</td>';
    echo '<td>' . ucfirst($inc->getIncrementType()) . '</td>';
    if($inc->isDeleted() && $inc->isActive())
        echo '<td>-</td>';
    else
        echo '<td>' . tick_or_cross($inc->isActive()) . '</td>';
    echo '<td>';
    echo anchor(
        $controller . '/iu/model/paramincrement/row_id/' . $inc->getId() . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
        ' Edit',
        array('class'=>'adminedit')
    );
    echo '</td>';
    if ($inc->isDeleted()) {
        echo '<td>';
        echo anchor(
            $controller . '/undelete/paramincrement/' . $inc->getId() . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            ' Undelete',
            array('class'=>'adminundelete')
        );
        echo '</td>';
    } else {
        echo '<td>';
        echo anchor(
            'delete/model/paramincrement/item_id/' . $inc->getId() . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            ' Delete',
            array('class'=>'admindelete')
        );
        echo '</td>';
    }
    echo '<td>';
    echo anchor($controller . '/move/up/paramincrement/' . $inc->getId() . '/' . $parameter->getId(), '<img border="0" src="' . base_url() . 'images/up.png' . '">');
    echo anchor($controller . '/move/dn/paramincrement/' . $inc->getId() . '/' . $parameter->getId(), '<img border="0" src="' . base_url() . 'images/dn.png' . '">');
    echo '</td>';
    echo '</tr>';
}
?>
</tbody>
</table>
<?php
endif;
?>
</fieldset>
