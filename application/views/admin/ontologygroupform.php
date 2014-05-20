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
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="procedure_id" value="<?php echo set_value('procedure_id', (isset($procedure_id)) ? $procedure_id : ''); ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="ontology_group_id" value="<?php echo set_value('ontology_group_id', (isset($ontology_group_id)) ? $ontology_group_id : ''); ?>">

<table id="edit">

<tr>
	<td><label for="parameter" class="required">Parameter</label></td>
	<?php $parameter = new Parameter($parameter_id); ?>
	<td><input type="text" name="parameter" disabled="disabled" value="<?php echo form_prep($parameter->getItemName()); ?>"></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>	
	<td><label for="ontology_group_id">Id</label></td>
	<td><input type="text" name="ontology_group_id" disabled="disabled" value="<?php echo set_value('ontology_group_id', (isset($ontology_group_id)) ? $ontology_group_id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td><label for="name" class="required">Group Name</label></td>
	<td><input type="text" title="<?php tooltip('ontology_group_name'); ?>" name="name" value="<?php echo set_value('name', (isset($name)) ? $name : ''); ?>"></td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" name="submit" value="Submit" id="submit"></td>
</tr>
</table>

<?php echo form_close();
