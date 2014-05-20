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
<input type="hidden" name="pipeline_id" value="<?php echo (isset($pipeline_id)) ? $pipeline_id : ''; ?>">
<input type="hidden" name="procedure_id" value="<?php echo (isset($procedure_id)) ? $procedure_id : ''; ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="param_ontologyoption_id" value="<?php echo set_value('param_ontologyoption_id', (isset($param_ontologyoption_id)) ? $param_ontologyoption_id : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">
<input type="hidden" name="weight" value="<?php echo set_value('weight', (isset($weight)) ? $weight : ''); ?>">
<input type="hidden" name="ontology_group_id" value="<?php echo set_value('ontology_group_id', (isset($ontology_group_id)) ? $ontology_group_id : ''); ?>">

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="param_ontologyoption_id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('param_ontologyoption_id', (isset($param_ontologyoption_id)) ? $param_ontologyoption_id : ''); ?>"></td>
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
	<td><label for="ontology_group_id" class="required">Ontology Group</label></td>
	<td>
	<select name="ontology_group_id" id="ontology_group_id" style="min-width:140px" disabled="disabled">
	<option value="">&nbsp;</option>
	<?php
	$ci =& get_instance();
	$ci->load->model('ontologygroupmodel');
	$chosen = set_value('ontology_group_id', (isset($ontology_group_id)) ? $ontology_group_id : '');
	foreach ($ci->ontologygroupmodel->fetchAll() as $item) {
		$selected = ($chosen == $item[OntologyGroupModel::PRIMARY_KEY]) ? ' selected' : '';
		echo '<option value="' . $item[OntologyGroupModel::PRIMARY_KEY] . '"' . $selected . '>' . e($item['name']) . '</option>' . PHP_EOL;
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="ontology_term" class="required">Ontology Term</label></td>
	<td><input type="text" name="ontology_term" id="ontology_term" title="<?php tooltip('ontology_term') ?>" value="<?php echo set_value('ontology_term', (isset($ontology_term)) ? $ontology_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="ontology_id" class="required">Ontology ID</label></td>
	<td><input type="text" name="ontology_id" id="ontology_id" title="<?php tooltip('ontology_id') ?>" value="<?php echo set_value('ontology_id', (isset($ontology_id)) ? $ontology_id : ''); ?>"></td>
</tr>
<tr>
	<td><label for="is_collapsed">Is Collapsed</label></td>
	<td><input type="checkbox" name="is_collapsed" title="<?php tooltip('is_collapsed') ?>"<?php echo set_value('is_collapsed', (@$is_collapsed) ? ' checked' : ''); ?>></td>
</tr>
<tr>
	<td><label for="is_active">Is Active</label></td>
	<td><input type="checkbox" name="is_active" title="<?php tooltip('option_active') ?>"<?php if($mode=='I') echo ' checked'; else echo set_value('is_active', (@$is_active) ? ' checked' : ''); ?>></td>
</tr>
<tr>
	<td><label for="is_default">Is Default Option?</label></td>
	<td><input type="checkbox" name="is_default" title="<?php tooltip('option_default') ?>"<?php echo set_value('is_default', (@$is_default) ? ' checked' : ''); ?>></td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>
</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('document').ready(function(){ontologysearch('ontology_id', 'ontology_term');});
</script>
