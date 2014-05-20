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
<input type="hidden" name="param_eqterm_id" value="<?php echo set_value('param_eqterm_id', (isset($param_eqterm_id)) ? $param_eqterm_id : ''); ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">
<input type="hidden" name="weight" value="<?php echo set_value('weight', (isset($weight)) ? $weight : ''); ?>">

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>
	<td><label for="param_eqterm_id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('param_eqterm_id', (isset($param_eqterm_id)) ? $param_eqterm_id : ''); ?>"></td>
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
		echo "<option value='$sex'$select> $sex </option>\n";
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
		$select = ($chosenValue == $so) ? ' selected' : '';
		echo "<option value='$so'$select> $so </option>\n";
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td><label for="entity1_term" class="required">Entity 1 Term</label></td>
	<td><input type="text" name="entity1_term" id="entity1_term" title="<?php tooltip('entity_term') ?>" value="<?php echo set_value('entity1_term', (isset($entity1_term)) ? $entity1_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="entity1_id" class="required">Entity 1 ID</label></td>
	<td><input type="text" name="entity1_id" id="entity1_id" title="<?php tooltip('entity_id') ?>" value="<?php echo set_value('entity1_id', (isset($entity1_id)) ? $entity1_id : ''); ?>"></td>
</tr>
<tr>
	<td><label for="entity2_term">Entity 2 Term</label></td>
	<td><input type="text" name="entity2_term" id="entity2_term" title="<?php tooltip('entity_term') ?>" value="<?php echo set_value('entity2_term', (isset($entity2_term)) ? $entity2_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="entity2_id">Entity 2 ID</label></td>
	<td><input type="text" name="entity2_id" id="entity2_id" title="<?php tooltip('entity_id') ?>" value="<?php echo set_value('entity2_id', (isset($entity2_id)) ? $entity2_id : ''); ?>"></td>
</tr>
<tr>
	<td><label for="entity3_term">Entity 3 Term</label></td>
	<td><input type="text" name="entity3_term" id="entity3_term" title="<?php tooltip('entity_term') ?>" value="<?php echo set_value('entity3_term', (isset($entity3_term)) ? $entity3_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="entity3_id">Entity 3 ID</label></td>
	<td><input type="text" name="entity3_id" id="entity3_id" title="<?php tooltip('entity_id') ?>" value="<?php echo set_value('entity3_id', (isset($entity3_id)) ? $entity3_id : ''); ?>"></td>
</tr>
<tr>
	<td><label for="quality1_term" class="required">Quality 1 Term</label></td>
	<td><input type="text" name="quality1_term" id="quality1_term" title="<?php tooltip('quality_term') ?>" value="<?php echo set_value('quality1_term', (isset($quality1_term)) ? $quality1_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="quality1_id" class="required">Quality 1 ID</label></td>
	<td><input type="text" name="quality1_id" id="quality1_id" title="<?php tooltip('quality_id') ?>" value="<?php echo set_value('quality1_id', (isset($quality1_id)) ? $quality1_id : ''); ?>"></td>
</tr>
<tr>
	<td><label for="quality2_term">Quality 2 Term</label></td>
	<td><input type="text" name="quality2_term" id="quality2_term" title="<?php tooltip('quality_term') ?>" value="<?php echo set_value('quality2_term', (isset($quality2_term)) ? $quality2_term : ''); ?>"></td>
</tr>
<tr>
	<td><label for="quality2_id">Quality 2 ID</label></td>
	<td><input type="text" name="quality2_id" id="quality2_id" title="<?php tooltip('quality_id') ?>" value="<?php echo set_value('quality2_id', (isset($quality2_id)) ? $quality2_id : ''); ?>"></td>
</tr>
<!--<tr>
	<td><label for="weight">Weight</label></td>
	<td><input type="text" name="weight" title="<?php //tooltip('weight') ?>" value="<?php //echo set_value('weight', (isset($weight)) ? $weight : ''); ?>"></td>
</tr>-->
<tr>
	<td></td>
	<td><input type="submit" name="submit" value="Submit" id="submit"></td>
</tr>

</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('document').ready(function(){
	ontologysearch('entity1_id', 'entity1_term');
	ontologysearch('entity2_id', 'entity2_term');
	ontologysearch('entity3_id', 'entity3_term');
	ontologysearch('quality1_id', 'quality1_term');
	ontologysearch('quality2_id', 'quality2_term');
});
</script>
