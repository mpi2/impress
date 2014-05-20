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
 * @param SOP $sop The SOP object
 * @param Procedure $proc The Procedure object
 * @param int $pipelineId
 * @param string $controller
 */

echo '<div id="soppdf">' . anchor('protocol/' . $proc->getId() . '/' . $pipelineId . '/' . $proc->getItemKey(), 'View as PDF');
echo ' / ' . anchor('procedureontologies/' . $proc->getId() . '/' . $pipelineId, 'Display Ontologies');
echo ' / ' . anchor('procedurexml/' . $proc->getId() . '/' . $pipelineId, 'Example XML Submission');
echo ' / ' . anchor('history/' . $pipelineId . '/' . $proc->getId(), 'Change History');
echo '</div>';
echo '<h2>' . $sop->getTitle() . ' <span class="procedurekey dark">' . $proc->getItemKey() . '</span></h2>' . PHP_EOL;

$levelTitle = '';
$sections = $sop->getSections();
$secs = array();

//put sop section title and text in $secs array
if( ! empty($sections)){
    foreach($sections as $section){
        if($section->getLevelText() != $levelTitle){
            $levelTitle = e($section->getLevelText());
            $secs[$section->getSectionTitle()->getTitle()][] = "<h4>$levelTitle</h4>\n";
        }
        $level = str_repeat("\t", $section->getLevel());
        $secs[$section->getSectionTitle()->getTitle()][] = $level . $section->getSectionText() . PHP_EOL; //dexss() removed surrounding p tags
    }
}

//get measured and metadata parameters
$measuredParams = array();
$metadataParams = array();
foreach ($proc->getParameters() as $param) {
    if ($param->getType() == EParamType::METADATA) {
        $metadataParams[] = $param;
    } else {
        $measuredParams[] = $param;
    }
}

//put the measured and metadata parameters into their own sections
if (empty($measuredParams)) {
    $secs['Parameters'][] = '<p>This Procedure does not contain any Measured Parameters</p>';
} else {
    $secs['Parameters'][] = $this->load->view(
        'listparameterstable',
        array(
            'params' => $measuredParams,
            'procedureId' => $proc->getId(),
            'pipelineId' => $pipelineId,
            'controller' => $controller
        ),
        TRUE
    );
}
if (empty($metadataParams)) {
    $secs['Metadata'][] = '<p>This Procedure does not contain any Metadata Parameters</p>';
} else {
    $secs['Metadata'][] = $this->load->view(
        'listparameterstable',
        array(
            'params' => $metadataParams,
            'procedureId' => $proc->getId(),
            'pipelineId' => $pipelineId,
            'controller' => $controller
        ),
        TRUE
    );
}

//display the menu
?>

<ul id="sopmenu">
<?php
foreach (array_keys($secs) as $title) {
    echo "<li><a href='#" , e($title) , "'>", e($title), "</a></li>\n";
}
?>
</ul>

<?php
//display the sections
if ( ! empty($secs)) {
    foreach ($secs as $sectionTitle => $sections) {
        echo "<h3><a name='" . e($sectionTitle) . "'>" . $sectionTitle . "</a></h3>\n";
        if ($sectionTitle == 'Metadata') echo "<div class='notexpandable'>\n";
        else echo "<div class='expandable'>\n";
        foreach ($sections as $section)
            echo $section;
        echo "</div>\n";
    }
}
