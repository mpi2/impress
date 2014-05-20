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
 * @param array  $from  A hash of the fields and values to compare changes from
 * @param array  $to    A hash of the fields and values to compare changes to
 * @param int    $id    The id of the record being compared
 * @param string $model The model (type) of item being compared,
 * @param array  $formatFields   A list of fields that need to be formatted - either the _title_ needs to be set differently, or it _is_bool_ and needs a tick to display
 * @param array  $hideFields     A list of fields to not display
 * @param bool   $hideUnchanged  if set to true then unchanged fields are hidden
 * @param int 	$pipeline_id
 * @param int 	$procedure_id   (optional)
 * @param int 	$parameter_id   (optional)
 * @param string $controller
 */

//set defaults for flags
$hideUnchanged = (isset($hideUnchanged)) ? (bool)$hideUnchanged : false;
$formatFields  = (isset($formatFields))  ? (array)$formatFields : array();
$hideFields    = (isset($hideFields))    ? (array)$hideFields   : array();
$pipeline_id   = (isset($pipeline_id))   ? $pipeline_id         : null;
$procedure_id  = (isset($procedure_id))  ? $procedure_id        : null;
$parameter_id  = (isset($parameter_id))  ? $parameter_id        : null;
?>

<h3>Comparing Changes In Revisions</h3>
			
<table class="listitemstable">
<thead>
<tr>
<th>Field</th>
<th>From Revision <?php echo $from['minor_version']; ?></th>
<th>To Revision <?php echo $to['minor_version']; ?></th>
</tr>
</thead>
<tbody>
<?php
foreach($from as $key => $value){
    if($key == 'id') continue;
    if(in_array($key, $hideFields)) continue;
    if($hideUnchanged && $value == $to[$key]) continue;
    $style = ($value != $to[$key]) ? ' class="changed"' : '';
    echo '<tr><th valign="top">';
    $displayKey = e(ucwords(str_replace('_', ' ', $key)));
    $is_bool = false;
    if(preg_match('/^is_/', $key)) $is_bool = true;
    if(array_key_exists($key, $formatFields)){
        if(array_key_exists('is_bool', $formatFields[$key]))
            $is_bool = (bool) $formatFields[$key]['is_bool'];
        if(array_key_exists('title', $formatFields[$key]))
            $displayKey = e($formatFields[$key]['title']);
    }
    echo $displayKey;
    echo '</th><td valign="top"> ';
    echo ($is_bool) ? (((bool)$value) ? 'Yes' : 'No') : $value;
    echo '</td><td valign="top"' . $style . '>';
    echo ($is_bool) ? (((bool)$to[$key]) ? 'Yes' : 'No') : $to[$key];
    echo '</td></tr>' . PHP_EOL;
}
?>
</tbody>
</table>

<h3>Revert revision to current</h3>

<?php
if ( ! empty($from['id'])) {
    echo anchor(
        $controller . '/revertrevision/' . $model . '/' . $id . '/' . $from['id'] . '/pipeline_id/' . $pipeline_id . '/procedure_id/' . $procedure_id . '/parameter_id/' . $parameter_id, 'Set revision ' . $from['minor_version'] . ' to become the current one'
    );
    echo '<br>';
}
if ( ! empty($to['id'])) {
    echo anchor(
        $controller . '/revertrevision/' . $model . '/' . $id . '/' . $to['id'] . '/pipeline_id/' . $pipeline_id . '/procedure_id/' . $procedure_id . '/parameter_id/' . $parameter_id, 'Set revision ' . $to['minor_version'] . ' to become the current one'
    );
}
