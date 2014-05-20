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
 * @param mixed ${*} All the fields that were submitted from the previous page
 * @param string $allvalues
 * @param string $controller
 */
?>

<h3>New Parameter Version Creation Required</h3>

<p>The changes you are making to this Option will impact on the submission of data so in order 
to stop submissions failing you will need to create a new version of the Parameter and use this 
new Parameter to submit your data.</p>

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="allvalues"       value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id"     value="<?php echo $pipeline_id; ?>">
<input type="hidden" name="procedure_id"    value="<?php echo $procedure_id; ?>">
<input type="hidden" name="parameter_id"    value="<?php echo $parameter_id; ?>">
<input type="hidden" name="param_option_id" value="<?php echo $param_option_id; ?>">
<input type="hidden" name="user_id"         value="<?php echo $user_id; ?>">
<input type="hidden" name="name"            value="<?php echo form_prep($name); ?>">
<input type="hidden" name="parent_id"       value="<?php echo $parent_id; ?>">
<input type="hidden" name="description"     value="<?php echo form_prep($description); ?>">
<input type="hidden" name="is_active"       value="<?php echo @$is_active; ?>">
<input type="hidden" name="is_default"      value="<?php echo @$is_default; ?>">
<input type="hidden" name="time_modified"   value="<?php echo $time_modified; ?>">

<table id="edit">

<tr>
    <td>Option Version Relationships:</td>
</tr>
<tr>
	<td>
	<div id="newoptionmajorversionfields">
	<?php $this->load->view('admin/newoptionversionfields'); ?>
	</div>
	</td>
</tr>
<tr style="background-color:inherit">
    <td></td>
</tr>
<tr>
    <td>Parameter/Procedure Version Relationships:</td>
</tr>
<tr>
	<td>
	<div id="newmajorversionfields">
	<?php
	$pipeline = new Pipeline($pipeline_id);
	$ci =& get_instance();
	$this->load->view('admin/newmajorversionfields', 
            array(
                'controller' => $controller,
                'selectedPipeline' => $pipeline_id,
                'selectedProcedure' => $procedure_id,
                'pipelines' => PipelinesFetcher::fetchAll(),
                'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()), //$pipeline->getProcedures(),
                'setChecked' => $ci->proceduremodel->isInBeta($procedure_id) //if we are editing an v1 parameter in a newly created procedure version then we don't want to create another version of this procedure, just v2 of this parameter
            )
	);
	?>
	</div>
	<input type="hidden" id="nvsubmitbuttonclicked" name="nvsubmitbuttonclicked" value="">
	<input type="hidden" name="new_major_version_submit" value="Create a new Major Version">
	<input type="submit" name="new_major_version_submit" id="nvsubmit" value="Create a new Major Version">
	</td>
</tr>

</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('#nvsubmit').click(function(e){
	//submit the new version form but first set the button as clicked (to fix jquery.submit() bug)
	e.preventDefault();
	$('#nvsubmitbuttonclicked').val('1');
	$('#addeditform').submit();
});
</script>
