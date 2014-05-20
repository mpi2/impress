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
 * @param Pipeline[] $pipelines
 * @param int $pipelineId The pipeline which the user is residing in - we check
 * for this as the user is not meant to import Procedures from the same Pipeline
 * they want to import into
 * @param Procedure $procedure The Procedure which the user is residing in- ibid
 * @param string $controller
 */
?>

<p>Please select the Parameters you wish to Soft-Link into the <?php echo e($procedure->getItemName()) . ' [' . $procedure->getItemKey() . ']'; ?> Procedure:</p>
<p>Please note that Soft-linked items are not editable from outside their original Pipeline and that any changes to the original item will reflect in your Pipeline.</p>

<?php echo form_open(null, array('id'=>'addeditform')); ?>

<table style="overflow:visible" cellspacing="10" id="softlinkprocedure">
<tr>
    <td valign="top" align="center" width="33%">
	<label for="pipeline" class="required">Pipeline</label>
	<br>
    <select name="pipeline" id="pipeline" size="7" style="min-width:260px">
	<?php
	$chosen = set_value('pipeline');
	foreach ($pipelines AS $pip) {
		if ($pip->getId() != $pipelineId) {
			echo "<option value='" . $pip->getId() . "'"
			   . (($pip->getId() == $chosen) ? ' selected' : '')
			   . ">" . $pip->getItemKey() . " " . $pip->getItemName() . "</option>\n";
		}
	}
	?>
    </select>
    </td>
    <td valign="top" align="center" width="33%">
	<label for="procedure" class="required">Procedures</label>
	<br>
	<select name="procedure" id="procedure" size="7" style="min-width:260px">
	</select>
	</td>
	<td valign="top" align="center" width="33%">
	<label for="parameters[]" class="required">Parameters</label>
	<br>
	<select name="parameters[]" id="parameters" size="7" multiple="multiple" style="min-width:260px">
	</select>
	<br><p style="font-size:small">Hold down the Control (Ctrl) or Command button on your keyboard to select multiple items</p>
	</td>
</tr>
<tr>
	<td colspan="3" align="center">
	<input type="hidden" name="softlinkimportsubmit" id="softlinkimportsubmit" value="">
	<input type="submit" name="importsubmit" id="importsubmit" value="Import">
	</td>
</tr>
</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('#pipeline').change(function(e){
    $.ajax({
        url: '<?php echo base_url() . 'ajax/getProcedures/'; ?>' + $(this).val(),
        success: function(procedure){
            $('#procedure').empty();
			$('#parameters').empty();
            $.each(procedure, function(){
				if(this.procedure_id != <?php echo $procedure->getId(); ?>){
					$('#procedure').append(
						$('<option></option>')
						.attr('value', this.procedure_id)
						.text(this.procedure_key + ' ' + this.name)
					);
				}
            });
        }
    });
});
$('#procedure').change(function(e){
    $.ajax({
        url: '<?php echo base_url() . 'ajax/getParameters/'; ?>' + $(this).val(),
        success: function(parameters){
            $('#parameters').empty();
            $.each(parameters, function(){
                $('#parameters').append(
					$('<option></option>')
					.attr('value', this.parameter_id)
					.text(this.parameter_key + ' ' + this.name)
                );
            });
        }
    });
});
$('#importsubmit').click(function(e){
	e.preventDefault();
	$('#softlinkimportsubmit').val(1);
	$('#addeditform').submit();
	return true;
});
</script>
