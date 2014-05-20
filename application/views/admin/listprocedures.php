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
 * @param array $procedures Array of Procedure objects
 * @param Pipeline $pipeline object
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Procedures</legend>
<h2>Procedures for Pipeline: 
<?php echo anchor($controller . '/pipeline', e($pipeline->getItemName())); ?>  <span class="pipelinekey"><?php echo $pipeline->getItemKey(); ?></span> 
</h2>
<p>
<?php echo anchor($controller . '/iu/model/procedure/pipeline_id/' . $pipeline->getId(), 'Create a new Procedure', array('class'=>'admincreate')); ?> 
 ... Or
 <?php echo anchor($controller . '/softlinkprocedure/' . $pipeline->getId(), 'Soft-Link Existing Procedures', array('class'=>'adminimport')); ?>
 into this Pipeline
 | Manage Procedure: <?php echo anchor($controller . '/manageproceduretypes/' . $pipeline->getId(), 'Types', array('class'=>'adminimport')); ?> 
<?php echo anchor($controller . '/manageprocedureweeks/' . $pipeline->getId(), 'Weeks', array('class'=>'adminimport')); ?> 
<?php echo anchor($controller . '/itemRelationship/procedure/' . $pipeline->getId(), 'Relationships', array('class'=>'adminimport')); ?>
<?php
if (User::isAdmin() || User::isSuperAdmin()) {
    echo ' | ' . anchor('impress/displayChangeHistory/' . $pipeline->getId(), 'View Change History', array('class'=>'adminimport', 'target' => '_blank'));
}
?>
 | <?php echo anchor($controller . '/recyclebin/procedure/' . $pipeline->getId(), 'Procedure Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php echo $flash; ?>

<?php if(count($procedures) > 0): ?>
    <p>Please select the procedure in which the Parameter/Ontology you wish to edit exists...</p>
    <table class="listitemstable">
    <thead>
    <tr>
    <th>Id</th>
    <th>Week</th>
    <th>Procedure</th>
    <th>Flags</th>
    <th>Version</th>
    <th>Protocol</th>
    <th>Edit</th>
    <th>Clone</th>
    <th>Delete</th>
    <th>Move 
    <?php echo anchor($controller . '/resequence/procedure/' . $pipeline->getId(), '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
    </th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach($procedures as $proc){
        if(($proc->isDeleted() && ! User::hasPermission(User::VIEW_DELETED))) // || ($proc->isInternal() && ! User::hasPermission(User::VIEW_INTERNAL)
            continue;
        echo "<tr><td>" . $proc->getId() . "</td>\n";
                echo "<td>" . $proc->getWeek() . "</td>\n";
        echo "<td>" . anchor($controller . '/parameter/' . $proc->getId() . '/' . $pipeline->getId(), e($proc->getItemName())) . " ";
        echo "<span class='procedurekey'>" . $proc->getItemKey() . "</span></td>\n";
        echo "<td class='adminstatus'>" . item_flags($proc) . "</td>\n";
        echo "<td>" . $proc->getMajorVersion() . '.' . $proc->getMinorVersion() . "</td>\n";
        echo "<td>" . anchor($controller . '/sop/' . $proc->getId() . '/' . $pipeline->getId(), 'Procotol', array('class'=>'adminsop')) . "</td>\n";
        echo "<td>" . anchor($controller . '/iu/model/procedure/row_id/' . $proc->getId() . '/pipeline_id/' . $pipeline->getId(), 'Edit', array('class'=>'adminedit')) . "</td>\n";
        echo "<td>" . anchor($controller . '/cloneProcedure/item_id/' . $proc->getId() . '/pipeline_id/' . $pipeline->getId(), 'Clone', array('class'=>'adminclone')) . "</td>\n";
        if($proc->isDeleted())
            echo "<td>" . anchor($controller . '/undelete/procedure/' . $proc->getId() . '/pipeline_id/' . $pipeline->getId() . '/procedure_id/' . $proc->getId(), 'Undelete', array('class'=>'adminundelete')) . "</td>\n";
        else
            echo "<td>" . anchor('delete/model/procedure/item_id/' . $proc->getId() . '/pipeline_id/' . $pipeline->getId(), 'Delete', array('class'=>'admindelete')) . "</td>\n";
        echo "<td>" . anchor($controller . '/move/up/procedure/' . $proc->getId() . '/' . $pipeline->getId(), '<img border="0" src="' . base_url() . 'images/up.png' . '">') . "\n";
        echo anchor($controller . '/move/dn/procedure/' . $proc->getId() . '/' . $pipeline->getId(), '<img border="0" src="' . base_url() . 'images/dn.png' . '">') . "</td></tr>\n";
    }
    ?>
    </tbody>
    </table>

<?php else: ?>
    <p>There are currently no Procedures in this Pipeline.</p>
<?php endif; ?>
</fieldset>

<?php $this->load->view('admin/flaglegend');
