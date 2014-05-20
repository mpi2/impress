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

<div class="restorelocationselectbox">
<label for="parameter_id" class="required">Parameter</label>
<select name="parameter_id" id="parameter_id" size="7">
</select>
</div>

<script type="text/javascript">
$('#procedure_id').change(function(e){
    $.ajax({
        url: '<?php echo base_url() . 'ajax/getParameters/'; ?>' + $(this).val(),
        success: function(parameters){
            $('#parameter_id').empty().append($('<option></option>'));
            $.each(parameters, function(){
                $('#parameter_id').append(
                      $('<option></option>')
                      .attr('value', this.parameter_id)
                      .text(this.parameter_key + ' ' + this.name)
                );
            });
        }
    });
});
$('#pipeline_id').change(function(e){
	$('#parameter_id').empty().append($('<option></option>'));
});
</script>
