<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * @param string $m The model of the item
 * @param int $id Item id
 * @param int $pipelineId
 * @param int $procedureId
 * @param int $parameterId
 * @param string $controller
 */

if ($m == 'sop' || $m == 'section'):
?>

<!-- display nothing for sops/sections -->

<?php
elseif ($m == 'procedure'):
?>

<p>Would you like to
<?php echo anchor($controller . '/cloneprocedure/item_id/' . $id . '/pipeline_id/' . $pipelineId, 'clone this Procedure'); ?> 
into this Pipeline instead?</p>

<?php
elseif (in_array($m, array('parameter', 'paramoption', 'paramincrement', 'parammpterm', 'parameqterm', 'paramontologyoption'))):
?>

<p>Would you like to 
<?php echo anchor($controller . '/cloneparameter/item_id/' . $parameterId . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId, 'clone this Parameter'); ?> 
into this Procedure instead? Or...<br>
Would you like to 
<?php
echo ($m == 'paramoption') ?
anchor($controller . '/replaceParameterWithNewVersion/parameter_id/' . $parameterId . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId . '/option_id/' . $id, 'create a new version') :
anchor($controller . '/replaceParameterWithNewVersion/parameter_id/' . $id . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId, 'create a new version');
?> 
of this Parameter to replace the old one?</p>

<?php
else:
?>

<p>Would you like to
<?php echo anchor($controller . '/cloneparameter/item_id/' . $id . '/pipeline_id/' . $pipelineId . '/procedure_id/' . $procedureId, 'clone the Parameter'); ?> 
this item belongs to instead?</p>

<?php
endif;
