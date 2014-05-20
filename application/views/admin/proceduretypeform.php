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

<?php echo form_open(null, array('id'=>'addeditform')); ?>
<input type="hidden" name="allvalues" value="<?php echo (isset($allvalues)) ? $allvalues : ''; ?>">
<input type="hidden" name="id" value="<?php echo set_value('id', (isset($id)) ? $id : ''); ?>">
<input type="hidden" name="key" value="<?php echo set_value('key', (isset($key)) ? $key : ''); ?>">

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>	
	<td><label for="id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('id', (isset($id)) ? $id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td><label for="type" class="required">Type Name</label></td>
	<td><input type="text" title="<?php tooltip('proctype_name'); ?>" name="type" value="<?php echo set_value('type', (isset($type)) ? $type : ''); ?>"></td>
</tr>
<tr>
	<td><label for="key" class="required">Key</label></td>
	<td><input type="text" title="<?php tooltip('proctype_key'); ?>" placeholder="e.g. XRY (3 Uppercase letters)" 
	oninvalid="setCustomValidity('Please enter only 3 Uppercase Alphabetic letters')"
	name="key" value="<?php echo set_value('key', (isset($key)) ? $key : ''); ?>"<?php if($mode=='U') echo ' disabled="disabled"'; ?>></td>
</tr>
<tr>
	<?php
	if($mode == 'I'){
		$ci =& get_instance();
		$ci->load->model('proceduretypemodel');
		$num = $ci->proceduretypemodel->makeTriple( 1 + $ci->proceduretypemodel->getLastNum() );
	}
	?>
	<td><label for="num">Number</label></td>
	<td><input type="hidden" name="num" value="<?php echo set_value('num', (isset($num)) ? $num : ''); ?>">
	<input type="text" name="num" disabled="disabled" value="<?php echo set_value('num', (isset($num)) ? $num : ''); ?>"></td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" name="submit" value="Submit" id="submit"></td>
</tr>
</table>

<?php echo form_close();
