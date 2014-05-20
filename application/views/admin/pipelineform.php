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

<script type="text/javascript">
var nvclick = true;
</script>

<?php echo form_open(null, array('id'=>'addeditform','name'=>'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="weight" value="<?php echo set_value('weight', (isset($weight)) ? $weight : ''); ?>">
<input type="hidden" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>">
<input type="hidden" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>">
<input type="hidden" name="pipeline_key" value="<?php echo set_value('pipeline_key', (isset($pipeline_key)) ? $pipeline_key : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<?php
$ci =& get_instance();
//check the item being edited is the latest version
if ($mode == 'U') {
//    $isLatestVersion = $ci->pipelinemodel->isLatestVersion(array('pipeline_id' => $pipeline_id));
//    if ( ! $isLatestVersion) {
//        echo '<p><b>Note:</b> This is not the latest version of this Pipeline.
//              If you create a new version of this Pipeline the next version 
//              number will not be the immediate increment of this version.</p>';
//    }
}
?>

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>
    <td><label for="pipeline_id">Id</label></td>
    <td><input type="text" disabled="disabled" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>"></td>
</tr>
<!--<tr>
    <td><label for="weight">Weight</label></td>
    <td><input type="text" disabled="disabled" name="weight" value="<?php //echo set_value('weight', (isset($weight)) ? $weight : ''); ?>"></td>
</tr>-->
<tr>
    <td><label for="major_version">Major Version</label></td>
    <td><input type="text" disabled="disabled" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>"></td>
</tr>
<tr>
    <td><label for="minor_version">Minor Version</label></td>
    <td><input type="text" disabled="disabled" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>"></td>
</tr>
<?php } ?>

<tr>
    <td><label for="pipeline_key" class="required">Pipeline Key<?php echo ($mode == 'I') ? ' Stub' : ''; ?></label></td>
    <td><input type="text" name="pipeline_key" title="<?php tooltip('pipeline_stub') ?>"
    value="<?php echo set_value('pipeline_key', (isset($pipeline_key)) ? $pipeline_key : ''); ?>"<?php echo ($mode == 'U') ? ' disabled="disabled"' : ''; ?>>
    <!-- placeholder="e.g. IMPC (3 - 8 Uppercase Letters)"
    pattern="^[A-Z]{3,8}$"oninvalid="setCustomValidity('Please enter only Uppercase Alphabetic characters. A Pipeline Key Stub must be between 3 and 8 characters long.')" --></td>
</tr>
<tr>
    <td><label for="name" class="required">Name</label></td>
    <td><input type="text" name="name" title="<?php tooltip('pipeline_name') ?>" value="<?php echo set_value('name', (isset($name)) ? $name : ''); ?>"></td>
</tr>
<tr>
    <td><label for="visible">Visible</label></td>
    <td><input type="checkbox" name="visible" title="<?php tooltip('visible') ?>"
    <?php
    //decided that setting a Pipeline to not be visible for non-SuperAdmin users was not practical, hence the commenting out
//    if (User::isSuperAdmin()) {
        echo ($mode == 'I') ? 'checked' : set_value('visible', (@$visible) ? 'checked' : '');
//    } else {
//        echo set_value('visible', (@$visible) ? 'checked' : '');
//        echo ' disabled="disabled"';
//    }
    ?>></td>
</tr>
<tr>
    <td><label for="active">Active</label></td>
    <td><input type="checkbox" name="active" title="<?php tooltip('3P_active') ?>" <?php echo set_value('active', (@$active) ? 'checked' : ''); if($mode=='I') echo 'checked'; ?>></td>
</tr>

<?php if (User::isAdmin()) { ?>
<tr>
    <td><label for="internal">Internal</label></td>
    <td><input type="checkbox" name="internal" title="<?php tooltip('internal') ?>"<?php echo set_value('internal', (@$internal) ? ' checked' : ''); ?>></td>
</tr>
<?php } ?>

<?php
if ($this->config->item('modify_deprecated')) {
?>
<tr>
    <td><label for="deprecated">Deprecated</label></td>
    <td><input type="checkbox" name="deprecated" title="<?php tooltip('deprecated') ?>"<?php echo set_value('deprecated', (@$deprecated) ? ' checked' : ''); ?>></td>
</tr>
<?php
} else {
?>
<input type="hidden" name="deprecated" value="<?php echo set_value('deprecated', (@$deprecated) ? '1' : '0'); ?>">
<?php
}
?>

<tr>
    <td><label for="centre_name">Centre ILAR code</label></td>
    <td><input type="text" name="centre_name" title="<?php tooltip('ilar_code') ?>" value="<?php echo set_value('centre_name', (isset($centre_name)) ? $centre_name : ''); ?>"></td>
</tr>

<tr>
    <td><label for="impc">Is IMPC Pipeline?</label></td>
    <td><input type="checkbox" name="impc" title="<?php echo tooltip('is_impc'); ?>"<?php echo set_value('impc', (@$impc) ? ' checked' : '');?>></td>
</tr>

<tr>
    <td><label for="description">Description</label></td>
    <td><textarea name="description" title="<?php tooltip('description') ?>"><?php echo set_value('description', (isset($description)) ? $description : ''); ?></textarea></td>
</tr>

<?php if($mode == 'U'){ ?>
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
    <td>
    <span id="hidefornewversion">
        <input type="submit" name="submit" id="submit" value="Submit"> <?php if($mode == 'U') echo 'or'; ?>
    </span>
    <div id="newmajorversionfields" style="display:inline;display:none">
    <?php $this->load->view('admin/newmajorversionfields', array('controller' => $controller)); ?>
    </div>
    <?php if($mode == 'U'){ ?>
	<input type="hidden" name="nvsubmitbuttonclicked" id="nvsubmitbuttonclicked" value="">
	<input type="submit" name="new_major_version_submit" id="nvsubmit" value="Create a new Major Version">
	<input type="submit" name="cancel" style="display:none" id="nvcancel" value="Cancel">
	<script type="text/javascript">
	$('#nvsubmit').click(function(e){
		if(nvclick){
			//display the versioning fields
			e.preventDefault();
			$('#hidefornewversion').hide();
			$('#newmajorversionfields').show();
			$('#nvcancel').show();
		}else{
			//submit the new version form but first set the button as clicked (to fix jquery.submit() bug)
			$('#nvsubmitbuttonclicked').val('1');
		}
		nvclick = false;
	});
	$('#nvcancel').click(function(e){
		e.preventDefault();
		$('#newmajorversionfields').hide();
		$('#hidefornewversion').show();
		$(this).hide();
		nvclick = true;
	});
	</script>
    <?php } ?>
    </td>
</tr>
</table>

<?php echo form_close();
