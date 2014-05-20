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

?>

<script type="text/javascript" src="<?php echo base_url(); ?>js/ckeditor/ckeditor.js"></script>

<?php echo form_open(null, array('id' => 'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="procedure_id" value="<?php echo set_value('procedure_id', (isset($procedure_id)) ? $procedure_id : ''); ?>">
<input type="hidden" name="section_id" value="<?php echo set_value('section_id', (isset($section_id)) ? $section_id : ''); ?>">
<input type="hidden" name="sop_id" value="<?php echo set_value('sop_id', (isset($sop_id)) ? $sop_id : ''); ?>">
<input type="hidden" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>">
<input type="hidden" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<table id="edit" class="ckd">

<?php if($mode == 'U'){ ?>
<tr>
	<td class="f1"><label for="section_id">Section Id</label></td>
	<td class="f2"><input type="text" name="section_id" disabled="disabled" value="<?php echo set_value('section_id', (isset($section_id)) ? $section_id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td class="f1"><label for="protocol" class="required">Protocol</label></td>
	<?php $protocol = new SOP($sop_id); ?>
	<td class="f2"><input type="text" disabled="disabled" name="protocol" value="<?php echo form_prep($protocol->getTitle()); ?>"></td>
</tr>
<tr>
	<td class="f1"><label for="section_title_id" class="required">Section Title Id</label></td>
	<td class="f2">
	<?php $chosenValue = set_value('section_title_id', (isset($section_title_id)) ? $section_title_id : ''); ?>
	<input type="hidden" name="section_title_id" value="<?php echo $chosenValue; ?>">
	<select name="section_title_id" title="<?php tooltip('section_title'); ?>"<?php echo ($mode == 'U') ? ' disabled="disabled"' : ''; ?>>
	<option value=""></option>
	<?php
	$ci =& get_instance();
	$ci->load->model('sectiontitlemodel');
	foreach($ci->sectiontitlemodel->fetchAll() AS $i){
		$select = ($chosenValue == $i[SectionTitleModel::PRIMARY_KEY]) ? ' selected' : '';
		echo "<option value='" . $i[SectionTitleModel::PRIMARY_KEY] . "'" . $select . ">" . $i[SectionTitleModel::PRIMARY_KEY] . ". " . e($i['title']) . "</option>\n";
	}
	?>
	</select></td>
</tr>
<tr>
	<td class="f1"><label for="section_text" class="required">Section Text</label></td>
	<td class="f2"><textarea name="section_text" class="ckeditor"><?php echo set_value('section_text', (isset($section_text)) ? $section_text : ''); ?></textarea></td>
</tr>
<tr>
	<td class="f1"><label for="weight">Weight</label></td>
	<td class="f2"><input type="text" name="weight" title="<?php tooltip('weight') ?>" value="<?php echo set_value('weight', (isset($weight)) ? $weight : ''); ?>"></td>
</tr>
<!--<tr>
	<td class="f1"><label for="level">Level</label></td>
	<td class="f2"><input type="text" name="level" value="<?php //echo set_value('level', (isset($level)) ? $level : ''); ?>"></td>
</tr>
<tr>
	<td class="f1"><label for="level_text">Level Text</label></td>
	<td class="f2"><input type="text" name="level_text" value="<?php //echo set_value('level_text', (isset($level_text)) ? $level_text : ''); ?>"></td>
</tr>-->

<?php if($mode == 'U'){ ?>
<tr>
	<td class="f1"><label for="major_version">Major Version</label></td>
	<td class="f2"><input type="text" disabled="disabled" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>"></td>
</tr>
<tr>
	<td class="f1"><label for="minor_version">Minor Version</label></td>
	<td class="f2"><input type="text" disabled="disabled" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>"></td>
</tr>
<tr>
	<td class="f1"><label for="time_modified">Time Modified</label></td>
	<td class="f2"><input type="text" name="time_modified" disabled="disabled" value="<?php echo set_value('time_modified', (isset($time_modified)) ? $time_modified : ''); ?>"></td>
</tr>
<tr>
	<td><label for="user_id">User</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo form_prep(@$username); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td class="f1"></td>
	<td class="f2"><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>

</table>

<?php echo form_close();
