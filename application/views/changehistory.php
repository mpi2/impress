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
 * @param Pipeline[] $pipelines
 * @param int $selectedPipelineId
 * @param int $selectedProcedureId
 * @param array $users
 * @param string $result
 */


/*
if ($result):
    <!--<div id="changehistoryflatlegend">
        <div><span class="actionupdated"></span> Updated</div>
        <div><span class="actioncreated"></span> Created/Added</div>
        <div><span class="actionimport"></span> Imported</div>
        <div><span class="actioncloned"></span> Cloned</div>
        <div><span class="actiondeleted"></span> Deleted/Unlinked</div>
        <div><span class="actionversioned"></span> Versioned</div>
    </div>-->
        
    <!--<div id="changehistorylegend">
        <table>
            <thead>
                <tr>
                    <th colspan="2">Legend</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="actionupdated">&nbsp;</td>
                    <td>Updated</td>
                </tr>
                <tr>
                    <td class="actioncreated">&nbsp;</td>
                    <td>Created/Added</td>
                </tr>
                <tr>
                    <td class="actionimport">&nbsp;</td>
                    <td>Imported</td>
                </tr>
                <tr>
                    <td class="actioncloned">&nbsp;</td>
                    <td>Cloned</td>
                </tr>
                <tr>
                    <td class="actiondeleted">&nbsp;</td>
                    <td>Deleted/Unlinked</td>
                </tr>
                <tr>
                    <td class="actionundeleted">&nbsp;</td>
                    <td>Undeleted</td>
                </tr>
                <tr>
                    <td class="actionversioned">&nbsp;</td>
                    <td>Versioned</td>
                </tr>
            </tbody>
        </table>
    </div>-->

<?php
endif;

*/
?>

<h2>IMPReSS Change History</h2>

<noscript>
<p class="error">You must enable JavaScript in your browser for this website to work properly.</p>
</noscript>

<?php
$pip  = new Pipeline($selectedPipelineId);
$proc = new Procedure($selectedProcedureId, $pip->getId());
if ($pip->exists() && $proc->exists()) {
    echo '<p>Currently showing the change history for Procedure ' . e($proc->getItemName()) . ' <span class="procedurekey">'
       . $proc->getItemKey() . '</span> of Pipeline ' . e($pip->getItemName()) . ' <span class="procedurekey">'
       . $pip->getItemKey() . '</span>.</p>';
}

if (User::isAdmin() || User::isSuperAdmin()) {
?>

    <p id="blurb">Please select a Pipeline and/or Procedure to view what has changed.</p>

    <?php echo form_open(); ?>

    <label for="pipeline">Pipeline</label>
    <select id="pipeline" name="pipeline">
    <option value=""></option>
    <?php
    foreach ($pipelines as $p) {
        $chosen = ($selectedPipelineId == $p->getId()) ? ' selected' : '';
        echo "<option value='" . $p->getId() . "'$chosen>" . e($p->getItemName()) . "</option>\n";
    }
    ?>
    </select>

    <label for="procedure">Procedure</label>
    <select id="procedure" name="procedure">
    <option value=""></option>
    <?php
    if ( ! empty($selectedPipelineId)) {
        foreach ($pipelines as $p) {
            if ($selectedPipelineId == $p->getId()) {
                foreach (PipelineHasProcedures::fetchAll($p->getId()) as $proc) { //$p->getProcedures() AS
                    $chosen = ($selectedProcedureId == $proc->getId()) ? ' selected' : '';
                    echo '<option value="' . $proc->getId() . '"' . $chosen . '>' . e($proc->getItemName()) . '</option>' . PHP_EOL;
                }
            }
        }
    }
    ?>
    </select>
	
	<label for="user">User</label>
	<select id="user" name="user">
	<option value=""></option>
	<?php
	foreach ($users as $user) {
		echo '<option value="', e($user), '">', e($user), '</option>', PHP_EOL;
	}
	?>
	</select>

    <?php echo form_close(); ?>

<?php
}

?>

<div id="results">
<?php echo $result; ?>
</div>

<script type="text/javascript">
var results = $('#results').html();
var pipeUrl = '<?php echo base_url() . 'ajax/getprocedures/'; ?>';
var resultsUrl = '<?php echo base_url() . $controller . '/getchangehistory/'; ?>';
$('#pipeline').change(function(e){
    $('#procedure').empty().append($('<option></option>').text(' '));
    if ($(this).val() == "") {
        $('#results').html(results);
    } else {
        $.ajax({
            url: pipeUrl + $(this).val(),
            success: function(procedures){
                $.each(procedures, function(){
                    $('#procedure').append(
                        $('<option></option>')
                        .attr('value', this.procedure_id)
                        .text(this.procedure_key + ' - ' + this.name)
                    );
                });
            }
        });
        loadResults();
    }
});
$('#procedure').change(function(e){
    if($(this).val() != "")
        loadResults();
});
$('#user').change(function(e){
	var user = $(this).val();
	if (user == '') {
		$('table.changehistorytables tbody tr').show();
	} else {
		$('table.changehistorytables tbody tr').each(function(i){
                    $(this).toggle((user == $(this).children('td').eq(2).text()));
		});
	}
});
function loadResults(){
    if($('#pipeline').val() == "")
        return $('#results').html(results);
    $.ajax({
        url: resultsUrl + $('#pipeline').val() + '/' + $('#procedure').val(),
        success: function(html){
            return $('#results').html(html);
        }
    });
}
</script>
