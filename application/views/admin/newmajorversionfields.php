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
 * @param string $controller
 * @param int $selectedPipeline The id of the current pipeline item being edited
 * @param int $selectedProcedure The id of the current procedure item being edited
 * @param int $excludePipeline The pipeline ids to exclude from the list
 * @param array $pipelines All the Pipelines in IMPReSS as an array of Pipeline
 * @param array $procedures All the Procedures for the selected Pipeline - this is only needed in parameter versioning
 * @param bool $setChecked If this is set to TRUE then the nvforkprocedure button is set to checked and made read-only.
 * If FALSE it is set unchecked and read-only. Otherwise, it is checked by default but can be unchecked by the user
 */

$excludePipeline = (isset($excludePipeline)) ? $excludePipeline : null;
?>

<p>Please define how your new version is related to the old one.</p>

<label for='nvrelation' class='required'>Relationship</label>
<select title="<?php tooltip('nv_relation') ?>" name='nvrelation' id='nvrelation'>
<?php
foreach(ERelationType::__toArray() as $relation) {
    echo '<option value="' . $relation . '">' . $relation . '</option>' . PHP_EOL;
}
?>
</select>
<br>
<label for="nvrelationdescription">Explanation</label>
<textarea title="<?php tooltip('nv_relationdescription') ?>" name='nvrelationdescription' id='nvrelationdescription'><?php echo set_value('nvrelationdescription'); ?></textarea>

<?php
if (isset($pipelines) && ! empty($pipelines)) { ?>

    <p>And where would you like to place the new version?</p>

    <label for='nvpipline' class='required'>Pipeline</label>
    <select title="<?php tooltip('nv_pipeline') ?>" name="nvpipeline" id="nvpipeline">
    <?php
    foreach ($pipelines as $pip) {
        $selected = ($pip->getId() == $selectedPipeline) ? ' selected="selected"' : '';
        echo '<option value="' . $pip->getId() . '"' . $selected . '>' . $pip->getItemKey() . ' - ' . e($pip->getItemName()) . '</option>' . PHP_EOL;
    }
    ?>
    </select><br>
    Use Prefix from old Pipeline? <input title="<?php tooltip('nvuseoldpipelinekey') ?>" type="checkbox" name="nvuseoldpipelinekey" checked="checked">

<?php
}


if (isset($procedures) && ! empty($procedures)) {
?>

    <br>
    <label for="nvprocedure" class="required">Procedure</label>
    <select title="<?php tooltip('nv_procedure') ?>" name="nvprocedure" id="nvprocedure">
    <?php
    foreach ($procedures as $proc) {
        $selected = ($proc->getId() == $selectedProcedure) ? ' selected="selected"' : '';
        echo '<option value="' . $proc->getId() . '"' . $selected . '>' . $proc->getItemKey() . ' ' . e($proc->getItemName()) . '</option>' . PHP_EOL;
    }
    ?>
    </select>
    <br>
    <label for='nvforkprocedure'>Make a new Procedure version for this new Parameter version</label>
    <input type="checkbox" name="nvforkprocedure" id="nvforkprocedure"
    <?php
    if (isset($setChecked) && $setChecked === true) {
        echo 'readonly="readonly" checked="checked"';
    } else if (isset($setChecked) && $setChecked === false) {
        echo 'readonly="readonly"';
    } else {
        echo 'checked="checked"';
    }
    ?>
    >
    <br>
    <!--<label for="nvdeleteolditem">Delete old Parameter</label>
    <input type="checkbox" name="nvdeleteolditem" id="nvdeleteolditem" checked="checked">-->
        


    <script type="text/javascript">
    $('#nvpipeline').change(function(e){
        $.ajax({
            url: '<?php echo base_url() . 'ajax/getProcedures/'; ?>' + $(this).val(),
            success: function(procedures){
                $('#nvprocedure').empty();
                $.each(procedures, function(){
                    $('#nvprocedure').append(
                        $("<option></option>")
                        .attr('value', this.procedure_id)
                        .text(this.procedure_key + ' ' + this.name)
                    );
                });
            }
        });
    });
    </script>

<?php
}

//The $selectedPipeline variable is not set when editing Pipelines so we can use it to hide the view for pipelines
if (isset($selectedPipeline)) {
?>
    <div id="softlinktopipelinessection">
    <br>
    <label for="softlinkintopipelines">Soft-link the new Procedure version into other Pipelines</label>
    <br>
    <select name="softlinkintopipelines[]" id="softlinkintopipelines" multiple="multiple" size="8">
        <option value="">&nbsp;</option>
    <?php
    foreach (PipelinesFetcher::fetchAll() as $pipeline) {
        if ($pipeline->isDeprecated() || $pipeline->getId() == $excludePipeline) {
            continue;
        }
        echo "<option value='{$pipeline->getId()}'>{$pipeline->getItemName()}</option>\n";
    }
    ?>
    </select>
    <br>
    <small>Hold down the control key on your keyboard to select more than one item</small>
    </div>
    
    <script type="text/javascript">
    $("#nvforkprocedure").change(function(e){
        if ($(this).is(":checked")) {
            $("#softlinktopipelinessection").show("fast");
        } else {
            $("#softlinktopipelinessection").hide("slow");
        }
    });
    $("#nvforkprocedure").change();
    </script>
<?php
}
?>
<br>
<br>
