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
 * @param Procedure[] $procedures may be empty
 * @param int $selectedPipeline may be empty
 * @param int $selectedProcedure may be empty
 * @param string $xmldir
 * @param array $xmlfiles array of hashs with keys filename and procname
 * @param string $controller
 */
?>

<h1>Import Parameters</h1>

<p>In order to import your new Parameters into your Procedure you need to have generated an XML file that contains all
 the parameters, including options, increments and ontologies, and placed it into the required location -
 <i><?php echo str_replace('\\', '/', $xmldir); ?></i>.
 When you have done this you will find your XML file listed below.</p>
<p>To import an XML file, the Procedure into which you wish to import your new Parameters needs to have already been
 created.</p>

<?php echo form_open($controller . '/importxml', array('name' => 'selectxmlform', 'id' => 'addeditform', 'onsubmit' => "return confirm('Are you sure?')")); ?>

<!-- <form method="post" action="' . base_url() . $this->_controller . '/importxml" name="selectxmlform" id="selectxmlform" onsubmit="return confirm(\'Are you sure?\')">' . PHP_EOL; -->

<fieldset><legend>Import Parameters</legend>
<table id="xmlimport">
<tr>
	<td colspan="2">
		<p>Select the XML file you want to Import into an existing Procedure:</p>
	</td>
</tr>
<tr>
	<th width="14%">XML Files:</th>
	<td id="xmlfilelist">
		<select name="xmlfile" id="xmlfileselect">
		<?php
		foreach((array)$xmlfiles AS $x){
			echo '<option value="' . e($x['filename']) . '"> ' . e($x['procname']) . ' (' . e($x['filename']) . ')</option>' . PHP_EOL;
		}
		?>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2">
		<p>Please select the Pipeline in which your Procedure exists and then select the Procedure you wish to import into:</p>
	</td>
</tr>
<tr>
	<th>Pipeline/Procedure:</th>
	<td id="procfilelist">
		<select name="pipid" id="pipidselect">
			<option>&nbsp; </option>
			<?php
			foreach ($pipelines AS $p) {
				$chosen = ($p->getId() == $selectedPipeline) ? ' selected="selected"' : '';
				echo '<option value="' . $p->getId() . '"' . $chosen . '>' . e($p->getItemName()) . '</option>' . PHP_EOL;
			}
			?>
		</select>
		<select name="procid" id="procidselect" style="min-width:250px">
			<option>&nbsp; </option>
			<?php
			foreach ($procedures as $p) {
				$chosen = ($p->getId() == $selectedProcedure) ? ' selected="selected"' : '';
				echo '<option value="' . $p->getId() . '"' . $chosen . '>' . $p->getItemKey() . ' - ' . e($p->getItemName()) . '</option>' . PHP_EOL;
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2">
		<p>Please select the mode in which your Parameters are inserted into the database:</p>
	</td>
</tr>
<tr>
	<th valign="top">Import mode:</th>
	<td>
		<input type="radio" name="importmode" value="delete" checked="checked"> Delete existing parameters and add new ones<br>
		<input type="radio" name="importmode" value="append"> Leave existing parameters and add these ones
	</td>
</tr>
<tr>
	<td></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td>
		<input type="submit" name="submitxmlform" id="submit" value="Import XML into Procedure">
	</td>
</tr>
</table>
</fieldset>

<?php echo form_close(); ?>

<script type="text/javascript">
var pipeUrl = '<?php echo base_url() . 'ajax/getprocedures/'; ?>';
var resultsUrl = '<?php echo base_url() . $controller . '/getchangehistory/'; ?>';
$('#pipidselect').change(function(e){
	$('#procidselect').empty().append($('<option></option>').text(' '));
	if ($(this).val() == "") {
		return false;
	} else {
		$.ajax({
			url: pipeUrl + $(this).val(),
			success: function(procedures){
				$.each(procedures, function(){
					$('#procidselect').append(
						$('<option></option>')
						.attr('value', this.procedure_id)
						.text(this.procedure_key + ' - ' + this.name)
					);
				});
			}
		});
	}
});
</script>
