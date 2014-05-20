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

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="procedure_id" value="<?php echo set_value('procedure_id', (isset($procedure_id)) ? $procedure_id : ''); ?>">
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="sop_id" value="<?php echo set_value('sop_id', (isset($sop_id)) ? $sop_id : ''); ?>">
<input type="hidden" name="weight" value="<?php echo set_value('weight', (isset($weight)) ? $weight : '0'); ?>">
<input type="hidden" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>">
<input type="hidden" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<table id="edit">

<tr>
	<td><label for="procedure" class="required">Procedure</label></td>
	<?php $procedure = new Procedure($procedure_id); ?>
	<td><input type="text" disabled="disabled" name="procedure" value="<?php echo form_prep($procedure->getItemName()); ?>"></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="sop_id">SOP Id</label></td>
	<td><input type="text" disabled="disabled" name="sop_id" value="<?php echo set_value('sop_id', (isset($sop_id)) ? $sop_id : ''); ?>"></td>
</tr>
<?php } ?>

<!--<tr>
	<td><label for="centre_id">Centre Id</label></td>
	<td><input type="text" name="centre_id" disabled="disabled" value="<?php //echo set_value('centre_id', (isset($centre_id)) ? $centre_id : ''); ?>"></td>
</tr>-->
<tr>
	<td><label for="title" class="required">Title</label></td>
	<td><input type="text" name="title" title="<?php tooltip('protocol_title') ?>" value="<?php echo ($mode == 'I') ? form_prep($procedure->getItemName()) : set_value('title', (isset($title)) ? $title : ''); ?>"></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="major_version">Major Version</label></td>
	<td><input type="text" disabled="disabled" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>"></td>
</tr>
<tr>
	<td><label for="minor_version">Minor Version</label></td>
	<td><input type="text" disabled="disabled" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>"></td>
</tr>
<tr>
	<td><label for="time_modified">Time Modified</label></td>
	<td><input type="text" name="time_modified" disabled="disabled" value="<?php echo set_value('time_modified', (isset($time_modified)) ? $time_modified : ''); ?>"></td>
</tr>
<tr>
	<td><label for="user_id">User</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo form_prep(@$username); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td></td>
	<td><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>

</table>

<?php echo form_close();
