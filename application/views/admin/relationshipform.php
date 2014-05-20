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
 * @param string $itemType either paramoption or parameter or procedure
 * @param Pipeline[] $pipelines
 * @param int $selectedPipeline
 * @param Procedure[] $procedures
 * @param int $selectedProcedure
 * @param Parameter[] $parameters
 * @param int $selectedParameter
 * @param ParamOption[] $options
 * @param string $errors
 * @param string $flash
 * @param string $controller
 */
?>

<h2>Create a relationship between items</h2>

<?php echo $flash; ?>

<p>Please select the items you want to create a relationship between:</p>

<?php
if(isset($errors) && ! empty($errors)) echo $errors;
?>

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<table width="95%" cellspacing="10" id="relation">
<tr>
<th width="33%">
	Select an item...
</th>
<th width="33%">
	...to have this type of relationship...
</th>
<th width="33%">
	...with this other item
</th>
</tr>
<tr>
<td>
	<!--Pipeline-->
	<label for='frompipline' class='required'>Pipeline</label>
	<select name="frompipeline" id="frompipeline">
        <option value=""> &nbsp;</option>
	<?php
        $selectedPipeline = set_value('frompipeline', $selectedPipeline);
        foreach ($pipelines as $pip) {
            $chosen =  ($pip->getId() == $selectedPipeline) ? ' selected' : '';
            echo '<option value="' . $pip->getId() . '"' . $chosen . '>' . $pip->getItemKey() . ' ' . e($pip->getItemName()) . '</option>' . PHP_EOL;
        }
	?>
	</select>
	<br>
	<!--Procedure-->
	<label for='fromprocedure' class='required'>Procedure</label>
	<select name="fromprocedure" id="fromprocedure">
        <option value=""> &nbsp;</option>
        <?php
        $selectedProcedure = set_value('fromprocedure', $selectedProcedure);
        foreach ((array)$procedures as $proc) {
            $chosen = ($proc->getId() == $selectedProcedure) ? ' selected' : '';
            echo '<option value="' . $proc->getId() . '"' . $chosen . '>' . $proc->getItemKey() . ' ' . e($proc->getItemName()) . '</option>' . PHP_EOL;
        }
        ?>
        </select>
	<br>
	<!--Parameter-->
	<?php if($itemType == 'parameter' || $itemType == 'paramoption'){ ?>
	<label for='fromparameter' class='required'>Parameter</label>
	<select name="fromparameter" id="fromparameter">
        <option value=""> &nbsp;</option>
        <?php
        $selectedParameter = set_value('fromparameter', $selectedParameter);
        foreach ((array)$parameters as $param) {
            $chosen = ($param->getId() == $selectedParameter) ? ' selected' : '';
            echo '<option value="' . $param->getId() . '"' . $chosen . '>' . $param->getItemKey() . ' ' . e($param->getItemName()) . '</option>' . PHP_EOL;
        }
        ?>
        </select>
	<?php } ?>
        <!--ParamOption-->
        <?php if($itemType == 'paramoption'){ ?>
        <label for="fromparamoption" class="required">Option</label>
        <select name="fromparamoption" id="fromparamoption">
        <option value=""> &nbsp;</option>
        <?php
        $chosenOption = set_value('fromparamoption');
        foreach ((array)$options as $option) {
            $chosen = ($option->getId() == $chosenOption) ? ' selected' : '';
            echo '<option value="' . $option->getId() . '"' . $chosen . '>' . e($option->getName()) . '</option>' . PHP_EOL;
        }
        ?>
        </select>
        <?php } ?>
</td>
<td>
	<!--Relationship-->
	<label for='nvrelation' class='required'>Relationship</label>
	<select name='nvrelation' title="<?php tooltip('relationship_relationship') ?>" id='nvrelation'>
	<?php
        $selectedRelation = set_value('nvrelation');
        foreach (ERelationType::__toArray() as $relation) {
            $chosen = ($relation == $selectedRelation) ? ' selected' : '';
            echo '<option value="' . $relation . '"' . $chosen . '>' . $relation . '</option>' . PHP_EOL;
        }
	?>
	</select>
	<br>
	<label for="nvrelationdescription">Explanation</label>
	<textarea name='nvrelationdescription' title="<?php tooltip('relationship_description') ?>" id='nvrelationdescription'><?php
        echo set_value('nvrelationdescription');
        ?></textarea>
</td>
<td>
	<!--Pipeline-->
	<label for='topipline' class='required'>Pipeline</label>
	<select name="topipeline" id="topipeline"><option value="">&nbsp; </option>
	<?php
        $selectedToPipeline = set_value('topipeline');
        foreach ($pipelines as $pip) {
            $chosen = ($pip->getId() == $selectedToPipeline) ? ' selected' : '';
            echo '<option value="' . $pip->getId() . '"' . $chosen . '>' . $pip->getItemKey() . ' ' . e($pip->getItemName()) . '</option>' . PHP_EOL;
        }
	?>
	</select>
	<br>
	<!--Procedure-->
	<label for='toprocedure' class='required'>Procedure</label>
	<select name="toprocedure" id="toprocedure"></select>
	<br>
	<!--Parameter-->
	<?php if($itemType == 'parameter' || $itemType == 'paramoption'){ ?>
	<label for='toparameter' class='required'>Parameter</label>
	<select name="toparameter" id="toparameter"></select>
	<?php } ?>
        <!--ParamOption-->
        <?php if($itemType == 'paramoption'){ ?>
        <label for="toparamoption" class="required">Option</label>
        <select name="toparamoption" id="toparamoption"></select>
        <?php } ?>
</td>
</tr>
<tr>
<td></td>
<td><input type="submit" name="submit" id="submit" value="Submit"></td>
<td></td>
</tr>
</table>
<?php echo form_close(); ?>

<!--<h3>Existing Relationships</h3>-->
<div id="existingrelations">-</div>


<script type="text/javascript">
function ftproc(procedures, ft){
	//procedures = $.parseJSON(procedures);
	$('#'+ft+'procedure').empty().append($('<option></option>'));
	$('#'+ft+'parameter').empty();
        $('#'+ft+'paramoption').empty();
	$.each(procedures, function(){
		$('#'+ft+'procedure').append(
			  $("<option></option>")
			  .attr('value', this.procedure_id)
			  .text(this.procedure_key + ' ' + this.name)
		);
	});
}
function ftparam(parameters, ft){
	//parameters = $.parseJSON(parameters);
	$('#'+ft+'parameter').empty().append($('<option></option>'));
        $('#'+ft+'paramoption').empty();
	$.each(parameters, function(){
		$('#'+ft+'parameter').append(
			$('<option></option>')
			.attr('value', this.parameter_id)
			.text(this.parameter_key + ' ' + this.name)
		);
	});
}
function ftparamoption(paramoptions, ft){
    //parameters = $.parseJSON(parameters);
    $('#'+ft+'paramoption').empty().append($('<option></option>'));
    $.each(paramoptions, function(){
        $('#'+ft+'paramoption').append(
            $('<option></option>')
            .attr('value', this.param_option_id)
            .text(this.name)
        );
    });
}
function deleteRelationship(id, itemType){
    $.ajax({
        url: "<?php echo base_url() . 'ajax/deleteRelationship/'; ?>" + id + "/" + itemType,
        success: function(response){
            if(response && response.success){
                $("#r" + id).hide('slow');
            } else {
                alert('Error deleting relationship');
            }
        }
    });
}

var pipeUrl  = '<?php echo base_url() . 'ajax/getProcedures/'; ?>';
var procUrl  = '<?php echo base_url() . 'ajax/getParameters/'; ?>';
var paramUrl = '<?php echo base_url() . 'ajax/getParameterOptions/'; ?>';
var relUrl   = '<?php echo base_url() . 'ajax/getRelations/'; ?>';
var procTH   = '<table><tr><th>RowId</th><th>Procedure Id</th><th>Procedure Key</th><th>Relationship</th><th>Other Procedure Id</th><th>Other Procedure Key</th><th>Connection</th><th>Description</th></tr>';
var paramTH  = procTH.replace('Procedure','Parameter');
var paramoptionTH = '<table><tr><td>RowId</th><th>Option Id</th><th>Relationship</th><th>Other Option Id</th><th>Connection</th><th>Description</th></tr>';
var procTF = paramTF = paramoptionTF = '</table>';

$('#frompipeline').change(function(e){
	$.ajax({
			url: pipeUrl + $(this).val(),
			success: function(procedures){ftproc(procedures, 'from');}
	});
});
$('#topipeline').change(function(e){
	$.ajax({
			url: pipeUrl + $(this).val(),
			success: function(procedures){ftproc(procedures, 'to');}
	});
});
$('#fromprocedure').change(function(e){
	if(<?php echo "'$itemType'"; ?> == 'parameter' || <?php echo "'$itemType'"; ?> == 'paramoption'){
		$.ajax({
			url: procUrl + $(this).val() + '/'<?php echo ($itemType == 'paramoption') ? ' + 1' : '' ?>,
			success: function(parameters){ftparam(parameters, 'from');}
		});
	}
});
$('#toprocedure').change(function(e){
	if(<?php echo "'$itemType'"; ?> == 'parameter' || <?php echo "'$itemType'"; ?> == 'paramoption'){
		$.ajax({
			url: procUrl + $(this).val() + '/'<?php echo ($itemType == 'paramoption') ? ' + 1' : '' ?>,
			success: function(parameters){ftparam(parameters, 'to');}
		});
	}
});
$('#fromparameter').change(function(e){
    if(<?php echo "'$itemType'"; ?> == 'paramoption'){
        $.ajax({
            url: paramUrl + $(this).val(),
            success: function(paramoptions){ftparamoption(paramoptions, 'from');}
        });
    }
});
$('#toparameter').change(function(e){
    if(<?php echo "'$itemType'"; ?> == 'paramoption'){
        $.ajax({
            url: paramUrl + $(this).val(),
            success: function(paramoptions){ftparamoption(paramoptions, 'to');}
        });
    }
});

if (<?php echo "'$itemType'"; ?> == 'paramoption') {
    $('#fromparamoption, #toparamoption').change(function(e){
        if ( ! ($('#fromparamoption').val() == null || $('#fromparamoption').val() == '') && ! ($('#toparamoption').val() == null || $('#toparamoption').val() == '')) {
			$.ajax({
				url: relUrl + 'paramoption/' + $('#fromparamoption').val() + '/' + $('#toparamoption').val(),
				success: function(relation){
					var s = '';
					if (relation.length == 0)
						s = '-';
					$.each(relation, function(){
                                                s += "<div id='r" + this.id + "'>";
						s += "A relationship between these two Options already exists - they have a " + this.relationship + " by " + this.connection + " relationship with each other. ";
						if(this.description != null && this.description.length > 0) s+= "Explanation: " + escapeHTML(this.description);
						//s += " <a href=\"<?php echo base_url() . $controller; ?>/itemRelationshipDelete/<?php echo $itemType; ?>/" + this.id + "\" class=\"admindelete\">Delete?</a><br>";
                                                s += " <a href=\"#\" onclick=\"deleteRelationship(" + this.id + ", '<?php echo $itemType; ?>')\" class=\"admindelete\">Delete?</a></div>";
					});
					if(relation.length > 0) $('input[type="submit"]').attr('disabled','disabled');
					else $('input[type="submit"]').removeAttr('disabled');
					$('#existingrelations').html(s);
				}
			});
		} else if ( ! ($('#fromparamoption').val() == null || $('#fromparamoption').val() == '')) {
			$.ajax({
				url: relUrl + 'paramoption/' + $('#fromparamoption').val(),
				success: function(relation){
					var s = '';
					if (relation.length == 0)
						s = '-';
					$.each(relation, function(){
                                                s += "<div id='r" + this.id + "'>";
						s += "Option " + escapeHTML(this.child_option_name) + " of Parameter [" + this.child_parameter_key + "] " + escapeHTML(this.child_parameter_name)
						   + " has a " + this.relationship + " by " + this.connection + " relationship with Option " + escapeHTML(this.parent_option_name) + " of Parameter ["
						   + this.parent_parameter_key + "] " + escapeHTML(this.parent_parameter_name) + ". ";
						if (this.description != null && this.description.length > 0) s += "Explanation: " + escapeHTML(this.description);
						//s += " <a href=\"<?php echo base_url() . $controller; ?>/itemRelationshipDelete/<?php echo $itemType; ?>/" + this.id + "\" class=\"admindelete\">Delete?</a><br>";
                                                s += " <a href=\"#\" onclick=\"deleteRelationship(" + this.id + ", '<?php echo $itemType; ?>')\" class=\"admindelete\">Delete?</a></div>";
					});
					$('input[type="submit"]').removeAttr('disabled');
					$('#existingrelations').html(s);
				}
			});
		}
    });
}else if(<?php echo "'$itemType'"; ?> == 'parameter'){
	$('#fromparameter, #toparameter').change(function(e){
		if( ! ($('#fromparameter').val() == null || $('#fromparameter').val() == '') && ! ($('#toparameter').val() == null || $('#toparameter').val() == '')) {
			$.ajax({
				url: relUrl + 'parameter/' + $('#fromparameter').val() + '/' + $('#toparameter').val(),
				success: function(relation){
					var s = '';
					if(relation.length == 0)
						s = '-';
					$.each(relation, function(){
                                                s += "<div id='r" + this.id + "'>";
						s += "A relationship between these two Parameters already exists - they have a " + this.relationship + " by " + this.connection + " relationship with each other. ";
						if(this.description != null && this.description.length > 0) s+= "Explanation: " + escapeHTML(this.description);
						//s += " <a href=\"<?php echo base_url() . $controller; ?>/itemRelationshipDelete/<?php echo $itemType; ?>/" + this.id + "\" class=\"admindelete\">Delete?</a><br>";
                                                s += " <a href=\"#\" onclick=\"deleteRelationship(" + this.id + ", '<?php echo $itemType; ?>')\" class=\"admindelete\">Delete?</a></div>";
					});
					if(relation.length > 0) $('input[type="submit"]').attr('disabled','disabled');
					else $('input[type="submit"]').removeAttr('disabled');
					$('#existingrelations').html(s);
				}
			});
		} else if ( ! ($('#fromparameter').val() == null || $('#fromparameter').val() == '')) {
			$.ajax({
				url: relUrl + 'parameter/' + $('#fromparameter').val(),
				success: function(relation){
					var s = '';
					if(relation.length == 0)
						s = '-';
					$.each(relation, function(){
                                                s += "<div id='r" + this.id + "'>";
						s += "Parameter [" + this.parameter_key + "] " + escapeHTML(this.parameter_name) + " has a " + this.relationship + " by " + this.connection
						   + " relationship with Parameter [" + this.parent_key + "] " + escapeHTML(this.parent_name) + ". ";
						if (this.description != null && this.description.length > 0) s += "Explanation: " + escapeHTML(this.description);
						//s += " <a href=\"<?php echo base_url() . $controller; ?>/itemRelationshipDelete/<?php echo $itemType; ?>/" + this.id + "\" class=\"admindelete\">Delete?</a><br>";
                                                s += " <a href=\"#\" onclick=\"deleteRelationship(" + this.id + ", '<?php echo $itemType; ?>')\" class=\"admindelete\">Delete?</a></div>";
					});
					$('input[type="submit"]').removeAttr('disabled');
					$('#existingrelations').html(s);
				}
			});
		}
	});
}else if(<?php echo "'$itemType'"; ?> == 'procedure'){
	$('#fromprocedure, #toprocedure').change(function(e){
		if ( ! ($('#fromprocedure').val() == null || $('#fromprocedure').val() == '') && ! ($('#toprocedure').val() == null || $('#toprocedure').val() == '')) {
			$.ajax({
				url: relUrl + 'procedure/' + $('#fromprocedure').val() + '/' + $('#toprocedure').val(),
				success: function(relation){
					var s = '';
					if(relation.length == 0)
						s = '-';
					$.each(relation, function(){
                                                s += "<div id='r" + this.id + "'>";
						s += "A relationship between these two Procedures already exists - they have a " + this.relationship + " by " + this.connection + " relationship with each other. ";
						if(this.description != null && this.description.length > 0) s+= "Explanation: " + escapeHTML(this.description);
						//s += " <a href=\"<?php echo base_url() . $controller; ?>/itemRelationshipDelete/<?php echo $itemType; ?>/" + this.id + "\" class=\"admindelete\">Delete?</a><br>";
                                                s += " <a href=\"#\" onclick=\"deleteRelationship(" + this.id + ", '<?php echo $itemType; ?>')\" class=\"admindelete\">Delete?</a></div>";
					});
					if(relation.length > 0) $('input[type="submit"]').attr('disabled','disabled');
					else $('input[type="submit"]').removeAttr('disabled');
					$('#existingrelations').html(s);
				}
			});
		} else if ( ! ($('#fromprocedure').val() == null || $('#fromprocedure').val() == '')) {
			$.ajax({
				url: relUrl + 'procedure/' + $('#fromprocedure').val(),
				success: function(relation){
					var s = '';
					if(relation.length == 0)
						s = '-';
					$.each(relation, function(){
                                                s += "<div id='r" + this.id + "'>";
						s += "Procedure [" + this.procedure_key + "] " + escapeHTML(this.procedure_name) + " has a " + this.relationship + " by " + this.connection
						   + " relationship with Procedure [" + this.parent_key + "] " + escapeHTML(this.parent_name) + ". ";
						if(this.description != null && this.description.length > 0) s+= "Explanation: " + escapeHTML(this.description);
						s += " <a href=\"#\" onclick=\"deleteRelationship(" + this.id + ", '<?php echo $itemType; ?>')\" class=\"admindelete\">Delete?</a></div>";
					});
					if(relation.length > 0) $('input[type="submit"]').attr('disabled','disabled');
					else $('input[type="submit"]').removeAttr('disabled');
					$('#existingrelations').html(s);
				}
			});
		} else {
			return;
		}
	});
}
</script>
