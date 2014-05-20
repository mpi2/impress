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
 * @param array $parameters Array of Parameter objects
 * @param Procedure $procedure object
 * @param int $pipelineId
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Parameters</legend>
<h2>Parameters for Procedure: 
<?php echo anchor($controller . '/procedure/' . $pipelineId, e($procedure->getItemName())); ?>  <span class="procedurekey"><?php echo $procedure->getItemKey(); ?></span>
</h2>
<p>
<?php echo anchor($controller . '/iu/model/parameter/procedure_id/' . $procedure->getId() . '/pipeline_id/' . $pipelineId, 'Create a new Parameter', array('class'=>'admincreate')); ?>
 ... Or 
<?php echo anchor($controller . '/softlinkparameter/' . $procedure->getId() . '/' . $pipelineId, 'Soft-Link Existing Parameters', array('class'=>'adminimport')); ?> 
<?php
if (User::isSuperAdmin()) {
    echo ' or ';
    echo anchor('xmlimport/selectxml/' . $pipelineId . '/' . $procedure->getId(), 'Bulk Import Parameters from XML', array('class'=>'adminimport','target'=>'_blank'));
}
?>
 into this Procedure
 | Manage Parameter:
 <?php echo anchor($controller . '/itemRelationship/parameter/' . $pipelineId . '/' . $procedure->getId(), 'Relationships', array('class'=>'adminimport')); ?> 
 <?php echo anchor($controller . '/manageunits/' . $pipelineId . '/' . $procedure->getId(), 'Units', array('class'=>'adminimport')); ?>
<?php
if (User::isAdmin() || User::isSuperAdmin()) {
    echo ' | ' . anchor('impress/displayChangeHistory/' . $pipelineId . '/' . $procedure->getId(), 'View Change History', array('class'=>'adminimport', 'target' => '_blank'));
}
?>
 | <?php echo anchor($controller . '/recyclebin/parameter/' . $pipelineId . '/' . $procedure->getId(), 'Parameter Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php echo $flash; ?>

<?php if(count($parameters) > 0): ?>
    <p>Please select the Parameter for which the Ontology you wish to edit exists...</p>
    <table class="listitemstable">
    <thead>
    <tr>
    <th>Id</th>
    <th>Parameter</th>
    <th>Flags</th>
    <th>Version</th>
    <th>Edit</th>
    <th>Clone</th>
    <th>Delete</th>
    <th>Increments</th>
    <th>Options</th>
    <th>Ontology Groups</th>
    <th>Move 
    <?php echo anchor($controller . '/resequence/parameter/' . $procedure->getId(), '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
    </th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($parameters as $param) {
        if(($param->isDeleted() && ! User::hasPermission(User::VIEW_DELETED)) || ($param->isInternal() && ! User::hasPermission(User::VIEW_INTERNAL)))
            continue;
        echo "<tr><td>" . $param->getId() . "</td>\n<td>";
        echo anchor(
            $controller . '/ontology/' . $param->getId() . '/' . $procedure->getId() . '/' . $pipelineId,
            e($param->getItemName()),
            array('class' => ($param->getType() == EParamType::METADATA) ? 'dark' : '')
        );
        echo " <span class='parameterkey'>" . $param->getItemKey() . "</span></td>\n";
        echo "<td class='adminstatus'>" . item_flags($param) . "</td>\n";
        echo "<td>" . $param->getMajorVersion() . '.' . $param->getMinorVersion() . "</td>\n";
        echo "<td>" . anchor($controller . '/iu/model/parameter/row_id/' . $param->getId() . '/procedure_id/' . $procedure->getId() . '/pipeline_id/' . $pipelineId, 'Edit', array('class'=>'adminedit')) . "</td>\n";
        echo "<td>" . anchor($controller . '/cloneParameter/item_id/' . $param->getId(), 'Clone', array('class'=>'adminclone')) . "</td>\n";
        if($param->isDeleted())
            echo "<td>" . anchor($controller . '/undelete/parameter/' . $param->getId() . '/parameter_id/' . $param->getId() . '/procedure_id/' . $procedure->getId() . '/pipeline_id/' . $pipelineId, 'Undelete', array('class'=>'adminundelete')) . "</td>\n";
        else
            echo "<td>" . anchor('delete/model/parameter/item_id/' . $param->getId() . '/procedure_id/' . $procedure->getId() . '/pipeline_id/' . $pipelineId, 'Delete', array('class'=>'admindelete')) . "</td>\n";
        echo "<td>" . anchor($controller . '/increment/' . $param->getId() . '/' . $procedure->getId() . '/' . $pipelineId, (($param->isIncrement()) ? 'Yes' : 'No')) . "</td>\n";
        echo "<td>" . anchor($controller . '/option/' . $param->getId() . '/' . $procedure->getId() . '/' . $pipelineId, (($param->isOption()) ? 'Yes' : 'No')) . "</td>\n";
        $ontologyGroups = $param->getOntologyGroups();
        echo "<td>" . anchor($controller . '/ontologygroup/' . $param->getId() . '/' . $procedure->getId() . '/' . $pipelineId, ((empty($ontologyGroups)) ? 'No' : 'Yes')) . "</td>\n";
        echo "<td>" . anchor($controller . '/move/up/parameter/' . $param->getId() . '/' . $procedure->getId(), '<img border="0" src="' . base_url() . 'images/up.png' . '">') . "\n";
        echo anchor($controller . '/move/dn/parameter/' . $param->getId() . '/' . $procedure->getId(), '<img border="0" src="' . base_url() . 'images/dn.png' . '">') . "</td></tr>\n";
    }
    ?>
    </tbody>
    </table>
    
<?php else: ?>
    <p>There are currently no Parameters in this Procedure.</p>
<?php endif; ?>
</fieldset>

<?php $this->load->view('admin/flaglegend');
