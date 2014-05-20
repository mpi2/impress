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
<input type="hidden" name="id" value="<?php echo (isset($id)) ? $id : ''; ?>">
<input type="hidden" name="weight" value="<?php echo (isset($weight)) ? $weight : ''; ?>">

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>	
	<td><label for="id">Id</label></td>
	<td><input type="text" disabled="disabled" value="<?php echo set_value('id', (isset($id)) ? $id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
	<td><label for="label" class="required">Week Label</label></td>
	<td><input type="text" title="<?php tooltip('week_label') ?>" name="label" value="<?php echo set_value('label', (isset($label)) ? $label : ''); ?>"></td>
</tr>
<tr>
	<td><label for="num" class="required">Week Number</label></td>
	<td><input type="text" title="<?php tooltip('week_number') ?>" name="num" value="<?php echo set_value('num', (isset($num)) ? $num : ''); ?>"<?php if($mode == 'U') echo ' disabled="disabled"'; ?>></td>
</tr>
<tr>
        <td><label for="stage" class="required">Stage</label></td>
        <td><select name="stage"><option value="">&nbsp;</option>
        <?php
        $chosen = set_value('stage', (isset($stage)) ? $stage : '');
        foreach (EProcedureWeekStage::getLabels() as $stage => $label) {
            $selected = ($chosen == $stage) ? ' selected="selected"' : '';
            echo "<option value='$stage'$selected>$label</option>\n";
        }
        ?>
        </select></td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" name="submit" value="Submit" id="submit"></td>
</tr>
</table>

<?php echo form_close();
