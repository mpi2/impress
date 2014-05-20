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
 * @param array $releases array of rows from the change_log table
 * @param string $controller
 */
?>

<fieldset><legend>Insert</legend>
<p>Insert a new release.</p>

<?php 
$d = new DateTime();
echo form_open(null, array('id'=>'addeditform'));
?>

<label for="date">Date</label>
<input type="text" name="date" id="date" placeholder="You can leave this field blank if you want..." value="<?php echo set_value('date', $d->format('Y-m-d H:i:s')); ?>"><br>
<label for="message">Message</label>
<input type="text" name="message" id="message" placeholder="You can leave this field blank if you want..." value="<?php echo set_value('message'); ?>" style="width:90%"><br>
<div style="margin-left:2.5cm"><input type="submit" name="submit" id="submit" value="Insert"></div>

<?php echo form_close(); ?>

</fieldset>
