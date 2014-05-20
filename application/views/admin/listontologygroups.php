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
 * @param Parameter $parameter
 * @param int $procedureId
 * @param int $pipelineId
 * @param string $flash
 * @param string $controller
 */
?>


<br>
<fieldset><legend>Ontology Option Groups</legend>

<h2>Ontology Option Groups for Parameter: <?php echo anchor($controller . '/parameter/' . $procedureId . '/' . $pipelineId, trim(e($parameter->getItemName()))); ?> 
<span class="parameterkey"><?php echo $parameter->getItemKey(); ?></span></h2>

<p>
<?php
echo anchor(
    $controller . '/iu/model/ontologygroup/parameter_id/' . $parameter->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId,
    'Create a new Group',
    array('class'=>'admincreate')
);
?>
 | <?php
echo anchor(
    $controller . '/softlinkontologygroup/' . $parameter->getId() . '/' . $procedureId . '/' . $pipelineId,
    'Soft-Link Existing Ontology Groups to this Parameter',
    array('class' => 'adminimport')
);
?>
 | <?php
echo anchor(
    $controller . '/recyclebin/paramontologyoption/' . $pipelineId . '/' . $procedureId . '/' . $parameter->getId(),
    'Ontology Option Bin',
    array('class'=>'adminrecycle')
);
?>
</p>

<?php echo $flash; ?>

<?php

$groups = $parameter->getOntologyGroups();

if (empty($groups)):

echo '<p>There are currently no Ontology Groups associated with this Parameter.</p>';

else:
?>

<table class="listitemstable">
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Options</th>
    <th>Edit</th>
    <th>Delete</th>
</tr>
</thead>
<tbody>

<?php
foreach ($groups as $group) {
    echo "<tr>\n";
    echo '<td valign="top">' . $group->getId() . '</td>';
    echo '<td valign="top">' . e($group->getName()) . '</td>';
    echo '<td valign="top">';
    foreach ($group->getOntologyOptions() as $option) {
        //edit link
        echo anchor(
            $controller . '/iu/model/paramontologyoption/row_id/' . $option->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(), 
            '<img border="0" src="' . base_url() . 'images/pencil.png' . '" alt="Edit">',
            array('title'=>'Edit')
        ) . ' ';
        //delete/undelete link
        if ($option->isDeleted()) {
            echo anchor(
                $controller . '/undelete/paramontologyoption/' . $option->getId() . '/ontology_group_id/' . $group->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
                '<img border="0" src="' . base_url() . 'images/undelete.png' . '" alt="Undelete">',
                array('title'=>'Undelete')
            ) . ' ';
        } else {
            echo anchor(
                'delete/model/paramontologyoption/item_id/' . $option->getId() . '/ontology_group_id/' . $group->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
                '<img border="0" src="' . base_url() . 'images/delete.png' . '" alt="Delete">',
                array('title'=>'Delete')
            ) . ' ';
        }
        //up link
        echo anchor(
            $controller . '/move/up/paramontologyoption/' . $option->getId() . '/' . $group->getId(),
            '<img border="0" src="' . base_url() . 'images/up.png' . '" alt="Move Up">',
            array('title'=>'Move Up')
        ) . ' ';
        //down link
        echo anchor(
            $controller . '/move/dn/paramontologyoption/' . $option->getId() . '/' . $group->getId(),
            '<img border="0" src="' . base_url() . 'images/dn.png' . '" alt="Move Down">',
            array('title'=>'Move Down')
        ) . ' ';
        //ontology
        echo "[" . $option->getOntologyId() . "] " . e($option->getOntologyTerm()) . "<br>\n";
    }
    //add new ontology
    echo anchor(
        $controller . '/iu/model/paramontologyoption/ontology_group_id/' . $group->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
        'Add a new Ontology Option to this group',
        array('class'=>'admincreate')
    ) . ' ';
    //resequence
    echo anchor($controller . '/resequence/paramontologyoption/' . $group->getId(), ' &crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence'));
    echo "</td>\n";
    //edit group
    echo '<td valign="top">';
    echo anchor(
        $controller . '/iu/model/ontologygroup/row_id/' . $group->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
        'Edit',
        array('class'=>'adminedit')
    ) . '</td>';
    //delete group
    echo '<td valign="top">';
    if ($group->isDeleted()) {
        echo anchor(
            $controller . '/undelete/ontologygroup/' . $group->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
            'Unelete',
            array('class'=>'adminundelete')
        ) . '</td>';
    } else {
        echo anchor(
            'delete/model/ontologygroup/item_id/' . $group->getId() . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/parameter_id/' . $parameter->getId(),
            'Delete',
            array('class'=>'admindelete')
        ) . '</td>';
    }
    echo "\n</tr>\n";
}
?>

</tbody>
</table>

<?php
endif;
?>
</fieldset>
