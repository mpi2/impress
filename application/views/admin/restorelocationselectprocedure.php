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
<label for="procedure_id" class="required">Procedure</label>
<select name="procedure_id" id="procedure_id" size="7">
<option></option>
</select>
</div>

<script type="text/javascript">
$('#pipeline_id').change(function(e){
    $.ajax({
        url: '<?php echo base_url() . 'ajax/getProcedures/'; ?>' + $(this).val(),
        success: function(procedures){
            $('#procedure_id').empty().append($('<option></option>'));
            $.each(procedures, function(){
                $('#procedure_id').append(
                      $('<option></option>')
                      .attr('value', this.procedure_id)
                      .text(this.procedure_key + ' ' + this.name)
                );
            });
        }
    });
});
</script>
