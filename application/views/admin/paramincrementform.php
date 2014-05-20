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

<?php
echo form_open(null, array('id' => 'addeditform'));
?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id" value="<?php echo (isset($pipeline_id)) ? $pipeline_id : ''; ?>">
<input type="hidden" name="procedure_id" value="<?php echo (isset($procedure_id)) ? $procedure_id : ''; ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="param_increment_id" value="<?php echo set_value('param_increment_id', (isset($param_increment_id)) ? $param_increment_id : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">
<input type="hidden" name="weight" value="<?php echo set_value('weight', (isset($weight)) ? $weight : ''); ?>">

<table id="edit">

<tr>
	<td><label for="parameter">Parameter</label></td>
	<?php $parameter = new Parameter($parameter_id); ?>
	<td><input type="text" disabled="disabled" name="parameter" value="<?php echo form_prep($parameter->getItemName()); ?>"></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="param_increment_id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('param_increment_id', (isset($param_increment_id)) ? $param_increment_id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td><label for="increment_type" class="required">Increment Type</label></td>
	<td>
	<select name="increment_type" id="increment_type" title="<?php tooltip('increment_type') ?>">
	<option value=""> &nbsp;</option>
	<?php
	$increment_type = set_value('increment_type', (isset($increment_type)) ? $increment_type : '');
	foreach (EIncrementType::getLabels() as $value => $title) {
		$chosen = (strtolower($increment_type) == strtolower($value)) ? ' selected' : '';
		echo "<option value='$value'$chosen>$title</option>\n";
	}
	?>
	</select>
	</td>
</tr>
<tr id="incminrow">
	<td><label for="increment_min">Increment Minimum No. Repeats</label></td>
	<td><input type="text" name="increment_min" id="increment_min" title="<?php tooltip('increment_min') ?>" value="<?php echo set_value('increment_min', (isset($increment_min)) ? $increment_min : ''); ?>"></td>
</tr>
<tr>
	<td><label for="increment_string">Increment String</label></td>
	<td><input type="text" name="increment_string" id="increment_string" title="<?php tooltip('increment_string') ?>" value="<?php echo set_value('increment_string', (isset($increment_string)) ? $increment_string : ''); ?>"></td>
</tr>
<tr>
	<td><label for="increment_unit">Increment Unit</label></td>
	<td><select name="increment_unit" title="<?php tooltip('increment_unit') ?>">
	<?php
	$increment_unit = set_value('increment_unit', (isset($increment_unit)) ? $increment_unit : '');
	foreach (EIncrementUnit::__toArray() as $unit) {
		$chosen = ($increment_unit == $unit) ? ' selected' : '';
		echo "<option value='$unit'$chosen>" . ucfirst($unit) . "</option>\n";
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="parameter_type" class="required">Parameter Type</label>
	<td><select name="parameter_type" id="parameter_type" title="<?php tooltip('parameter_type_for_increment') ?>">
		<option value=""> &nbsp;</option>
		<option value="<?php echo EParamType::SERIES; ?>"<?php echo ($parameter->getType() == EParamType::SERIES) ? ' selected' : ''; ?>><?php echo EParamType::SERIES; ?></option>
		<option value="<?php echo EParamType::SERIES_MEDIA; ?>"<?php echo ($parameter->getType() == EParamType::SERIES_MEDIA) ? ' selected' : ''; ?>><?php echo EParamType::SERIES_MEDIA; ?></option>
	</select></td>
</tr>
<tr>
	<td><label for="is_active">Is Active</label></td>
	<td><input type="checkbox" name="is_active" title="<?php tooltip('increment_active') ?>" <?php echo set_value('is_active', (@$is_active) ? 'checked' : ''); if($mode=='I') echo 'checked'; ?>></td>
</tr>

<!--<tr>
	<td><label for="weight">Weight</label></td>
	<td><input type="text" name="weight" title="<?php //tooltip('weight') ?>" value="<?php //echo set_value('weight', (isset($weight)) ? $weight : ''); ?>"></td>
</tr>-->

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
	<td><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>

</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('#increment_type').on('change', function(){
	if($(this).val() == "<?php echo EIncrementType::REPEAT; ?>" || $(this).val() == "<?php echo EIncrementType::DATETIME; ?>"){
		$('#incminrow').show();
	}else{
		$('#increment_min').val('');
		$('#incminrow').hide();
	}
});
$(document).ready(function(){
	if($('#increment_min').length == 0)
		$('#increment_type').change();
});
</script>
