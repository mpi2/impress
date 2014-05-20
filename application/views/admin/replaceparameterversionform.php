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

/**
 * @param Parameter $parameter The parameter selected to be versioned
 * @param Procedure $procedure destination procedure
 * @param Pipeline  $pipeline destination pipeline
 * @param int $oldoptionid Optional
 * @param string $itemType parameter or paramoption
 * @param string $controller
 */
?>

<h2>Replace a Parameter with a new version</h2>

<p>Please confirm you wish to replace the selected Parameter with a new version into the chosen Procedure:</p>

<?php if(isset($flash) && ! empty($flash)) echo $flash; ?>

<?php if(isset($errors) && ! empty($errors)) echo $errors; ?>

<fieldset><legend>Parameter Versioning</legend>

<?php echo form_open(null, array('id'=>'addeditform')); ?>

<table width="50%" style="width:60%" id="parameterversioning">
<!--<tr>
	<th><label for="pipeline_id" class="required">Pipeline</label></th>
	<td><select name="pipeline_id" id="pipeline_id">
	<option value="<?php echo $pipeline->getId(); ?>"><?php echo $pipeline->getItemKey() . ' - ' . $pipeline->getItemName(); ?></option>
	</select></td>	
</tr>
<tr>
	<th><label for="procedure_id" class="required">Procedure</label></th>
	<td><select name="procedure_id" id="procedure_id">
	<option value="<?php echo $procedure->getId(); ?>"><?php echo $procedure->getItemKey() . ' - ' . $procedure->getItemName(); ?></option>
	</select></td>
</tr>-->
<tr>
	<input type="hidden" name="parameter_id" value="<?php echo $parameter->getId(); ?>">
        <input type="hidden" name="deleteolditem" value="1">
        <input type="hidden" name="oldoptionid" value="<?php echo $oldoptionid; ?>">
	<th><label for="parameter_id" class="required">Parameter</label></th>
	<td><select name="parameter_id" id="parameter_id" disabled="disabled">
	<option value="<?php echo $parameter->getId(); ?>"><?php echo $parameter->getItemKey() . ' - ' . $parameter->getItemName(); ?></option>
	</select></td>
</tr>
<tr>
	<th valign="top"><label class="required">Describe new version</label></th>
	<td valign="top">
	<div id="newmajorversionfields">
	<?php $this->load->view('admin/newmajorversionfields', 
		array(
			'controller' => $controller,
			'pipelines' => PipelinesFetcher::fetchAll(),
			'selectedPipeline' => $pipeline->getId(),
			'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()), //$pipeline->getProcedures(),
			'selectedProcedure' => $procedure->getId()
		)
	);
	?>
	</div>
	<input type="hidden" id="nvsubmitbuttonclicked" name="nvsubmitbuttonclicked" value="">
	<input type="hidden" name="new_major_version_submit" value="Create a new Version">
	<input type="submit" name="new_major_version_submit" id="nvsubmit" value="Create a new Version">
	</td>
</tr>
</table>

<?php echo form_close(); ?>

</fieldset>


<script type="text/javascript">
$(document).ready(function(){
	$('#nvforkprocedure').attr('checked', false);
});
$('#nvsubmit').click(function(e){
	//submit the new version form but first set the button as clicked (to fix jquery.submit() bug)
	e.preventDefault();
	$('#nvsubmitbuttonclicked').val('1');
	$('#addeditform').submit();
});
</script>
