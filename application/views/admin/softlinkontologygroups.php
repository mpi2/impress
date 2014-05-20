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
 * @param Pipeline $pipeline The Procedure which the user is residing in
 * @param Procedure $procedure -ibid
 * @param Parameter $parameter -ibid
 * @param OntologyGroup[] $allGroups All Ontology Groups except the ones that currently belong to the Parameter
 * @param string $controller
 */
?>

<p>Please select the Ontology Groups you wish to Soft-Link to the <?php echo e($parameter->getItemName()) . ' [' . $parameter->getItemKey() . ']'; ?> Parameter:</p>

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="pipeline" value="<?php echo $pipeline->getId(); ?>">
<input type="hidden" name="procedure" value="<?php echo $procedure->getId(); ?>">
<input type="hidden" name="parameter" value="<?php echo $parameter->getId(); ?>">

<table style="overflow:visible" cellspacing="10" id="softlinkprocedure">
<tr>
	<!-- Selected Item -->
    <td valign="top" align="center" width="33%">
	<label for="pipeline" class="required">Pipeline</label>
	<br>
    <select name="pipeline" id="pipeline" style="min-width:260px" disabled="disabled">
		<option value="<?php echo $pipeline->getId(); ?>"><?php echo $pipeline->getItemKey() . ' ' . e($pipeline->getItemName()); ?></option>
    </select>
	<br>
	<label for="procedure" class="required">Procedure</label>
	<br>
	<select name="procedure" id="procedure" style="min-width:260px" disabled="disabled">
		<option value="<?php echo $procedure->getId(); ?>"><?php echo $procedure->getItemKey() . ' ' . e($procedure->getItemName()); ?></option>
	</select>
	<br>
	<label for="parameter" class="required">Parameter</label>
	<br>
	<select name="parameter" id="parameter" style="min-width:260px" disabled="disabled">
		<option value="<?php echo $parameter->getId(); ?>"><?php echo $parameter->getItemKey() . ' ' . e($parameter->getItemName()); ?></option>
	</select>
	<br>
	</td>
	
	<!-- Ontology Groups -->
	<td valign="top" align="center" width="33%">
	<select name="groups[]" id="groups" size="10" multiple="multiple" style="min-width:260px">
	<?php
	foreach ($allGroups as $og)
		echo '<option value="' . $og->getId() . '">' . e($og->getName()) . '</option>' . PHP_EOL;
	?>
	</select>
	<br><p style="font-size:small">Hold down the Control (Ctrl) or Command button on your keyboard to select multiple items</p>
	</td>
	
	<!-- Options in the selected Group -->
	<td valign="top" align="center" width="33%">
	<select name="ontologyoptions" id="ontologyoptions" size="10" disabled="disabled" style="min-width:260px">
	</select>
	<br><p style="font-size:small">Preview of Ontology Options in the Ontology Group selected</p>
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
$('#groups').click(function(e){
	$.ajax({
		url: "<?php echo base_url() . 'ajax/getOntologyOptions/' ?>" + $(this).val()[$(this).val().length - 1],
		success: function(ontologyoptions){
			$('#ontologyoptions').empty();
			$.each(ontologyoptions, function(){
				$('#ontologyoptions').append(
					$('<option></option>')
					.attr('value', this.id)
					.text('[' + this.ontology_id + '] ' + this.ontology_term)
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
