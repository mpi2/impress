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
* @param Parameter[] $params An array of Parameter objects
* @param int $procedureId (Optional) The id of the procedure to which these parameters belong
* @param int $pipelineId (Optional) The id of the pipeline
* @param bool $fixedsizetable (optional) 
* @param string $controller controller
*/

$procedureId = (isset($procedureId)) ? (int)$procedureId : null;
if( ! isset($fixedsizetable) || FALSE === (bool)$fixedsizetable) $style = '';
else $style = ' style="table-layout:fixed"';
?>

<table border="1" id="paramtable"<?php echo $style; ?>>
<thead>
<tr align="center">
	<th scope="col"></th>
	<th scope="col">Version</th>
	<th scope="col">Type</th>
	<th scope="col" title="Required for data upload">Req.&nbsp;Upload</th>
	<th scope="col" title="Required for data analysis">Req.&nbsp;Analysis</th><!--<th>Important</th>-->
	<th scope="col">Annotation</th>
	<th scope="col">Increment</th>
	<th scope="col">Option</th>
	<th scope="col">Ontology Options</th>
	<!--<th scope="col">Media</th>-->
	<th scope="col">Derived</th>
	<th scope="col">Unit</th>
	<th scope="col">Data Type</th>
</tr>
</thead>
<tbody>

<?php

$this->load->helper('tick_or_cross');

$ontologyGroups = array();

foreach ($params as $param){
    if ( ! should_display($param)) {
        continue;
    }

    echo '<tr align="center">';

    //parameter name
    $paramName = '';
    if ($param->getType() == EParamType::METADATA) {
        $paramName = e($param->getItemName()) . ' <span class="parameterkey">' . $param->getItemKey() . '</span>';
    } else {
        $paramName = anchor(
            'parameterontologies/' . $param->getId() . '/' . $procedureId, 
            e($param->getItemName()) . ' <span class="parameterkey">' . $param->getItemKey() . '</span>', 
            array('title'=>'View Parameter Associations')
         );
    }
    echo '<td align="left" class="param">' . $paramName . '</td>' . PHP_EOL;
    //version
    echo '<td>' . (int)$param->getMajorVersion() . '.' . $param->getMinorVersion() . '</td>' . PHP_EOL;
    //type
    echo '<td class="leftrightpad">' . $param->getType() . '</td>' . PHP_EOL;
    //required
    echo '<td>' . tick_or_cross($param->isRequired(), 7) . '</td>' . PHP_EOL;
    //important
    echo '<td>' . tick_or_cross($param->isImportant(), 7) . '</td>' . PHP_EOL;
    //metadata
    //echo '<td>' . tick_or_cross($param->isMeta(), 7) . '</td>' . PHP_EOL;
    //annotation
    echo '<td>' . tick_or_cross($param->isAnnotation(), 7) . '</td>' . PHP_EOL;
    //increment
    $incs = '';
    if($param->isIncrement()){
        foreach ($param->getIncrements() as $i) {
            if ( ! $i->isActive())
                continue;
            $incstr = e(trim($i->getIncrementString()));
            $inctyp = trim($i->getIncrementType());
            $incmin = trim($i->getIncrementMin());
            if (strlen($incstr) > 0) {
                $incs .= '<span class="multi">' . $incstr . '</span>';
            } else if ($inctyp == EIncrementType::DATETIME) {
                $incs .= $inctyp;
                if (strlen($incmin) > 0)
                    $incs .= '<br> Minimum: ' . $incmin;
            } else if (strlen($incmin) > 0) {
                $incs .= 'Minimum: ' . $incmin;
            } else if ($inctyp != EIncrementType::REPEAT) {
                $incs .= '<span class="multi">' . $inctyp . '</span>';
            } else {
                $incs .= tick_or_cross(FALSE, 7);
            }
        }
    }else{
            $incs = tick_or_cross(FALSE, 7);
    }
    echo '<td>' . $incs . '</td>' . PHP_EOL;
    //option
    $ops = '';
    if($param->isOption()){
        foreach ($param->getOptions() as $i) {
            if ( ! $i->isActive())
                continue;
            $ops .= '<span class="multi">' . e($i->getName());
            $ods = e($i->getDescription());
            if ( ! empty($ods))
                $ops .= '<span class="optiondescription" title="' . $ods . '"> (' . $ods . ')</span>';
            $ops .= '</span>';
        }
    }else{
            $ops = tick_or_cross(FALSE, 7);
    }
    echo '<td>' . $ops . '</td>' . PHP_EOL;
    //ontology option
    echo '<td>';
    foreach ($param->getOntologyGroups() as $group) {
        if ( ! $group->isActive())
            continue;
        
        $options = array();
        if (isset($ontologyGroups[$group->getId()])) {
            $options = $ontologyGroups[$group->getId()];
        } else {
            $collapsedOntologyOptions = array();
            $uncollapsedOntologyOptions = array();
            foreach ($group->getOntologyOptions() as $o) {
                if ($o->isActive() && ! $o->isDeleted()) {
                    $line = '<span class="multi">[' . $o->getOntologyId() . '] ' . e($o->getOntologyTerm()) . '</span>' . PHP_EOL;
                    if ($o->isCollapsed())
                        $collapsedOntologyOptions[] = $line;
                    else
                        $uncollapsedOntologyOptions[] = $line;
                }
            }
            $ontologyGroups[$group->getId()]['collapsed'] = $collapsedOntologyOptions;
            $ontologyGroups[$group->getId()]['uncollapsed'] = $uncollapsedOntologyOptions;
            $options = $ontologyGroups[$group->getId()];
        }
        
        //print uncollapsed options as normal
        if (isset($options['uncollapsed'])) {
            foreach ($options['uncollapsed'] as $line)
                echo $line;
        }
        //print collapsed options
        if (empty($options['collapsed'])) {
            //print nothing
        } else if (count($options['collapsed']) < $this->config->item('ontologyoptionlistlimit')) {
            echo '<div class="collapsed"><a href="#">+ Expand</a>';
            echo '<div class="collapsedOntologyOptions">';
            foreach($options['collapsed'] as $line)
                echo $line;
            echo '</div>';
            echo '</div>';
        } else if (count($options['collapsed']) >= $this->config->item('ontologyoptionlistlimit')
        ) {
            echo anchor('ontologyoptions/' . $param->getId() . '/'
               . $procedureId . '/' . $pipelineId, 'View all Ontology Options');
        }
    }
    echo '</td>' . PHP_EOL;
    //derived
    $drv = '';
    if($param->isDerived()) $drv = e($param->getDerivation());
    else $drv = tick_or_cross(FALSE, 7);
    echo '<td>' . $drv . '</td>' . PHP_EOL;
    //unit
    $unit = e($param->getUnit());
    if(empty($unit)) $unit = tick_or_cross(FALSE, 7);
    echo '<td>' .  $unit . '</td>' . PHP_EOL;
    //data type
    $valueType = $param->getValueType();
    if(empty($valueType)) $valueType = tick_or_cross(FALSE, 7);
    echo '<td>' . $valueType . '</td>' . PHP_EOL;

    echo "</tr>\n";

}
?>
</tbody>
</table>
