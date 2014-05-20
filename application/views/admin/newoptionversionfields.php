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

<p>Please define how your new option are related to the old one.</p>

<label for='nvoption_relation' class='required'>Relationship</label>
<select title="<?php tooltip('nvoption_relation') ?>" name='nvoption_relation' id='nvoption_relation'>
<?php
foreach(ERelationType::__toArray() as $relation)
    echo '<option value="' . $relation . '">' . $relation . '</option>' . PHP_EOL;
?>
</select>
<br>
<label for="nvoption_relationdescription">Explanation</label>
<textarea title="<?php tooltip('nvoption_relationdescription') ?>" name='nvoption_relationdescription' id='nvoption_relationdescription'><?php echo set_value('nvoption_relationdescription'); ?></textarea>
