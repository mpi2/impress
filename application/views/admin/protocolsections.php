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
 * @param string $controller
 */
?>

<fieldset><legend>Protocol Sections</legend>

<?php

//loop through sections of this sop

$sections = $sop->getSections();

if (empty($sections)):

    echo '<p>This SOP does not contain any sections.</p>';

else:
?>

<script type="text/javascript">
//admin protocol preview section
function previewSection(secId){
    $('.previewsection').hide('fast');
    if( ! $('#previewsection' + secId).is(':visible'))
        $('#previewsection' + secId).show('slow');
}
</script>

<table class="listitemstable foreigner protocolsections">
<thead>
<tr>
<th>ID</th>
<th>Version</th>
<th>Section Title</th>
<th>Edit</th>
<th>Delete</th>
</tr>
</thead>
<tbody>
<?php
foreach ($sections as $section) {
    if($section->isDeleted() && ! User::hasPermission(User::VIEW_DELETED))
        continue;
    echo '<tr>';
    echo '<td>' . $section->getId() . '</td>';
    echo '<td>' . $section->getMajorVersion() . '.' . $section->getMinorVersion() . '</td>';
    echo '<td><a title="Click to Preview" href="javascript:previewSection(' . $section->getId() . ')">' . e($section->getSectionTitle()->getTitle()) . '</a></td>';
    echo '<td>' . anchor($controller . '/iu/model/section/row_id/' . $section->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id, 'Edit', array('class'=>'adminedit')) . '</td>';
    echo '<td>';
    if($section->isDeleted())
        echo anchor($controller . '/undelete/section/' . $section->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id, 'Undelete', array('class'=>'adminundelete'));
    else
        echo anchor('delete/model/section/item_id/' . $section->getId() . '/procedure_id/' . $procedure_id . '/pipeline_id/' . $pipeline_id, 'Delete', array('class'=>'admindelete'));
    echo '</td>';
    echo '</tr>';
    echo '<tr class="previewsection" id="previewsection' . $section->getId() . '"><td colspan="5">' . dexss($section->getSectionText()) . '</td></tr>' . PHP_EOL;
}
?>
</tbody>
</table>

<?php
endif;
?>

</fieldset>
