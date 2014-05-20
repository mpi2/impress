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

<table id="edit">

<?php if($mode == 'U'){ ?>
<tr>
    <td><label for="id">Id</label></td>
    <td><input type="text" disabled="disabled" value="<?php echo set_value('id', (isset($id)) ? $id : ''); ?>"></td>
</tr>
<?php } ?>

<tr>
    <td><label for="unit" class="required">Unit</label></td>
    <td><input type="text" name="unit" value="<?php echo set_value('unit', (isset($unit)) ? $unit : ''); ?>"></td>
</tr>
<tr>
    <td></td>
    <td><input type="submit" name="submit" id="submit" value="Submit"></td>
</tr>
</table>

<?php echo form_close();
