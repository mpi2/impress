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
 * @param int $pipeline_id
 * @param int $procedure_id
 * @param int $parameter_id
 * @param int $item_id Id of the ontology option which we want to delete
 * @param string $errors
 * @param string $flash
 * @param bool $isLatestVersion
 */

$group = new OntologyGroup($item_id);
?>

<h2>Delete an Ontology Group</h2>

<?php
if (isset($isLatestVersion) && ! $isLatestVersion) {
?>    
<p><i>Warning: This ontology group does not belong to the latest version of the parameter. Are you sure you are deleting the correct group?</i></p>
<?php
}
?>

<p>You are about to delete the Ontology Group <?php
echo '"(' . $group->getId() . ') ' . e($group->getName()); ?>".</p>

<p>Deleting an Ontology Group leads to the creation of a new version of the Parameter 
it is associated with. Are you sure you want to remove the Ontology Group?</p>

<p>If you are sure you want to delete this ontology group and create a new version 
of the Parameter (and potentially the Procedure) then please complete the form 
below and click delete.</p>

<?php
if(isset($errors) && ! empty($errors)) echo $errors;
if(isset($flash) && ! empty($flash)) echo $flash;
?>

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="pipeline_id"  value="<?php echo $pipeline_id; ?>">
<input type="hidden" name="procedure_id" value="<?php echo $procedure_id; ?>">
<input type="hidden" name="parameter_id" value="<?php echo $parameter_id; ?>">
<input type="hidden" name="ontology_group_id" value="<?php echo $group->getId(); ?>">

<table id="edit">
<tr>
    <td></td>
    <td>
    <?php
    $pipeline = new Pipeline($pipeline_id);
    $this->load->view('admin/newmajorversionfields', array(
        'controller' => $controller,
        'selectedPipeline' => $pipeline_id,
        'selectedProcedure' => $procedure_id,
        'selectedParameter' => $parameter_id,
        'pipelines' => PipelinesFetcher::fetchAll(),
        'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()) //$pipeline->getProcedures()
    ));
    ?>
    </td>
</tr>
<tr>
    <td></td>
    <td><input type="submit" name="submit" value="Delete Option" id="submit"></td>
</tr>
</table>

<?php echo form_close();
