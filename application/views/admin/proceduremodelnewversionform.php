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

<h3>New Procedure Version Creation Required</h3>

<p>The changes you are making to the Procedure will impact on the submission of data so in order 
to stop submissions failing you will need to create a new version of the Procedure and use this 
new Procedure to submit your data.</p>

<table id="edit">
<tr>
	<td>
	<?php echo form_open(null, array('id'=>'addeditform')); ?>
	<input type="hidden" name="allvalues"     value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
	<input type="hidden" name="pipeline_id"   value="<?php echo $pipeline_id; ?>">
	<input type="hidden" name="procedure_id"  value="<?php echo $procedure_id; ?>">
	<input type="hidden" name="procedure_key" value="<?php echo $procedure_key; ?>">
	<input type="hidden" name="type"          value="<?php echo $type; ?>">
	<input type="hidden" name="name"          value="<?php echo form_prep($name); ?>">
	<input type="hidden" name="is_visible"    value="<?php echo @$is_visible; ?>">
	<input type="hidden" name="is_active"     value="<?php echo @$is_active; ?>">
	<input type="hidden" name="is_internal"   value="<?php echo @$is_internal; ?>">
	<input type="hidden" name="is_mandatory"  value="<?php echo @$is_mandatory; ?>">
	<input type="hidden" name="level"         value="<?php echo @$level; ?>">
	<input type="hidden" name="major_version" value="<?php echo $major_version; ?>">
	<input type="hidden" name="minor_version" value="<?php echo $minor_version; ?>">
	<input type="hidden" name="week"          value="<?php echo $week; ?>">
	<input type="hidden" name="min_females"   value="<?php echo $min_females; ?>">
	<input type="hidden" name="min_males"     value="<?php echo $min_males; ?>">
	<input type="hidden" name="min_animals"   value="<?php echo $min_animals; ?>">
	<input type="hidden" name="description"   value="<?php echo form_prep($description); ?>">
	<input type="hidden" name="time_modified" value="<?php echo $time_modified; ?>">
	<input type="hidden" name="user_id"       value="<?php echo $user_id; ?>">
	<div id="newmajorversionfields">
	<?php
	$this->load->view('admin/newmajorversionfields',
            array(
                'controller' => $controller,
                'selectedPipeline' => $pipeline_id,
                'selectedProcedure' => $procedure_id,
                'pipelines' => PipelinesFetcher::fetchAll()
            )
	);
	?>
	</div>
	<br>
	<input type="hidden" id="nvsubmitbuttonclicked" name="nvsubmitbuttonclicked" value="">
	<input type="hidden" name="new_major_version_submit" value="Create a new Major Version">
	<input type="submit" name="new_major_version_submit" id="nvsubmit" value="Create a new Major Version">
	<?php echo form_close(); ?>
	</td>
</tr>
</table>

<script type="text/javascript">
$('#nvsubmit').click(function(e){
	//submit the new version form but first set the button as clicked (to fix jquery.submit() bug)
	$('#nvsubmitbuttonclicked').val('1');
});
</script>
