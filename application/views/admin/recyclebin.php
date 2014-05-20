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
 * @param string $model
 * @param array $items
 * @param array $fields
 * @param string $title
 * @param string $flash
 * @param string $controller
 */
?>

<h2><?php echo dexss($title); ?></h2>

<fieldset><legend>Deleted Items</legend>

<?php echo dexss($message); ?>

<?php if (empty($items)) { ?>
    <p>There are no items of this type in the recycle bin currently.</p>
<?php
} else {
?>

<?php
echo form_open(null, array('id' => 'recyclebinform'));
?>

<div id="restorelocations">
<?php
if ($model != 'pipeline' && $model != 'paramontologyoption')
	echo '<p>If you wish to restore the selected item(s) then please select the location '
	   . 'where you want them to be restored to and click the Restore button.</p>';
if ($model == 'pipeline' || $model == 'paramontologyoption') {
	//pipeline has no parent so restores to root
} else if ($model == 'procedure') {
	$this->load->view('admin/restorelocationselectpipeline');
} else if (in_array($model, array('parameter', 'sop', 'section'))) {
	$this->load->view('admin/restorelocationselectpipeline');
	$this->load->view('admin/restorelocationselectprocedure');
} else {
	$this->load->view('admin/restorelocationselectpipeline');
	$this->load->view('admin/restorelocationselectprocedure');
	$this->load->view('admin/restorelocationselectparameter');
}
?>
</div>

<div class="recyclebinbuttons">
<input type="submit" name="restoresubmit" id="restoresubmit" value="Restore">
<input type="submit" name="purgesubmit" id="purgesubmit" value="Purge">
</div>
<br>

<input type="hidden" name="buttonclicked" id="buttonclicked" value="">
<table class="listitemstable">
<thead>
<tr>
    <th><input type="checkbox" name="checkallbox" id="checkallbox"></th>
    <th>ID</th>
    <?php if ( ! empty($fields[IRecyclable::R_NAME])) { ?>
        <th><?php echo titlize($fields[IRecyclable::R_NAME]); ?></th>
    <?php } ?>
    <?php if ( ! empty($fields[IRecyclable::R_FIELDS])) {
        foreach ($fields[IRecyclable::R_FIELDS] as $xf) { ?>
            <th><?php echo titlize($xf); ?></th>
    <?php }
    } ?>
    <th>Deleted At</th>
    <th>Deleted By</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $item) { ?>
<tr>
    <td><input type="checkbox" class="item_id" name="item_id[]" value="<?php echo $item[IRecyclable::R_ID]; ?>"></td>
    <td><?php echo $item[IRecyclable::R_ID]; ?></td>
    <?php if ( ! empty($fields[IRecyclable::R_NAME])) { ?>
        <td><?php echo e($item[$fields[IRecyclable::R_NAME]]); ?></td>
    <?php } ?>
    <?php if ( ! empty($fields[IRecyclable::R_FIELDS])) {
        foreach ($fields[IRecyclable::R_FIELDS] AS $xf) { ?>
            <td><?php echo e($item[$xf]); ?></td>
    <?php }
    } ?>
    <td><?php echo $item[IRecyclable::R_DATE]; ?></td>
    <td><?php echo e($item['username']); ?></td>
</tr>
<?php } ?>
</tbody>
</table>

<?php echo form_close(); ?>

<script type="text/javascript">
$('#checkallbox').click(function(e){
	if($(this).is(':checked'))
		$('.item_id').attr('checked','checked');
	else
		$('.item_id').removeAttr('checked');
	$('.item_id').trigger('change');
});
$('#purgesubmit').click(function(e){
	e.preventDefault();
	var c = confirm('Are you sure you want to purge these items? This action is permanent.');
	if(c){
		$('#buttonclicked').val('purge');
		$('#recyclebinform').submit();
	}
	return c;
});
$('#restoresubmit').click(function(e){
	e.preventDefault();
	$('#buttonclicked').val('restore');
	$('#recyclebinform').submit();
	return true;
});
$('.item_id').change(function(e){
	var show = false;
	$('.item_id').each(function(i){
		if($(this).is(':checked'))
			show = true;
	});
	if(show)
		$('#restorelocations').show('slow');
	else
		$('#restorelocations').hide('slow');
});
</script>

<?php
}
?>
</fieldset>
