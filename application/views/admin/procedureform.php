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

<?php
$ci =& get_instance();
echo form_open(null, array('id'=>'addeditform', 'name'=>'addeditform'));
?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="procedure_id" value="<?php echo set_value('procedure_id', (isset($procedure_id)) ? $procedure_id : ''); ?>">
<input type="hidden" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>">
<input type="hidden" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>">
<input type="hidden" name="procedure_key" value="<?php echo set_value('procedure_key', (isset($procedure_key)) ? $procedure_key : ''); ?>">
<input type="hidden" name="type" value="<?php echo set_value('type', (isset($type)) ? $type : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<table id="edit">
<tr>
    <td><label for="pipeline">Pipeline</label></td>
    <?php $pipeline = new Pipeline($pipeline_id); ?>
    <td><input type="text" disabled="disabled" name="pipeline" value="<?php echo form_prep($pipeline->getItemName()); ?>"></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>
    <td><label for="procedure">Procedure</label></td>
    <?php $procedure = new Procedure($procedure_id, $pipeline->getId()); ?>
    <td><input type="text" disabled="disabled" name="procedure" value="<?php echo form_prep($procedure->getItemName()); ?>"></td>
</tr>
<tr>
    <td><label for="major_version">Major Version</label></td>
    <td><input type="text" disabled="disabled" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>"></td>
</tr>
<tr>
    <td><label for="minor_version">Minor Version</label></td>
    <td><input type="text" disabled="disabled" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>"></td>
</tr>
<tr>
    <td><label for="procedure_key">Procedure Key</label></td>
    <td><input type="text" name="procedure_key" disabled="disabled" value="<?php echo set_value('procedure_key', (isset($procedure_key)) ? $procedure_key : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
    <td><label for="name" class="required">Name</label></td>
    <td><input type="text" name="name" title="<?php tooltip('procedure_name') ?>" value="<?php echo set_value('name', (isset($name)) ? $name : ''); ?>"></td>
</tr>
<tr>
    <td><label for="type" class="required">Procedure Type</label></td>
    <td>
    <select name="type"<?php echo ($mode == 'U') ? ' disabled="disabled"' : ''; ?> style="max-width:250px" title="<?php tooltip('procedure_type') ?>"><option value=""></option>
    <?php
    $ci->load->model('proceduretypemodel');
    foreach($ci->proceduretypemodel->fetchAll() AS $typ){
        $chosen = ($typ[ProcedureTypeModel::PRIMARY_KEY] == set_value('type', (isset($type)) ? $type : '')) ? ' selected="selected"' : '';
        echo "<option value='" . $typ[ProcedureTypeModel::PRIMARY_KEY] . "'$chosen>" . $typ['key'] . " - " . $typ['type'] . "</option>\n";
    }
    ?>
    </select>
    </td>
</tr>
<tr>
    <td><label for="is_visible">Visible</label></td>
    <td><input type="checkbox" title="<?php tooltip('visible') ?>" name="is_visible" <?php echo set_value('is_visible', (@$is_visible) ? 'checked' : ''); if($mode=='I') echo 'checked'; ?>></td>
</tr>
<tr>
    <td><label for="is_active">Active</label></td>
    <td><input type="checkbox" title="<?php tooltip('3P_active') ?>" name="is_active" <?php echo set_value('is_active', (@$is_active) ? 'checked' : ''); if($mode=='I') echo 'checked'; ?>></td>
</tr>
<tr>
    <td><label for="is_mandatory">Mandatory Procedure</label></td>
    <td><input type="checkbox" name="is_mandatory" <?php echo set_value('is_mandatory', (@$is_mandatory) ? 'checked' : ''); ?><?php
$ci->load->model('notinbetamodel');
if ($mode == 'I' && $this->config->item('version_triggering') && $ci->notinbetamodel->keyIsInBeta($pipeline->getItemKey())) {
	echo 'disabled="disabled"> <span style="width:92%" class="small align-right">', tooltip('mandatorynewpipeline'), '</span>';
} else {
	echo ' title="', tooltip('mandatory'), '">';
}
?></td>
</tr>

<?php if (User::isAdmin()) { ?>
<tr>
    <td><label for="is_internal">Internal</label></td>
    <td><input type="checkbox" name="is_internal" title="<?php tooltip('internal') ?>"<?php echo set_value('is_internal', (@$is_internal) ? ' checked' : ''); ?>></td>
</tr>
<?php } ?>

<?php
if ($this->config->item('modify_deprecated')) {
?>
<tr>
    <td><label for="is_deprecated">Deprecated</label></td>
    <td><input type="checkbox" title="<?php tooltip('deprecated') ?>" name="is_deprecated"<?php echo set_value('is_deprecated', (@$is_deprecated) ? ' checked' : ''); ?>></td>
</tr>
<?php
} else {
?>
<input type="hidden" name="is_deprecated" value="<?php echo set_value('is_deprecated', (@$is_deprecated) ? '1' : '0'); ?>">
<?php
}
?>

<tr>
    <td><label for="level" class="required">Procedure Level</label></td>
    <td><select name="level" title="<?php tooltip('level') ?>"><option value=""></option>
    <?php
    foreach (EProcedureLevel::__toArray() as $lvl) {
        $chosen = ($lvl == set_value('level', (isset($level)) ? $level : '')) ? ' selected="selected"' : '';
        echo "<option value='$lvl'$chosen>$lvl</option>\n";
    }
    ?>
    </select>
    </td>
</tr>

<tr>
    <td><label for="week" class="required">Week</label></td>
    <td><select name="week" title="<?php tooltip('week') ?>"><option value=""></option>
    <?php
    foreach (ProcedureWeeksFetcher::fetchAll() as $wk) {
        $chosen = ($wk->getId() == set_value('week', (isset($week)) ? $week : '')) ? ' selected="selected"' : '';
        echo "<option value='" . $wk->getId() . "'$chosen>(" . $wk->getStage() . ") " . e($wk->getLabel()) . "</option>\n";
    }
    ?>
    </select></td>
</tr>

<tr>
    <td><label for="min_females">Minimum No. Females</label></td>
    <td><input type="text" name="min_females" value="<?php echo (isset($min_females)) ? $min_females : ''; ?>" title="<?php tooltip('min_females') ?>"></td>
</tr>
<tr>
    <td><label for="min_males">Minimum No. Males</label></td>
    <td><input type="text" name="min_males" value="<?php echo (isset($min_males)) ? $min_males : ''; ?>" title="<?php tooltip('min_males') ?>"></td>
</tr>
<tr>
    <td><label for="min_animals">Minimum No. Animals</label></td>
    <td><input type="text" name="min_animals" value="<?php echo (isset($min_animals)) ? $min_animals : ''; ?>" title="<?php tooltip('min_animals') ?>"></td>
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
    <?php
    $this->load->view('admin/newmajorversionfields',
        array(
            'controller' => $controller,
            'selectedPipeline' => $pipeline->getId(),
            'selectedProcedure' => ($mode == 'U') ? $procedure_id : NULL,
            'pipelines' => PipelinesFetcher::fetchAll()
        )
    );
    ?>
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
