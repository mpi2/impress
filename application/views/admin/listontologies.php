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
 * @param Parameter $parameter The Parameter object which may contain ontologies
 * @param int $procedureId
 * @param int $pipelineId
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Ontologies</legend>

<h2>Ontologies for Parameter: <?php echo anchor($controller . '/parameter/' . $procedureId . '/' . $pipelineId, e($parameter->getItemName())); ?> 
<span class="parameterkey"><?php echo $parameter->getItemKey(); ?></span></h2>

<?php echo $flash; ?>

<?php
//extract ontologies
$mpterms = array();
foreach($parameter->getOntology()->getMPTerms() as $mp){
    $mpterms[] = array(
        'ontology_id' => $mp->getId(),
        'option'      => $mp->getOption(),
        'increment'   => $mp->getIncrement(),
        'sex'         => $mp->getSex(),
        'selection_outcome' => $mp->getSelectionOutcome(),
        'id'          => $mp->getId(),
        'mp_id'       => $mp->getMPId(),
        'mp_term'     => $mp->getMPTerm(),
        'mp_deleted'  => $mp->isDeleted()
    );
}
$eqterms = array();
foreach($parameter->getOntology()->getEQTerms() as $eq){
    $eqterms[] = array_merge(
        array(
            'ontology_id' => $eq->getId(),
            'option'      => $eq->getOption(),
            'increment'   => $eq->getIncrement(),
            'sex'         => $eq->getSex(),
            'selection_outcome' => $eq->getSelectionOutcome(),
            'id'          => $eq->getId(),
            'eq_deleted'  => $eq->isDeleted()
        ),
        $eq->getEQs()
    );
}

$sorter = new Array_Sorter($eqterms, 'selection_outcome');
$eqterms = $sorter->sortit();
?>

<h3>Basic Ontology Terms</h3>

<p>
<?php echo anchor($controller . '/iu/model/parammpterm/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId, 'Create a new MP/MA/EMAP Term', array('class'=>'admincreate')); ?>
 | <?php echo anchor($controller . '/recyclebin/parammpterm/' . $pipelineId . '/' . $procedureId . '/' . $parameter->getId(), 'Basic Ontology Term Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php
if (empty($mpterms)):
?>

<p>There are no Basic Ontology Terms for this Parameter.</p>

<?php
else:
?>

<table class="listitemstable">
<thead>
<tr>
<th>Ontology Id</th>
<th>Option</th>
<th>Increment</th>
<th>Sex</th>
<th>Selection Outcome</th>
<th>Ontology ID</th>
<th>Ontology Term</th>
<th>Edit</th>
<th>Delete</th>
</tr>
</thead>
<tbody>
<?php
foreach ($mpterms as $mpterm) {
    if($mpterm['mp_deleted'] == 1 && ! User::hasPermission(User::VIEW_DELETED))
        continue;
    echo '<tr>';
    echo '<td>' . $mpterm['ontology_id'] . '</td>';
    echo '<td>' . e($mpterm['option']->getName() . ' - ' . $mpterm['option']->getDescription()) . '</td>';
    echo '<td>' . e($mpterm['increment']->getIncrementString()) . '</td>';
    echo '<td>' . $mpterm['sex'] . '</td>';
    echo '<td>' . $mpterm['selection_outcome'] . '</td>';
    echo '<td>' . $mpterm['mp_id'] . '</td>';
    echo '<td>' . e($mpterm['mp_term']) . '</td>';
    echo '<td>';
    echo anchor(
        $controller . '/iu/model/parammpterm/row_id/' . $mpterm['id'] . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
        'Edit',
        array('class'=>'adminedit')
    );
    echo '</td>';
    if ($mpterm['mp_deleted']) {
        $deleted = anchor(
            $controller . '/undelete/parammpterm/' . $mpterm['id'] . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            'Undelete',
            array('class'=>'adminundelete')
        );
    } else {
        $deleted = anchor(
            'delete/model/parammpterm/item_id/' . $mpterm['id'] . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            'Delete',
            array('class'=>'admindelete')
        );
    }
    echo '<td>' . $deleted . '</td>';
    echo "</tr>\n";
}
?>
</tbody>
</table>

<?php
endif;
?>


<h3>EQ Terms</h3>

<p>
<?php echo anchor($controller . '/iu/model/parameqterm/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId, 'Create a new EQ Term', array('class'=>'admincreate')); ?>
 | <?php echo anchor($controller . '/recyclebin/parameqterm/' . $pipelineId . '/' . $procedureId . '/' . $parameter->getId(), 'EQ Term Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php
if (empty($eqterms)):
?>

<p>There are no EQ Terms for this Parameter.</p>

<?php
else:
?>

<table class="listitemstable">
<thead>
<tr>
<th>Ontology Id</th>
<th>Option</th>
<th>Increment</th>
<th>Sex</th>
<th>Selection Outcome</th>
<th>Entity 1</th>
<th>Entity 2</th>
<th>Entity 3</th>
<th>Quality 1</th>
<th>Quality 2</th>
<th>Edit</th>
<th>Delete</th>
</tr>
</thead>
<tbody>
<?php
foreach ($eqterms as $eqterm) {
    if($eqterm['eq_deleted'] == 1 && ! User::hasPermission(User::VIEW_DELETED))
        continue;
    echo '<tr>';
    echo '<td>' . $eqterm['ontology_id'] . '</td>';
    echo '<td>' . e($eqterm['option']->getName() . ' - ' . $eqterm['option']->getDescription()) . '</td>';
    echo '<td>' . e($eqterm['increment']->getIncrementString()) . '</td>';
    echo '<td>' . $eqterm['sex'] . '</td>';
    echo '<td>' . $eqterm['selection_outcome'] . '</td>';
    echo '<td>' . e($eqterm['entity1_term']) . ' ' . $eqterm['entity1_id'] . '</td>';
    echo '<td>' . e($eqterm['entity2_term']) . ' ' . $eqterm['entity2_id'] . '</td>';
    echo '<td>' . e($eqterm['entity3_term']) . ' ' . $eqterm['entity3_id'] . '</td>';
    echo '<td>' . e($eqterm['quality1_term']) . ' ' . $eqterm['quality1_id'] . '</td>';
    echo '<td>' . e($eqterm['quality2_term']) . ' ' . $eqterm['quality2_id'] . '</td>';
    echo '<td>';
    echo anchor(
        $controller . '/iu/model/parameqterm/row_id/' . $eqterm['id'] . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
        'Edit',
        array('class'=>'adminedit')
    );
    echo '</td>';
    if ($eqterm['eq_deleted']) {
        $deleted = anchor(
            $controller . '/undelete/parameqterm/' . $eqterm['id'] . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            'Undelete',
            array('class'=>'adminundelete')
        );
    } else {
        $deleted = anchor(
            'delete/model/parameqterm/item_id/' . $eqterm['id'] . '/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
            'Delete',
            array('class'=>'admindelete')
        );
    }
    echo '<td>' . $deleted . '</td>';
    echo "</tr>\n";
}
?>
</tbody>
</table>

<?php
endif;
