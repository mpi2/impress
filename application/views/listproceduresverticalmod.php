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
 *
 * @param array $procs An array of Procedure objects
 * @param int $pipelineId
 * @param string $controller controller
 *
 */

if (empty($procs)):
?>

<p>This Pipeline does not currently contain any Procedures (or you do not have the correct permissions to view them).</p>

<?php
else:

$weeks = array();

foreach ($procs as $proc) {
    $weeks[$proc->getWeekObject()->getId()][] = $proc;
}

unset($procs);

?>
<table border="1" id="weektable" class="weektablevertical">
<?php

$this->load->helper("tick_or_cross");

foreach ($weeks as $week => $procs) {
    echo '<tr><th class="weeks week', $week, '">(', $procs[0]->getWeekObject()->getStageLabel(), ')<br><nobr>', e($procs[0]->getWeekLabel()), '</nobr></th><td>', PHP_EOL;
    echo '<table width="100%" class="weekprocs">', PHP_EOL;
    foreach ($procs as $proc) {
        if ( ! should_display($proc)) {
            continue;
        }

        $tickorcross = ($proc->isMandatory()) ? tick_or_cross(TRUE) . 'Mandatory' : '';
        echo '<tr><td class="mandatory"><nobr>' . $tickorcross . '</nobr></td><td>', PHP_EOL;
        $purpose = $proc->getDescription();
        $purposeType = 'D'; //the description of the SOP can come from either the procedure (D)escription or (S)OP
        $sop = $proc->getSOP();

        echo '<div class="procedureitem">', PHP_EOL;
        //if a sop document exists then link to that otherwise just link to the parameters
        if($sop->exists()){
            echo '<span class="proceduretitle">',
                 anchor('protocol/' . $proc->getId() . '/' . $pipelineId, e($proc->getItemName()) . ' <span class="procedurekey">' . $proc->getItemKey() . '</span>', array('title'=>'Display Protocol')),
                 '</span>', PHP_EOL;
        }else{
            echo '<span class="proceduretitle">',
                 e($proc->getItemName()),
                 ' <span class="procedurekey">', $proc->getItemKey(), '</span>',
                 '</span>', PHP_EOL;
        }

        //get purpose from procedure description if available but
        //if there is no description, then fetch the first line from the SOP purpose section...
        //Newly modified to just display the sop purpose by adding the or clause... bodgetastic
        if (empty($purpose) || ! empty($purpose)) {
            $secs = $sop->getSections();
            if( ! empty($secs)){
                foreach ($secs as $sec){
                    if ($sec->getSectionTitle()->getId() == 1) { //1 == Purpose
                        $purpose .= strip_tags($sec->getSectionText());
                        break;
                    }
                }
            }
            $purposeType = 'S';
        }
        
        if (empty($purpose)) {
            echo '<span class="sopteaser">No description available yet</span><br>', PHP_EOL;
        } else if ($purposeType == 'D') {
            echo '<span class="sopteaser expandable">', $purpose, '</span><br>', PHP_EOL;
        } else if ($purposeType == 'S') {
            echo '<span class="sopteaser">', substr($purpose, 0, 100), '... ',
                 anchor('protocol/' . $proc->getId() . '/' . $pipelineId, 'continue reading Protocol...', array('title'=>'View SOP')),
                 '</span><br>', PHP_EOL;
        }
        
        echo '<div class="view first">' . anchor('parameters/' . $proc->getId() . '/' . $pipelineId, 'View Parameters <img src="' . base_url() . 'images/parameters.png" alt="View Parameters" title="View Parameters" border="0">') . '</div>';
        echo '<div class="view">' . anchor('procedureontologies/' . $proc->getId() . '/' . $pipelineId, 'View Ontology Annotations <img src="' . base_url() . 'images/ontologies.png" alt="View Ontologies" title="View Ontologies" border="0">') . '</div>';
        echo '<div class="view last">' . anchor('history/' . $pipelineId . '/' . $proc->getId(), 'View Change History <img src="' . base_url() . 'images/history.png" alt="View Change History" title="View Changes made to this Procedure" border="0">') . '</div>';
        echo '<br></div>' . PHP_EOL;
        echo '</td></tr>';
    }
    echo "</table></td></tr>\n";
}
?>
</table>
<?php

endif;
