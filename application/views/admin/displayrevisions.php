<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * @param array $revisions Revisioning records from the database for the current item
 * @param string $m model
 * @param int $id Item id
 * @param int $procedureId
 * @param int $pipelineId
 * @param string $controller
 */
?>

<h3>Revision History</h3>

<?php
if (count($revisions) == 0):
?>

<p>There are no revisions for this item.</p>

<?php
else:
?>

<?php echo form_open(base_url() . $controller . "/comparerevisions/$m/$id/pipeline_id/" . $pipelineId . "/procedure_id/" . $procedureId); ?>

Compare changes

from version

<select name="from">
<?php 
foreach ($revisions as $rev) {
    echo '<option value="' . (isset($rev['id']) ? $rev['id'] : '') . '">' . (int)$rev['major_version'] . '.' . $rev['minor_version'] . '</option>' . PHP_EOL;
}
?>
</select>

to

<select name="to">
<?php
foreach($revisions as $rev) {
    echo '<option value="' . (isset($rev['id']) ? $rev['id'] : '') . '">' . (int)$rev['major_version'] . '.' . $rev['minor_version'] . '</option>' . PHP_EOL;
}
?>
</select>

<input type="submit" name="comparesubmit" value="Compare">

<?php echo form_close(); ?>
<p></p>

<?php
foreach ($revisions as $rev) {
    $current = ( ! array_key_exists('id', $rev)) ? 'Current ' : '';
    $user = new Person($rev['user_id']);
    echo $current . 'Revision ' . (int)$rev['major_version'] . '.' . $rev['minor_version'] . ' Modified on ' . $rev['time_modified'] . ' by ' . e($user->getName()) . ' <br>'. PHP_EOL;
}
?>

<script type='text/javascript'>
$('select[name="to"] option:first-child').attr('selected','selected');
$('select[name="from"] option:first-child').next().attr('selected','selected');
</script>

<?php
endif;
