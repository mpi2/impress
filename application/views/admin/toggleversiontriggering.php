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

$currentStatus  = ($this->config->item('version_triggering')) ? 'on' : 'off';
$currentStatus .= (User::isSuperAdmin()) ? 1 : 0;
?>

<div id="togglevt">
    <span>Version Triggering</span> <a href="#" class="vt<?php echo $currentStatus; ?>"></a>
</div>

<?php
if (User::isSuperAdmin()):
?>

<script type="text/javascript">
$('#togglevt a').click(function(e){
    e.preventDefault();
    $.ajax({
        url: "<?php echo site_url() . 'ajax/toggleVersionTriggering'; ?>",
        success: function(data){
            if (data.status == "on") {
                $('#togglevt a').removeClass('vtoff1').addClass('vton1');
            } else {
                $('#togglevt a').removeClass('vton1').addClass('vtoff1');
            }
        }
    });
});
</script>

<?php
endif;
