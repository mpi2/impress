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
* @param Pipeline $srcPipeline
* @param Procedure $srcProcedure
* @param Parameter $srcParameter
* @param int $destPipelineId
* @param int $destProcedureId
* @param string $errors
* @param string $flash
* @param string $controller
*/
?>

<h2>Parameter Cloning</h2>

<p>Please select the Pipeline and Procedure you want to clone this Parameter into and explain the intended relationship between them:</p>

<?php if(isset($flash) && ! empty($flash)) echo $flash; ?>

<?php if(isset($errors) && ! empty($errors)) echo $errors; ?>

<fieldset><legend>Parameter Cloning</legend>

<?php echo form_open(null, array('id'=>'addeditform')); ?>

<table width="95%" cellspacing="10" id="cloneparameter">
<tr>
    <td width="20%"></td>
    <td width="80%"></td>
</tr>
<tr>
    <th colspan="2">Original Item</th>
</tr>
<tr>
    <td></td>
    <td></td>
</tr>
<tr>
    <th><label for="srcPipeline">Pipeline</label></th>
    <td>
    <select name="srcPipeline" id="srcPipeline" disabled="disabled">
        <?php echo '<option value="' . $srcPipeline->getId() . '" selected>' . $srcPipeline->getItemKey() . ' ' . e($srcPipeline->getItemName()) . '</option>'; ?>
    </select>
    </td>
</tr>
<tr>
    <th><label for="srcProcedure">Procedure</label></th>
    <td>
    <select name="srcProcedure" id="srcProcedure" disabled="disabled">
        <?php echo '<option value="' . $srcProcedure->getId() . '" selected>' . $srcProcedure->getItemKey() . ' ' . e($srcProcedure->getItemName()) . '</option>'; ?>
    </select>
    </td>
</tr>
<tr>
    <th><label for="srcParameter">Parameter</label></th>
    <td>
    <select name="srcParameter" id="srcParameter" disabled="disabled">
        <?php echo '<option value="' . $srcParameter->getId() . '" selected>' . $srcParameter->getItemKey() . ' ' . e($srcParameter->getItemName()) . '</option>'; ?>
    </select>
    </td>
</tr>
<tr>
    <td></td>
    <td></td>
</tr>
<tr>
    <th colspan="2">Destination for your cloned Item</th>
</tr>
<tr>
    <th><label for="destPipeline" class="required">Pipeline</label></th>
    <td>
    <select name="destPipeline" id="destPipeline">
	<option>&nbsp;</option>
	<?php
	$chosen = set_value('destPipeline');
	$chosen = (empty($chosen)) ? $destPipelineId : $chosen;
	foreach (PipelinesFetcher::fetchAll() AS $pip) {
		if (($pip->isDeprecated() === false)) { // && ($pip->getId() != $srcPipeline->getId()) //prevented an item being cloned into the same pipeline
			echo "<option value='" . $pip->getId() . "'"
			   . (($pip->getId() == $chosen) ? ' selected' : '')
			   . ">" . $pip->getItemKey() . " " . e($pip->getItemName()) . "</option>\n";
		}
	}
	?>
    </select>
    </td>
</tr>
<tr>
    <th><label for="destProcedure" class="required">Procedure</label></th>
    <td>
    <select name="destProcedure" id="destProcedure">
	<option>&nbsp;</option>
	<?php
	$chosen = set_value('destProcedure');
	$chosen = (empty($chosen)) ? $destProcedureId : $chosen;
	$destPipeline = new Pipeline($destPipelineId);
	if ($destPipeline->exists() && ($destPipeline->isDeprecated() === false)) { // && ($destPipeline->getId() != $srcPipeline->getId()) //see above
            foreach (PipelineHasProcedures::fetchAll($destPipeline->getId()) as $proc) { //$destPipeline->getProcedures() AS
                if ( ! $proc->isDeprecated() && ! $proc->isDeleted() && ($srcProcedure->getId() != $proc->getId())) { //prevent parameter being cloned into procedure it came from
                    echo "<option value='{$proc->getId()}'"
                       . (($proc->getId() == $chosen) ? ' selected' : '')
                       . ">{$proc->getItemKey()} " . e($proc->getItemName()) . "</option>\n";
                }
            }
	}
	?>
    </select>
    </td>
</tr>
<tr>
    <th valign="top"><label>Options</label></th>
    <td>
        <input type="checkbox" name="cloneMPs" checked> Clone MP Ontologies?<br>
        <input type="checkbox" name="cloneEQs" checked> Clone EQ Ontologies?<br>
        <input type="checkbox" name="cloneOptions" checked> Clone Options?<br>
        <input type="checkbox" name="cloneIncrements" checked> Clone Increments?
    </td>
</tr>
<tr>
    <td></td>
    <td></td>
</tr>
<tr>
    <th valign="top"><label>Relationship of clone to original item</label></th>
    <td>
        <label for='nvrelation' class='required'>Relationship</label>
        <select name='nvrelation' id='nvrelation' title="<?php tooltip('nv_relation') ?>">
			<?php
            foreach(ERelationType::__toArray() AS $relation)
                echo '<option value="' . $relation . '">' . $relation . '</option>' . PHP_EOL;
			?>
        </select>
        <br>
        <label for="nvrelationdescription">Explanation</label><br>
        <textarea name='nvrelationdescription' id='nvrelationdescription' style="width:350px" title="<?php tooltip('nv_relationdescription') ?>"><?php echo set_value('nvrelationdescription'); ?></textarea>
    </td>
</tr>
<tr>
    <td><input type="submit" name="submit" id="submit" value="Clone It"></td>
    <td></td>
</tr>
</table>

<?php echo form_close(); ?>

</fieldset>

<script type="text/javascript">
$('#destPipeline').change(function(e){
    $.ajax({
        url: '<?php echo base_url() . 'ajax/getProcedures/'; ?>' + $(this).val(),
        success: function(procedures){
            $('#destProcedure').empty().append($('<option></option>'));
            $.each(procedures, function(){
				//prevent parameter being cloned into procedure it came from
				if (this.procedure_id != <?php echo $srcProcedure->getId(); ?>) {
					$('#destProcedure').append(
						$('<option></option>')
						.attr('value', this.procedure_id)
						.text(this.procedure_key + ' ' + this.name)
					);
				}
            });
        }
    });
});
</script>
