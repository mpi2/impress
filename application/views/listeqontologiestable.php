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
* @param Parameter $param
* @param string $controller controller
* @param bool $hidden If set to TRUE the table will be hidden using CSS
* @param bool $hideemptytable If there is no data in a table and $hidemepty is set to TRUE then the table will be hidden using CSS
* @param bool $hideemptyrows If a row contains no data then it will not be shown
* @see listontologiestablemod.php
* @see listeqontologiestable.php for historic comparison
*/

//make option hidden = FALSE by default
if( ! isset($hidden)) $hidden = FALSE;
//ibid hideemptytable
if( ! isset($hideemptytable)) $hideemptytable = FALSE;
//ibid hideemptyrows
if( ! isset($hideemptyrows)) $hideemptyrows = FALSE;

//set main array with default values
$items = array(
    ESelectionOutcome::INCREASED => array(),
    ESelectionOutcome::DECREASED => array(),
    ESelectionOutcome::ABNORMAL  => array(),
    ESelectionOutcome::INFERRED  => array(),
    ESelectionOutcome::SOTRAIT   => array()
);

//load data
$tableemptyflag = TRUE;
foreach($param->getOntology()->getEQTerms() AS $eq){
	$items[$eq->getSelectionOutcome()][] = array('eq' => $eq);
	$tableemptyflag = FALSE;
}

//if a table is empty and the hide empty rows option is set to true then all 
//that will show up will be the headings. So to stop that from happening the
//hide empty rows option is turned to false. If they want to hide an empty 
//table they should use the hide empty table option
if($tableemptyflag)
	$hideemptyrows = FALSE;

//debugging
// echo '<p>MP Flags: <br>'
// . 'table empty flag: ' . var_export($tableemptyflag,TRUE) . '<br>'
// . 'hide empty table: ' . var_export($hideemptytable,TRUE) . '<br>'
// . 'hidden: '           . var_export($hidden,TRUE)         . '<br>'
// . 'hide empty rows: '  . var_export($hideemptyrows,TRUE)  . '</p>';

if($tableemptyflag && $hideemptytable){
	echo '<p class="noontologiesmsg">There are no Entity-Quality Ontology associations for this Parameter</p>';
}else{
?>

<table width="100%" border="1" class="onttable eqonttable<?php if($hidden === TRUE) echo ' hidden'; ?>">
<tr>
	<th>&nbsp;</th>
	<th>Option</th>
	<th>Increment</th>
	<th>Entities</th>
	<th>Qualities</th>
	<th>Sex</th>
</tr>
<?php
foreach($items AS $selectionOutcome => $arr){
	if(empty($arr) && $hideemptyrows === FALSE){
		echo '<tr><td class="OntologyOutcome">' . $selectionOutcome . '</td>' . str_repeat("<td>&nbsp;</td>", 13-8) . '</tr>' . PHP_EOL; //7
	}
	else if(empty($arr) && $hideemptyrows === TRUE){
		continue;
	}
	else{
		$i = 0;
		foreach($arr AS $a){
			//outcome
			if($i == 0)
				echo '<tr><td rowspan="' . count($arr) . '" class="OntologyOutcome">' . $selectionOutcome . '</td>' . PHP_EOL;
			else
				echo '<tr>';
			//option
			if($i == 0){
				$optionName = e($a['eq']->getOption()->getName());
				$optionDesc = e($a['eq']->getOption()->getDescription());
				echo '<td rowspan="' . count($arr) . '">';
				if( ! empty($optionName) && ! empty($optionDesc) )
					echo $optionName . ' <span class="optiondescription">' . $optionDesc . '</span></td>' . PHP_EOL;
				else if( ! empty($optionName))
					echo $optionName . '</td>' . PHP_EOL;
				else
					echo $optionDesc . '</td>' . PHP_EOL;
			}
			//increment
			if($i == 0)
				echo '<td rowspan="' . count($arr) . '">' . e($a['eq']->getIncrement()->getIncrementString()) . '</td>' . PHP_EOL; // . ' ' . $a['eq']->getIncrement()->getIncrementUnit()
			//Entity-Quality Terms and IDs
			$eq = $a['eq']->getEQs();
			$eqEntityArr = array();
			if( ! empty($eq['entity1_id']))
				$eqEntityArr[] = e($eq['entity1_term']) . ' [' . TermLinker::LinkId($eq['entity1_id']) . ']';
			if( ! empty($eq['entity2_id']))
				$eqEntityArr[] = e($eq['entity2_term']) . ' [' . TermLinker::LinkId($eq['entity2_id']) . ']';
			if( ! empty($eq['entity3_id']))
				$eqEntityArr[] = e($eq['entity3_term']) . ' [' . TermLinker::LinkId($eq['entity3_id']) . ']';
			$eqQualityArr = array();
			if( ! empty($eq['quality1_id']))
				$eqQualityArr[] = e($eq['quality1_term']) . ' [' . TermLinker::LinkId($eq['quality1_id']) . ']';
			if( ! empty($eq['quality2_id']))
				$eqQualityArr[] = e($eq['quality2_term']) . ' [' . TermLinker::LinkId($eq['quality2_id']) . ']';
			echo '<td>' . join(' ', $eqEntityArr) . '</td>' . PHP_EOL;
			echo '<td>' . join(' ', $eqQualityArr) . '</td>' . PHP_EOL;
			//sex
			if($i == 0)
				echo '<td rowspan="' . count($arr) . '">' . $a['eq']->getSex() . '</td>' . PHP_EOL;
			echo '</tr>' . PHP_EOL;
			
			$i++;
		}
	}
}

unset($tableemptyflag);
unset($hideemptytable);
unset($hidden);
unset($hideemptyrows);

?>
</table>

<?php
}
