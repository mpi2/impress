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

<p>The changes you are making will impact on the submission of data so in order 
to stop submissions failing you will need to create a new version of the Parameter and use this 
new Parameter to submit your data.</p>

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="allvalues"     value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id"   value="<?php echo $pipeline_id; ?>">
<input type="hidden" name="procedure_id"  value="<?php echo $procedure_id; ?>">
<input type="hidden" name="parameter_id"  value="<?php echo $parameter_id; ?>">
<input type="hidden" name="parameter_key" value="<?php echo $parameter_key; ?>">
<input type="hidden" name="type"          value="<?php echo $type; ?>">
<input type="hidden" name="name"          value="<?php echo form_prep($name); ?>">
<input type="hidden" name="visible"       value="<?php echo @$visible; ?>">
<input type="hidden" name="active"        value="<?php echo @$active; ?>">
<input type="hidden" name="internal"      value="<?php echo @$internal; ?>">
<input type="hidden" name="major_version" value="<?php echo $major_version; ?>">
<input type="hidden" name="minor_version" value="<?php echo $minor_version; ?>">
<input type="hidden" name="is_derived"    value="<?php echo @$is_derived; ?>">
<input type="hidden" name="is_increment"  value="<?php echo @$is_increment; ?>">
<input type="hidden" name="is_option"     value="<?php echo @$is_option; ?>">
<input type="hidden" name="is_required"   value="<?php echo @$is_required; ?>">
<input type="hidden" name="is_important"  value="<?php echo @$is_important; ?>">
<input type="hidden" name="is_annotation" value="<?php echo @$is_annotation; ?>">
<input type="hidden" name="derivation"    value="<?php echo form_prep($derivation); ?>">
<input type="hidden" name="unit"          value="<?php echo $unit; ?>">
<input type="hidden" name="qc_check"      value="<?php echo @$qc_check; ?>">
<input type="hidden" name="qc_min"        value="<?php echo $qc_min; ?>">
<input type="hidden" name="qc_max"        value="<?php echo $qc_max; ?>">
<input type="hidden" name="qc_notes"      value="<?php echo $qc_notes; ?>">
<input type="hidden" name="value_type"    value="<?php echo $value_type; ?>">
<input type="hidden" name="graph_type" 	  value="<?php echo $graph_type; ?>">
<input type="hidden" name="description"   value="<?php echo form_prep($description); ?>">
<input type="hidden" name="time_modified" value="<?php echo $time_modified; ?>">
<input type="hidden" name="user_id"       value="<?php echo $user_id; ?>">
<input type="hidden" name="data_analysis_notes" value="<?php echo form_prep($data_analysis_notes); ?>">

<table id="edit">

<tr>
    <td>
    <div id="newmajorversionfields">
    <?php
    $pipeline = new Pipeline($pipeline_id);
    $ci =& get_instance();
    $this->load->view('admin/newmajorversionfields', array(
        'controller' => $controller,
        'selectedPipeline' => $pipeline_id,
        'selectedProcedure' => $procedure_id,
        'pipelines' => PipelinesFetcher::fetchAll(),
        'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()), //$pipeline->getProcedures(),
        'setChecked' => $ci->proceduremodel->isInBeta($procedure_id) //if we are editing an v1 parameter in a newly created procedure version then we don't want to create another version of this procedure, just v2 of this parameter
    ));
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
