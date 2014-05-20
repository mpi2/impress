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

<?php echo form_open(null, array('id' => 'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="procedure_id" value="<?php echo set_value('procedure_id', (isset($procedure_id)) ? $procedure_id : ''); ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="param_option_id" value="<?php echo set_value('param_option_id', (isset($param_option_id)) ? $param_option_id : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<table id="edit">

<tr>
	<td><label for="parameter" class="required">Parameter</label></td>
	<?php $parameter = new Parameter($parameter_id); ?>
	<td><input type="text" name="parameter" disabled="disabled" value="<?php echo form_prep($parameter->getItemName()); ?>"></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="param_option_id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('param_option_id', (isset($param_option_id)) ? $param_option_id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td><label for="name" class="required">Option Name (Value)</label></td>
	<td><input type="text" name="name" title="<?php tooltip('option_name') ?>" value="<?php echo set_value('name', (isset($name)) ? $name : ''); ?>"></td>
</tr>
<tr>
	<td><label for="parent_id">Parent Option Id</label></td>
	<td><input type="text" name="parent_id" title="<?php tooltip('option_parent') ?>" value="<?php echo set_value('parent_id', (isset($parent_id)) ? $parent_id : ''); ?>"></td>
</tr>
<tr>
	<td><label for="description">Description</label></td>
	<td><textarea name="description" title="<?php tooltip('description') ?>"><?php echo set_value('description', (isset($description)) ? $description : ''); ?></textarea></td>
</tr>
<tr>
	<td><label for="is_default">Is Default Option?</label></td>
	<td><input type="checkbox" name="is_default" title="<?php tooltip('option_default') ?>"<?php echo set_value('is_default', (@$is_default) ? ' checked' : ''); ?>></td>
</tr>
<tr>
	<td><label for="is_active">Is Active</label></td>
	<td><input type="checkbox" name="is_active" title="<?php tooltip('option_active') ?>"<?php echo set_value('is_active', (@$is_active) ? ' checked' : ''); if($mode=='I') echo ' checked'; ?>></td>
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

<?php
//$ci =& get_instance();
//$ci->load->model('notinbetamodel');
//if ($mode == 'U' &&
//    $ci->config->item('version_triggering') &&
//    ! $ci->parametermodel->isInternal($parameter->getId()) &&
//    $ci->notinbetamodel->keyIsInBeta($parameter->getItemKey())
//) {
//    echo '<tr><td><label class="required">New Version</label></td>';
//    echo '<td>Editing an option causes the creation of a new Parameter Version.<br>';
//    $pipeline = new Pipeline($pipeline_id);
//    $ci->load->view(
//        'admin/newmajorversionfields',
//        array(
//                'controller' => $controller,
//                'selectedPipeline' => $pipeline_id,
//                'selectedProcedure' => $procedure_id,
//                'pipelines' => PipelinesFetcher::fetchAll(),
//                'procedures' => $pipeline->getProcedures()
//        )
//    );
//    echo '</td></tr>';
//}
?>

<tr>
	<td></td>
	<td><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>

</table>

<?php echo form_close();
