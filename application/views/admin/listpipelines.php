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
 * @param array $pipelines Array of Pipeline objects
 * @param string $flash
 * @param string $controller
 */
?>

<fieldset><legend>Pipelines</legend>
<h2>Pipelines</h2>
<p><?php echo anchor($controller . '/iu/model/pipeline', 'Create a new Pipeline', array('class'=>'admincreate')); ?> | 
<?php echo anchor($controller . '/checkmps', 'Check MP Ontologies', array('class'=>'adminontology')); ?> | 
<?php echo anchor($controller . '/manageGlossary', 'Manage Glossary', array('class'=>'adminimport')); ?> 
<?php //echo anchor($controller . '/manageReleases', 'Manage Releases', array('class'=>'adminimport')); ?>
<?php
if (User::isAdmin() || User::isSuperAdmin()) {
    echo ' | ' . anchor('impress/displayChangeHistory', 'View Change History', array('class'=>'adminimport', 'target' => '_blank'));
}
?>
<?php echo ' | ' . anchor($controller . '/recyclebin/pipeline', 'Pipeline Bin', array('class'=>'adminrecycle'));?>
</p>

<?php echo $flash; ?>

<p>Please select the pipeline in which the Procedure/Parameter/Ontology you wish to edit exists.</p>
<table class="listitemstable">
<thead>
<tr>
<th>Id</th>
<th>Pipeline</th>
<th>Flags</th>
<th>Version</th>
<th>Edit</th>
<th>Delete</th>
<th>Move 
<?php echo anchor($controller . '/resequence/pipeline', '&crarr;', array('title'=>'Reset/Resequence Display Order', 'class' => 'resequence')); ?>
</th>
</tr>
</thead>
<tbody>
<?php
foreach($pipelines as $p){
    if(($p->isDeleted() && ! User::hasPermission(User::VIEW_DELETED)))
        continue;
    echo "<tr><td>" . $p->getId() . "</td>\n";
    echo "<td>" . anchor($controller . '/procedure/' . $p->getId(), e($p->getItemName())) . " ";
    echo "<span class='pipelinekey'>" . $p->getItemKey() . "</span></td>\n";
    echo "<td class='adminstatus'>" . item_flags($p) . "</td>\n";
    echo "<td>" . $p->getMajorVersion() . '.' . $p->getMinorVersion() . "</td>\n";
    echo "<td>" . anchor($controller . '/iu/model/pipeline/row_id/' . $p->getId(), 'Edit', array('class'=>'adminedit')) . "</td>\n";
    if($p->isDeleted())
        echo "<td>" . anchor($controller . '/undelete/pipeline/' . $p->getId(), 'Undelete', array('class'=>'adminundelete')) . "</td>\n";
    else
        echo "<td>" . anchor('delete/model/pipeline/item_id/' . $p->getId(), 'Delete', array('class'=>'admindelete')) . "</td>\n";
    echo "<td>" . anchor($controller . '/move/up/pipeline/' . $p->getId(), '<img border="0" src="' . base_url() . 'images/up.png' . '">') . "\n";
    echo anchor($controller . '/move/dn/pipeline/' . $p->getId(), '<img border="0" src="' . base_url() . 'images/dn.png' . '">') . "</td></tr>\n";
}
?>
</tbody>
</table></fieldset>

<?php $this->load->view('admin/flaglegend');
