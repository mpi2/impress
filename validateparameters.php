<?php

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


error_reporting(E_ALL ^ E_STRICT);


//shell access only!
$message = '
Only shell access to this program is permitted. 
If you supply no argument then the hardcoded filename will be used. 
Program Usage: ' . PHP_EOL . 'php validateparameters.php -h | -help' . PHP_EOL . 'php validateparameters.php <$xmlfilein>
';
if ( ! defined('STDIN') || @$argv[1] == '-h' || @$argv[1] == '-help') {
    print $message;
    exit(0);
}

/**
 * Commandline arguments have precedence over hardcoded arguments so please supply the program with arguments
 * Please set this variable if you plan on running the program with no arguments
 */
$xmlfilein = './assets/xmlfiles/output.xml'; //name of input file
	

/******************************************************************************/

//set initial variables
if( ! empty($argv[1])) $xmlfilein = $argv[1];
$_errors = array();

//error message function
function _error($msg, $row = null, $parameter = null)
{
    global $_errors;
    $str = '';
    if ($row > 0 && $parameter)
        $str = "Row $row '" . (string) $parameter->parametername . "': ";
    $str .= "$msg";
    $_errors[] = $str;
}

//open file
$proc = simplexml_load_file($xmlfilein);
if($proc === FALSE){print $e->getMessage() . "\n"; exit(1);}
		
//procedure name
if(empty($proc['name'])) _error('Procedure Name Missing');

//check for duplicate parameter names
$names = array();
foreach($proc->parameter AS $parameter) $names[] = (string)$parameter->parametername;
foreach(array_count_values($names) AS $name => $count){
	if($count > 1) _error('Duplicate parameter found: ' . $name);
}

//loop through the parameters
$row = 1;
foreach($proc->parameter AS $parameter)
{
    //grab increments
    $increments = array();
    foreach ($parameter->increments->children() AS $incs) {
        $v = trim((string) $incs);
        if (strlen($v) > 0)
            $increments[] = $v;
    }
    //grab options
    $options = array();
    foreach ($parameter->options AS $o) {
        foreach ($o->optionsgroup AS $og) {
            $ov = trim((string) $og->optionsvalue);
            $od = trim((string) $og->optionsdescription);
            if ( ! empty($ov) || ! empty($od))
                $options[] = array('value' => $ov, 'description' => $od);
        }
    }
    //grab option/parameter ontologies
    $opontologies = array();
    foreach ($parameter->ontologies AS $onts) {
        foreach ($onts->ontologyitem AS $oi) {
            $opontologies[] = array(
                'option' => (string) $oi->option,
                'increment' => (string) $oi->increment,
                'sex' => (string) $oi->sex,
                'selectionoutcome' => (string) $oi->selectionoutcome,
                'mpid' => (string) $oi->mpid,
                'mpterm' => (string) $oi->mpterm
            );
        }
    }

    //parameter name
    $parametername = trim((string) $parameter->parametername);
    if (empty($parametername))
        _error('Empty Parameter name', $row, $parameter);
    //parameter type
    if (!in_array((string) $parameter->parametertype, array('Measured', 'Derived', 'MetaData')))
        _error('Invalid Parameter Type', $row, $parameter);
    //parameter type - derived (if parameter type derived then derived formala required)
    $derivedformula = trim((string) $parameter->derivedformula);
    if ((string) $parameter->parametertype == 'Derived' && empty($derivedformula))
        _error('Derived Parameter but no Derived Formula', $row, $parameter);
    //derived formula
    if ((string) $parameter->parametertype != 'Derived' && !empty($derivedformula))
        _error('Derived Formula filled out for non-derived parameter', $row, $parameter);
    //data type
    if (!in_array((string) $parameter->datatype, array('Float', 'Text', 'Boolean', 'Date Time', 'Date', 'Time', 'Integer', 'Image')))
        _error('Invalid data type', $row, $parameter);
    //required for upload
    if (!in_array((string) $parameter->requiredforupload, array('Yes', 'No')))
        _error('Invalid required for upload', $row, $parameter);
    //requried for data analysis
    if (!in_array((string) $parameter->requiredfordataanalysis, array('Yes', 'No')))
        _error('Invalid required for data analysis', $row, $parameter);
    //requried for data analysis should only be for metadata parameters
    if ('Yes' == (string) $parameter->requiredfordataanalysis && (string) $parameter->parametertype != 'MetaData')
        _error('Only MetaData parameters can be Required for data analysis', $row, $parameter);
    //if increment value then increment type required (but not other way round)
    $incrementtype = trim((string) $parameter->incrementtype);
    if (!empty($increments) && !in_array($incrementtype, array('Time Point', 'Simple repeat', 'Date Time')))
        _error('Increments provided with missing/invalid increment type', $row, $parameter);
    $incrementminimum = trim((string) $parameter->incrementminimum);
    //if increment type then either increments or increment min required
    if (!empty($incrementtype) && empty($increments) && empty($incrementminimum))
        _error('Increment Type but no Increments or Increment Minimum', $row, $parameter);
    //increment minimum should be empty if there are increments
    if (!empty($incrementminimum) && !empty($increments))
        _error('Increment Minimum value should not be supplied if increments present', $row, $parameter);
    //increment minimum must be numeric if it isn't empty
    if (!empty($incrementminimum) && (!is_numeric($incrementminimum) || (int) $incrementminimum < 1))
        _error('Increment Minimum must be a positive number', $row, $parameter);
    //image media
    if (!in_array((string) $parameter->imagemedia, array('Yes', 'No')))
        _error('Image/Media required to be either Yes or No', $row, $parameter);
    //annotation
    if (!in_array((string) $parameter->annotation, array('Yes', 'No')))
        _error('Annotation required to be either Yes or No', $row, $parameter);
    //graph type allows empty
    if (!in_array((string) $parameter->graphtype, array('', '1D', '2D', 'Categorical', 'Image')))
        _error('Invalid Graph Type', $row, $parameter);
    //unit
    $unit = trim((string) $parameter->unit);
    if (!empty($unit) && is_numeric($unit))
        _error('Unit supplied is a number; expecting text', $row, $parameter);
    if ($unit == '-' || preg_match('/[^\/\^\-\.\*\)\(\+a-zA-Z0-9%\\\\ ]/', $unit) != 0)
        _error('Invalid character identified in unit', $row, $parameter);
    //qcmin
    $qcmin = trim((string) $parameter->qcmin);
    if (strlen($qcmin) > 0 && !is_numeric($qcmin))
        _error('QC Minimum value must be a number', $row, $parameter);
    //qcmax
    $qcmax = trim((string) $parameter->qcmax);
    if (strlen($qcmax) > 0 && !is_numeric($qcmax))
        _error('QC Maximum value must be a number', $row, $parameter);
    //qcmin/max should not be equal
    //if(strlen($qcmin) > 0 && $qcmin == $qcmax) _error('QC Minimum and QC Maximum cannot have the same value', $row, $parameter);
    //if qc min is filled in then so should qc max
    //if( (strlen($qcmin) > 0 && strlen($qcmax) == 0) || (strlen($qcmax) > 0 && strlen($qcmin) == 0) ) _error('Both QC Minimum and Maximum need to be filled out', $row, $parameter);
    //qcnotes - qcmin or max must be set otherwise qcnotes should be empty
    $qcnotes = trim((string) $parameter->qcnotes);
    if (strlen($qcmin) == 0 && strlen($qcmax) == 0 && !empty($qcnotes))
        _error('QC Min and QC Max are empty yet you have something in QC Notes', $row, $parameter);
    //increased ontology - id and term required if either exists or leave empty
    $iid = trim((string) $parameter->increasedid);
    $iterm = trim((string) $parameter->increasedterm);
    if ((empty($iid) && !empty($iterm)) || (!empty($iid) && empty($iterm)))
        _error('Unbalanced Ontology Increased annotation', $row, $parameter);
    //decreased ontology - ibid
    $did = trim((string) $parameter->decreasedid);
    $dterm = trim((string) $parameter->decreasedterm);
    if ((empty($did) && !empty($dterm)) || (!empty($did) && empty($dterm)))
        _error('Unbalanced Ontology Decreased annotation', $row, $parameter);
    //abnormal ontology - ibid
    $aid = trim((string) $parameter->abnormalid);
    $aterm = trim((string) $parameter->abnormalterm);
    if ((empty($aid) && !empty($aterm)) || (!empty($aid) && empty($aterm)))
        _error('Unbalanced Ontology Abnormal annotation', $row, $parameter);
    //when parameter type is metadata it should not have ontologies associated with it
    if ((string) $parameter->parametertype == 'MetaData' && !empty($opontologies))
        _error('MetaData parameters should not have an ontology', $row, $parameter);
    //if option desc then option required
    foreach ($options AS $option) {
        if (strlen($option['value']) == 0 && !empty($option['description']))
            _error('Option description without a value', $row, $parameter);
    }
    //check options for duplicates
    foreach ($options AS $option) {
        if (is_duplicated_in_array($option, $options))
            _error('You appear to have duplicate options', $row, $parameter);
    }
    //check increments for duplicates
    foreach ($increments AS $increment) {
        if (is_duplicated_in_array($increment, $increments))
            _error('You appear to have duplicate increments', $row, $parameter);
    }
    //ontology annotation
    foreach ($opontologies AS $os) {
        //selection outcome required
        if (!in_array($os['selectionoutcome'], array('INCREASED', 'DECREASED', 'ABNORMAL')))
            _error('Invalid Selection Outcome for ontology annotation', $row, $parameter);
        //MP ID required
        if (preg_match('/^(MP:[0-9]{7})$/', $os['mpid']) == 0)
            _error('Invalid or missing MP_ID given for ontology annotation', $row, $parameter);
        //If MP ID given MP Term must be supplied too
        if (empty($os['mpterm']))
            _error('MP Term missing for ontology annotation', $row, $parameter);
        //validate sex
        if (!in_array($os['sex'], array('', 'Male', 'Female')))
            _error('Invalid Sex for ontology annotation', $row, $parameter);
        //grab options and also grab increments values and make sure that they match the ontology annotation option or increment
        $aoptions = array();
        foreach ($options AS $option)
            $aoptions[] = $option['value'];
        if (strlen($os['option']) > 0 && !in_array($os['option'], $aoptions))
            _error('Unidentified Option found in ontology annotation', $row, $parameter);
        if (strlen($os['increment']) > 0 && !in_array($os['increment'], $increments))
            _error('Unidentified Increment "' . $os['increment'] . '" found in ontology annotation', $row, $parameter);
    }

    $row++;
}

function is_duplicated_in_array($needle, $haystack)
{
    //if it's a flat array
    if (!is_array($needle) && is_array($haystack)) {
        $count = 0;
        foreach ($haystack AS $a) {
            if ($a == $needle)
                $count++;
        }
        return ($count > 1);
    }
    //if it's a hash array with one row in the $val needle
    else if (is_array($needle) && is_array($haystack)) {
        $keys = array_keys($needle);
        $flattened = array();
        foreach ($haystack AS $a) {
            $keymatches = 0;
            $valuesconcat = '';
            foreach ($keys AS $key) {
                if (!array_key_exists($key, $a))
                    throw new Exception('Value keys do not match array keys');
                if ($needle[$key] == $a[$key]) {
                    $keymatches++;
                    $valuesconcat .= (string) $key . (string) $needle[$key];
                }
            }
            if (count($keys) == $keymatches)
                $flattened[] = $valuesconcat;
        }
        foreach ($flattened AS $v) {
            if (is_duplicated_in_array($v, $flattened))
                return TRUE;
        }
        return FALSE;
    }
}

//display errors
if ( ! empty($_errors)) {
    print "\n" . count($_errors) . " Errors found: \n\n";
    print join("\n", $_errors);
    exit(1);
}

//no errors just exit
//print "OK\n";
exit(0);
