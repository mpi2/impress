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

<script type="text/javascript">
var nvclick = true;
</script>

<?php
$ci =& get_instance();
echo form_open(null, array('id'=>'addeditform','name'=>'addeditform'));
?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="pipeline_id" value="<?php echo set_value('pipeline_id', (isset($pipeline_id)) ? $pipeline_id : ''); ?>">
<input type="hidden" name="procedure_id" value="<?php echo set_value('procedure_id', (isset($procedure_id)) ? $procedure_id : ''); ?>">
<input type="hidden" name="parameter_id" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>">
<input type="hidden" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>">
<input type="hidden" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>">
<input type="hidden" name="parameter_key" value="<?php echo set_value('parameter_key', (isset($parameter_key)) ? $parameter_key : ''); ?>">
<input type="hidden" name="is_increment" value="<?php echo set_value('is_increment', (@$is_increment) ? 1 : 0); ?>">
<input type="hidden" name="is_option" value="<?php echo set_value('is_option', (@$is_option) ? 1 : 0); ?>">
<input type="hidden" name="time_modified" value="<?php echo set_value('time_modified', (isset($time_modified)) ? $time_modified : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<table id="edit">
<tr>
    <td><label for="pipeline">Pipeline</label></td>
    <?php $pipeline = new Pipeline($pipeline_id); ?>
    <td><input type="text" disabled="disabled" name="pipeline" value="<?php echo form_prep($pipeline->getItemName()); ?>"></td>
</tr>
<tr>
    <td><label for="procedure">Procedure</label></td>
    <?php $procedure = new Procedure($procedure_id); ?>
    <td><input type="text" disabled="disabled" name="procedure" value="<?php echo form_prep($procedure->getItemName()); ?>"></td>
</tr>

<?php if ($mode == 'U') { ?>
<tr>
    <td><label for="parameter_id">Parameter Id</label></td>
    <td><input type="text" disabled="disabled" value="<?php echo set_value('parameter_id', (isset($parameter_id)) ? $parameter_id : ''); ?>"></td>
</tr>
<tr>
    <td><label for="major_version">Major Version</label></td>
    <td><input type="text" disabled="disabled" name="major_version" value="<?php echo set_value('major_version', (isset($major_version)) ? $major_version : '1'); ?>"></td>
</tr>
<tr>
    <td><label for="minor_version">Minor Version</label></td>
    <td><input type="text" disabled="disabled" name="minor_version" value="<?php echo set_value('minor_version', (isset($minor_version)) ? $minor_version : '0'); ?>"></td>
</tr>
<?php
// if(empty($parameter_key))
    // $parameter_key = $ci->parametermodel->getNewParameterKeyForProcedure($procedure_id);
?>
<tr>
    <td><label for="parameter_key">Parameter Key</label></td>
    <td><input type="text" name="parameter_key" disabled="disabled" value="<?php echo set_value('parameter_key', (isset($parameter_key)) ? $parameter_key : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
    <td><label for="type" class="required">Parameter Type</label></td>
    <td>
    <select name='type' title="<?php tooltip('parameter_type') ?>"><option value=""></option>
    <?php
        $chosenValue = set_value('type', (isset($type)) ? $type : '');
        foreach (EParamType::__toArray() as $t) {
            $select = ($chosenValue == $t) ? ' selected="selected"' : '';
            echo "<option value='$t'$select> $t</option>\n";
        }
    ?>
    </select>
    </td>
</tr>
<tr>
    <td><label for="name" class="required">Parameter Name</label></td>
    <td><input type="text" title="<?php tooltip('parameter_name') ?>" name="name" value="<?php echo set_value('name', (isset($name)) ? $name : ''); ?>"></td>
</tr>
<tr>
    <td><label for="visible">Visible</label></td>
    <td><input type="checkbox" title="<?php tooltip('visible') ?>" name="visible" <?php echo set_value('visible', (@$visible) ? 'checked' : ''); if($mode=='I') echo 'checked'; ?>></td>
</tr>
<tr>
    <td><label for="active">Active</label></td>
    <td><input type="checkbox" title="<?php tooltip('3P_active') ?>" name="active" <?php echo set_value('active', (@$active) ? 'checked' : ''); if($mode=='I') echo 'checked'; ?>></td>
</tr>
<tr>
    <td><label for="deprecated">Deprecated</label></td>
    <td><input type="checkbox" title="<?php tooltip('deprecated') ?>" name="deprecated"<?php echo set_value('deprecated', (@$deprecated) ? ' checked' : ''); ?>></td>
</tr>
<tr>
    <td><label for="is_required">Required For Upload</label></td>
    <td><input type="checkbox" name="is_required" <?php echo set_value('is_required', (@$is_required) ? 'checked' : ''); ?><?php
$ci->load->model('notinbetamodel');
if ($mode == 'I' && $this->config->item('version_triggering') && $ci->notinbetamodel->keyIsInBeta($procedure->getItemKey())) {
    echo 'disabled="disabled"> <span style="width:92%" class="small align-right">', tooltip('requirednewprocedure'), '</span>';
} else {
    echo ' title="', tooltip('required'), '">';
}
?></td>
</tr>
<tr>
    <td><label for="is_important">Required for Data Analysis</label></td>
    <td><input type="checkbox" title="<?php tooltip('important') ?>" name="is_important" <?php echo set_value('is_important', (@$is_important) ? 'checked' : ''); ?>></td>
</tr>

<?php if (User::isAdmin()) { ?>
<tr>
    <td><label for="internal">Internal</label></td>
    <td><input type="checkbox" name="internal" title="<?php tooltip('internal') ?>"<?php echo set_value('internal', (@$internal) ? ' checked' : ''); ?>></td>
</tr>
<?php } ?>

<tr>
    <td><label for="is_annotation">Annotation</label></td>
    <td><input type="checkbox" title="<?php tooltip('annotation') ?>" name="is_annotation" <?php echo set_value('is_annotation', (@$is_annotation) ? 'checked' : ''); ?>></td>
</tr>
<tr>
    <td><label for="is_derived">Derived</label></td>
    <td><input type="checkbox" title="<?php tooltip('derived') ?>" name="is_derived" <?php echo set_value('is_derived', (@$is_derived) ? 'checked' : ''); ?>></td>
</tr>
<tr>
    <td><label for="derivation">Derivation</label></td>
    <td><input type="text" title="<?php tooltip('derivation') ?>" name="derivation" value="<?php echo set_value('derivation', (isset($derivation)) ? $derivation : ''); ?>"></td>
</tr>
<tr>
    <td><label for="unit">Unit</label></td>
    <td><select name="unit" title="<?php tooltip('unit') ?>">
    <?php
    $ci->load->model('unitmodel');
    foreach ($ci->unitmodel->fetchAll() as $u) {
        $select = (isset($unit) && $u[UnitModel::PRIMARY_KEY] == $unit) ? ' selected="selected"' : '';
        echo "<option value='" . $u[UnitModel::PRIMARY_KEY] . "'$select>" . e($u['unit']) . "</option>\n";
    }
    ?>
    </select></td>
</tr>
<tr>
    <td><label for="qc_check">QC Check</label></td>
    <td><input type="checkbox" title="<?php tooltip('qc_check') ?>" name="qc_check" <?php echo set_value('qc_check', (@$qc_check) ? 'checked' : ''); ?>></td>
</tr>
<tr>
    <td><label for="qc_min">QC Min</label></td>
    <td><input type="text" title="<?php tooltip('qc_min') ?>" name="qc_min" value="<?php echo set_value('qc_min', (isset($qc_min)) ? $qc_min : ''); ?>"></td>
</tr>
<tr>
    <td><label for="qc_max">QC Max</label></td>
    <td><input type="text" title="<?php tooltip('qc_max') ?>" name="qc_max" value="<?php echo set_value('qc_max', (isset($qc_max)) ? $qc_max : ''); ?>"></td>
</tr>
<tr>
    <td><label for="qc_notes">QC Notes</label></td>
    <td><textarea title="<?php tooltip('qc_notes') ?>" name="qc_notes"><?php echo set_value('qc_notes', (isset($qc_notes)) ? $qc_notes : ''); ?></textarea></td>
</tr>
<tr>
    <td><label for="value_type" class="required">Value Type</label></td>
    <td>
    <select name="value_type" title="<?php tooltip('value_type') ?>">
    <?php
    $chosenValue = set_value('value_type', (isset($value_type)) ? $value_type : '');
    foreach (EParamValueType::__toArray() as $vt) {
        $select = ($chosenValue == $vt) ? ' selected="selected"' : '';
        echo "<option value='$vt'$select> $vt</option>\n";
    }
    ?>
    </select>
    </td>
</tr>
<tr>
    <td><label for="graph_type">Graph Type</label></td>
    <td>
    <select name="graph_type" title="<?php tooltip('graph_type') ?>">
    <?php
    $chosenValue = set_value('graph_type', (isset($graph_type)) ? $graph_type : '');
    foreach (EParamGraphType::__toArray() as $gt) {
        $select = ($chosenValue == $gt) ? ' selected="selected"' : '';
        echo "<option value='$gt'$select> $gt</option>\n";
    }
    ?>
    </select>
    </td>
</tr>
<tr>
    <td><label for="data_analysis_notes">Data Analysis Notes</label></td>
    <td><textarea title="<?php tooltip('data_analysis_notes') ?>" name="data_analysis_notes"><?php echo set_value('data_analysis_notes', (isset($data_analysis_notes)) ? $data_analysis_notes : ''); ?></textarea></td>
</tr>
<tr>
    <td><label for="description">Parameter Description</label></td>
    <td><textarea title="<?php tooltip('description') ?>" name="description"><?php echo set_value('description', (isset($description)) ? $description : ''); ?></textarea></td>
</tr>

<?php if ($mode == 'U') { ?>
<tr>
    <td><label for="time_modified">Time Modified</label></td>
    <td><input type="text" name="time_modified" disabled="disabled" value="<?php echo set_value('time_modified', (isset($time_modified)) ? $time_modified : ''); ?>"></td>
</tr>
<tr>
    <td><label for="user_id">User</label></td>
    <td><input type="text" disabled="disabled" value="<?php echo form_prep(@$username); ?>"></td>
</tr>
<?php } ?>

<tr>
    <td></td>
    <td>
    <span id="hidefornewversion">
        <input type="submit" name="submit" value="Submit" id="submit"> <?php if($mode == 'U') echo 'or'; ?>
    </span>
    <div id="newmajorversionfields" style="display:inline;display:none">
    <?php
    $pipeline = new Pipeline($pipeline_id);
    $this->load->view('admin/newmajorversionfields',
        array(
            'controller' => $controller,
            'selectedPipeline' => $pipeline_id,
            'selectedProcedure' => $procedure_id,
            'pipelines' => PipelinesFetcher::fetchAll(),
            'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()) //$pipeline->getProcedures()
        )
    );
    ?>
    </div>
    <?php if ($mode == 'U') { ?>
    <input type="hidden" name="nvsubmitbuttonclicked" id="nvsubmitbuttonclicked" value="">
    <input type="submit" name="new_major_version_submit" id="nvsubmit" value="Create a new Major Version">
    <input type="submit" name="cancel" style="display:none" id="nvcancel" value="Cancel">
    <script type="text/javascript">
    $('#nvsubmit').click(function(e){
        if(nvclick){
            //display the versioning fields
            e.preventDefault();
            $('#hidefornewversion').hide();
            $('#newmajorversionfields').show();
            $('#nvcancel').show();
        }else{
            //submit the new version form but first set the button as clicked (to fix jquery.submit() bug)
            $('#nvsubmitbuttonclicked').val('1');
        }
        nvclick = false;
    });
    $('#nvcancel').click(function(e){
        e.preventDefault();
        $('#newmajorversionfields').hide();
        $('#hidefornewversion').show();
        $(this).hide();
        nvclick = true;
    });
    </script>
    <?php } ?>
    </td>

</tr>
</table>
<?php echo form_close();
