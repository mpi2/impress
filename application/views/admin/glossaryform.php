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

<script type="text/javascript" src="<?php echo base_url(); ?>js/ckeditor/ckeditor.js"></script>

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="glossary_id" value="<?php echo set_value('glossary_id', (isset($glossary_id)) ? $glossary_id : ''); ?>">
<input type="hidden" name="term" value="<?php echo set_value('term', (isset($term)) ? $term : ''); ?>">
<input type="hidden" name="user_id" value="<?php echo set_value('user_id', (isset($user_id)) ? $user_id : ''); ?>">

<table id="edit" class="ckd">
<tr>
	<td class="f1"><label for="term" class="required">Term</label></td>
	<td class="f2"><input type="text" name="term" value="<?php echo set_value('term', (isset($term)) ? $term : ''); ?>"<?php if($mode == 'U') echo ' disabled="disabled"'; ?>></td>
</tr>
<tr>
	<td class="f1"><label for="definition" class="required">Definition</label></td>
	<td class="f2"><textarea name="definition" class="ckeditor"><?php echo set_value('definition', (isset($definition)) ? $definition : ''); ?></textarea></td>
</tr>

<?php if($mode == 'U'){ ?>
<tr>
	<td class="f1"><label for="time_modified">Time Modified</label></td>
	<td class="f2"><input type="text" name="time_modified" disabled="disabled" value="<?php echo set_value('time_modified', (isset($time_modified)) ? $time_modified : ''); ?>"></td>
</tr>
<tr>
	<td><label for="user_id">User</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo e(@$username); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td class="f1"></td>
	<td class="f2"><input type="submit" id="submit" name="submit" value="Submit"></td>
</tr>
</table>

<?php echo form_close(); ?>

<?php if($mode == 'U'){ 
$CI =& get_instance();
$this->load->model('glossarymodel');
$oldedits = $this->glossarymodel->getOldEditsByTermId($glossary_id);
?>

<h3>Previous Versions</h3>

<?php if(count($oldedits) > 0): ?>

<table class="listitemstable">
<thead>
<tr>
<th>Date / Time Modified</th>
<th>By User</th>
<th>Definition</th>
</tr>
</thead>
<tbody>
<?php foreach($oldedits AS $oldedit){ ?>
	<tr>
	<td><?php echo $oldedit['time_modified']; ?></td>
	<td><?php 
	if($oldedit['user_id'] == $user_id){ 
		echo e($username);
	}else{
		$person = new Person($oldedit['user_id']);
		echo e($person->getName());
	}
	?></td>
	<td><?php echo dexss($oldedit['definition']); ?></td>
	</tr>
<?php } ?>
</tbody>
</table>
<?php else: ?>

<p>There are no previous versions for this term.</p>

<?php
endif;

}
