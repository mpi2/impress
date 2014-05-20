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
 * @param SOP $sop The SOP object which may contain sections
 * @param int $pipeline_id
 * @param int $procedure_id
 * @param string $pdffile path + name of pdf file of protocol
 * @param string $flash
 * @param string $controller
 */
?>


<p><?php 
echo anchor(
    $controller . '/iu/model/sop/row_id/' . $sop->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id,
    'Edit Protocol',
    array('class'=>'adminedit')
); 
echo " | \n";
if ($sop->isDeleted()) {
    echo anchor(
        $controller . '/undelete/sop/' . $sop->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id, 
        'Protocol Undelete',
        array('class'=>'adminundelete')
    );
} else {
    echo anchor(
        'delete/model/sop/item_id/' . $sop->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id,
        'Delete Protocol',
        array('class'=>'admindelete')
    );
}
?>
 | <?php echo anchor($controller . '/recyclebin/sop/' . $pipeline_id . '/' . $procedure_id, 'Protocol Bin', array('class'=>'adminrecycle')); ?>
</p>
<?php echo $flash; ?>
<table class="listitemstable" style="width:46%">
<tr>
<th width="30%">Protocol ID</th><td><?php echo $sop->getId(); ?></td>
</tr>
<tr>
<th>Procotol Title</th><td><?php echo e($sop->getTitle()); ?></td>
</tr>
<tr>
<th>Version</th><td><?php echo $sop->getMajorVersion() . '.' . $sop->getMinorVersion(); ?></td>
</tr>
<tr>
<th>PDF File</th><td>
<?php
if (file_exists($pdffile)) {
    //display link to view or delete pdf file
    echo $sop->getProcedure()->getItemKey() . '.pdf' . ' - '; 
    echo anchor(base_url() . 'impress/displaySOP/' . $sop->getProcedure()->getId() . '/pdf', 'View PDF', array('class'=>'adminpdf', 'target'=>'_blank')) . ' / ';
    echo anchor($controller . '/deletepdf/' . $sop->getProcedure()->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id, 'Delete PDF', array('class'=>'admindelete'));
}else{
    //display the upload form to upload a pdf 
?>
    <form method="post" action="<?php echo base_url() . $controller . '/uploadpdf/' . $procedure_id . '/' . $pipeline_id ; ?>" enctype="multipart/form-data">
    <input type="file" name="pdffile" title="<?php tooltip('pdf') ?>">
    <input type="submit" name="pdfsubmit" value="Upload">
    </form>
<?php
}
?>
</td>
</tr>
</table>

<p>
<?php echo anchor($controller . '/iu/model/section/sop_id/' . $sop->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id, 'Create a new section', array('class'=>'admincreate')); ?>
<?php 
if (User::isSuperAdmin())
    echo ' | ' . anchor($controller . '/sectiontitles/' . $pipeline_id . '/' . $procedure_id, 'Manage Section Titles', array('class'=>'adminmodify'));
?>
 | <?php echo anchor($controller . '/recyclebin/section/' . $pipeline_id . '/' . $procedure_id, 'Protocol Sections Bin', array('class'=>'adminrecycle')); ?>
</p>

<?php $this->load->view('admin/protocolsections', array('sop' => $sop, 'pipeline_id' => $pipeline_id, 'procedure_id' => $procedure_id, 'controller' => $controller)); ?>

<br>
