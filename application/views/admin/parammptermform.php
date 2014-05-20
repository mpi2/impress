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
<input type="hidden" name="param_mpterm_id" value="<?php echo set_value('param_mpterm_id', (isset($param_mpterm_id)) ? $param_mpterm_id : ''); ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">
<input type="hidden" name="weight" value="<?php echo set_value('weight', (isset($weight)) ? $weight : ''); ?>">

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="param_mpterm_id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('param_mpterm_id', (isset($param_mpterm_id)) ? $param_mpterm_id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td><label for="parameter">Parameter</label></td>
	<?php $parameter = new Parameter($parameter_id); ?>
	<td><input type="text" disabled="disabled" name="parameter" value="<?php echo form_prep($parameter->getItemName()); ?>"></td>
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
	<td><label for="option_id">Option Id</label></td>
	<td>
	<?php $param = new Parameter($parameter_id); ?>
	<select name="option_id" title="<?php tooltip('ont_option') ?>"<?php echo ( ! $param->isOption()) ? ' disabled="disabled"' : ''; ?> style="min-width:140px">
	<option value="">&nbsp;</option>
	<?php
	if($param->isOption()){
		$chosenValue = set_value('option_id', (isset($option_id)) ? $option_id : '');
		foreach($param->getOptions() AS $opt){
			$select = ($chosenValue == $opt->getId()) ? ' selected' : '';
			echo "<option value='" . $opt->getId() . "'" . $select . ">(" . $opt->getId() . ") " . ucwords(e($opt->getName())) . "</option>\n";
		}	
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="increment_id">Increment Id</label></td>
	<td>
	<select name="increment_id" title="<?php tooltip('ont_increment') ?>"<?php echo ( ! $param->isIncrement()) ? ' disabled="disabled"' : ''; ?> style="min-width:140px">
	<option value="">&nbsp;</option>
	<?php
	if($param->isIncrement()){
		$chosenValue = set_value('increment_id', (isset($increment_id)) ? $increment_id : '');
		foreach($param->getIncrements() AS $inc){
			$select = ($chosenValue == $inc->getId()) ? ' selected' : '';
			echo "<option value='" . $inc->getId() . "'" . $select . ">(" . $inc->getId() . ") " . ucwords(e($inc->getIncrementString())) . "</option>\n";
		}
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="sex">Sex</label></td>
	<td>
	<select name="sex" title="<?php tooltip('ont_sex') ?>">
	<?php
	$chosenValue = set_value('sex', (isset($sex)) ? $sex : '');
	foreach(ESexType::__toArray() AS $sex){
		$select = ($chosenValue == $sex) ? ' selected' : '';
		echo "<option value='$sex'$select> $sex</option>\n";
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="selection_outcome" class="required">Selection Outcome</label></td>
	<td>
	<select name="selection_outcome" title="<?php tooltip('ont_selection_outcome') ?>">
	<option value="">&nbsp;</option>
	<?php
	$chosenValue = set_value('selection_outcome', (isset($selection_outcome)) ? $selection_outcome : '');
	foreach(ESelectionOutcome::__toArray() AS $so){
		if($so == ESelectionOutcome::SOTRAIT) continue;
		$select = ($chosenValue == $so) ? ' selected' : '';
		echo "<option value='$so'$select> $so </option>\n";
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="mp_term" class="required">Ontology Term</label></td>
	<td><input type="text" name="mp_term" id="mp_term" title="<?php tooltip('mp_term') ?>" value="<?php echo set_value('mp_term', (isset($mp_term)) ? $mp_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="mp_id" class="required">Ontology ID</label></td>
	<td><input type="text" name="mp_id" id="mp_id" title="<?php tooltip('mp_id') ?>" value="<?php echo set_value('mp_id', (isset($mp_id)) ? $mp_id : ''); ?>"></td>
</tr>
<!--<tr>
	<td><label for="weight">Weight</label></td>
	<td><input type="text" name="weight" title="<?php //tooltip('weight') ?>" value="<?php //echo set_value('weight', (isset($weight)) ? $weight : ''); ?>"></td>
</tr>-->
<tr>
	<td></td>
	<td><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>
</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('document').ready(function(){ontologysearch('mp_id', 'mp_term');});
</script>
