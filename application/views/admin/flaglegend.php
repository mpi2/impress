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

?>

<div id="flaglegend">
<span id="legendtitle">Legend: </span>

<?php
echo "\n Normal " . pick_a_flag('regularitem');
echo "\n Mandatory/Required " . pick_a_flag('requireditem');
echo "\n Deprecated " . pick_a_flag('deprecateditem');
echo "\n Deleted " . pick_a_flag('deleteditem');
echo "\n Inactive " . pick_a_flag('inactiveitem');
echo "\n Hidden " . pick_a_flag('hiddenitem');
echo "\n Internal " . pick_a_flag('internalitem');
?>

</div>
