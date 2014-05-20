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
If you supply no arguments then the hardcoded filenames will be used. 
Note: the name of the input spreadsheet is taken as the procedure name. 
Program Usage: ' . PHP_EOL . 'php readinspreadsheet.php -h | -help' . PHP_EOL . 'php readinspreadsheet.php <$spreadsheetfilein> <$xmlfileout>
';
if ( ! defined('STDIN') || @$argv[1] == '-h' || @$argv[1] == '-help') {
    print $message;
    exit(0);
}

/**
 * Commandline arguments have precedence over hardcoded arguments so please supply the program with arguments
 * Please set these variables if you plan on running the program with no arguments
 */
$spreadsheetfilein = './assets/spreadsheets/IMPC Parameters IPGTT.xlsm'; 	//name of input file
$xmlfileout = './assets/xmlfiles/output.xml';                                   //name of output file
$colrow = 6;                                                                    //row on which the column headers are
$maxrowstoread = 1000;                                                          //Maximum rows to read in before program terminates
$maxrowsempty = 5;                                                              //Number of empty rows it reads before it assumes there are no more rows and terminates
$cols = array(
	'parametername'             => 0,
	'parametertype'             => 1,
	'incrementtype'             => 2,
	'incrementvalue'            => 3,
	'incrementminimum'          => 4,
	'optionsvalue'              => 5,
	'optionsdescription'        => 6,
	'unit'                      => 7,
	'datatype'                  => 8,
	'requiredforupload'         => 9,
	'requiredfordataanalysis'   => 10,
	'derivedformula'            => 11,
	'imagemedia'                => 12,
	'annotation'                => 13,
	'graphtype'                 => 14,
	'notes'                     => 15,
	'qcmin'                     => 16,
	'qcmax'                     => 17,
	'qcnotes'                   => 18,
	'increasedid'               => 19,
	'increasedterm'             => 20,
	'null1'                     => 21,
	'decreasedid'               => 22,
	'decreasedterm'             => 23,
	'null2'                     => 24,
	'abnormalid'                => 25,
	'abnormalterm'              => 26,
	'null3'                     => 27,
	'sex'                       => 28
);


/*****************************************************************************/

if( ! empty($argv[1])) $spreadsheetfilein = $argv[1];
if( ! empty($argv[2])) $xmlfileout = $argv[2];

//include PHPExcel (PEAR)
// include 'PHPExcel/PHPExcel.php';
require 'vendor/autoload.php';

//start the timer
$start = microtime(TRUE);


//create the XML object and add procedure
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = TRUE;
$procroot = $dom->createElement('procedure');
$procroot->setAttribute('name', basename(preg_replace('/\..{3,4}$/', '', $spreadsheetfilein)));

//try to open the spreadsheet file
try {
    $PHPExcel = PHPExcel_IOFactory::load($spreadsheetfilein);
    $worksheet = $PHPExcel->getActiveSheet();
}
catch (Exception $e) {
    print 'Error: ' . $e->getMessage();
    exit(1);
}

//define the essential variables for the loop
$continueloop = TRUE;
$emptycounter = 0;
$datarow = $colrow;
$parameternode = $dom->createElement("parameter");
$incrementsnode = $dom->createElement("increments");
$optionsnode = $dom->createElement("options");
$ontologiesnode = $dom->createElement("ontologies");

//the game loop (read the rows line by line)
while($continueloop)
{
    /**
     * Row + Limit Checks
     */
    //increment datarow
    $datarow++;
    //print "$datarow ";
    //check max rows have been read
    if ($datarow >= $maxrowstoread)
        $continueloop = FALSE;

    //check max empty rows read and if parameter node is not empty push the last bit out
    if ($emptycounter >= $maxrowsempty) {
        if ($parameternode->hasChildNodes()) {
            //print "1a ";
            //1.a. push the parameter node to procedure

            $parameternode->appendChild($incrementsnode);
            $parameternode->appendChild($optionsnode);
            $parameternode->appendChild($ontologiesnode);
            $procroot->appendChild($parameternode);
            $incrementsnode = $dom->createElement("increments");
            $optionsnode = $dom->createElement("options");
            $ontologiesnode = $dom->createElement("ontologies");
            //$emptycounter = 0;
            //1.b. create a new
            $parameternode = $dom->createElement("parameter");

            $continueloop = FALSE;
        }
    }

    /**
     * Read in columns
     */
    //
    //Get the values of all the fields:
    //
	
    $parameternamevalue = htmlentities(trim($worksheet->getCellByColumnAndRow($cols['parametername'], $datarow)->getValue()), ENT_QUOTES);
    $incrementvaluevalue = trim($worksheet->getCellByColumnAndRow($cols['incrementvalue'], $datarow)->getValue());
    $optionsvaluevalue = htmlentities(trim($worksheet->getCellByColumnAndRow($cols['optionsvalue'], $datarow)->getValue()));
    $optionsdescriptionvalue = trim($worksheet->getCellByColumnAndRow($cols['optionsdescription'], $datarow)->getValue());
    $parametertypevalue = $worksheet->getCellByColumnAndRow($cols['parametertype'], $datarow)->getValue();
    $incrementtypevalue = $worksheet->getCellByColumnAndRow($cols['incrementtype'], $datarow)->getValue();
    $incrementminimumvalue = $worksheet->getCellByColumnAndRow($cols['incrementminimum'], $datarow)->getValue();
    $unitvalue = $worksheet->getCellByColumnAndRow($cols['unit'], $datarow)->getValue();
    $datatypevalue = $worksheet->getCellByColumnAndRow($cols['datatype'], $datarow)->getValue();
    $requiredforuploadvalue = $worksheet->getCellByColumnAndRow($cols['requiredforupload'], $datarow)->getValue();
    $requiredfordataanalysisvalue = $worksheet->getCellByColumnAndRow($cols['requiredfordataanalysis'], $datarow)->getValue();
    $derivedformulavalue = $worksheet->getCellByColumnAndRow($cols['derivedformula'], $datarow)->getValue();
    $imagemediavalue = $worksheet->getCellByColumnAndRow($cols['imagemedia'], $datarow)->getValue();
    $annotationvalue = $worksheet->getCellByColumnAndRow($cols['annotation'], $datarow)->getValue();
    $graphtypevalue = $worksheet->getCellByColumnAndRow($cols['graphtype'], $datarow)->getValue();
    $notesvalue = $worksheet->getCellByColumnAndRow($cols['notes'], $datarow)->getValue();
    $qcminvalue = $worksheet->getCellByColumnAndRow($cols['qcmin'], $datarow)->getValue();
    $qcmaxvalue = $worksheet->getCellByColumnAndRow($cols['qcmax'], $datarow)->getValue();
    $qcnotesvalue = $worksheet->getCellByColumnAndRow($cols['qcnotes'], $datarow)->getValue();
    $increasedidvalue = $worksheet->getCellByColumnAndRow($cols['increasedid'], $datarow)->getValue();
    $increasedtermvalue = $worksheet->getCellByColumnAndRow($cols['increasedterm'], $datarow)->getValue();
    $decreasedidvalue = $worksheet->getCellByColumnAndRow($cols['decreasedid'], $datarow)->getValue();
    $decreasedtermvalue = $worksheet->getCellByColumnAndRow($cols['decreasedterm'], $datarow)->getValue();
    $abnormalidvalue = $worksheet->getCellByColumnAndRow($cols['abnormalid'], $datarow)->getValue();
    $abnormaltermvalue = $worksheet->getCellByColumnAndRow($cols['abnormalterm'], $datarow)->getValue();
    $sexvalue = $worksheet->getCellByColumnAndRow($cols['sex'], $datarow)->getValue();

    //debug
    //print " $parameternamevalue\n";
    //
    //logic
    //
    //- if the parameternamevalue is there then create a new parameter node
    //- if parameternamevalue is IS/ISN'T there but the options is there or increment is there then add it to existing parameter node
    //- if parameternamevalue is NOT there but options and/or increments are there and so are ontology terms then add ontologies to ontologies node
    //- if next line has a parameternamevalue then push the parameter to the node and create a new node for the new parameter
    //- if row is empty then skip to next line
    //1
    if ( ! empty($parameternamevalue)) {
        //print "Block 1 ";

        if ($parameternode->hasChildNodes()) {
            //print "1a ";
            //1.a. push the parameter node to procedure

            $parameternode->appendChild($incrementsnode);
            $parameternode->appendChild($optionsnode);
            $parameternode->appendChild($ontologiesnode);
            $procroot->appendChild($parameternode);
            $incrementsnode = $dom->createElement("increments");
            $optionsnode = $dom->createElement("options");
            $ontologiesnode = $dom->createElement("ontologies");
            $emptycounter = 0;

            //1.b. create a new
            $parameternode = $dom->createElement("parameter");
        }

        //print "1c\n";
        //1.c. make nodes of all variables and append to parameter node
        $parameternode = $dom->createElement("parameter");
        //parameter name
        $parametername = $dom->createElement('parametername', $parameternamevalue);
        $parameternode->appendChild($parametername);
        //parameter type
        $parametertype = $dom->createElement('parametertype', $parametertypevalue);
        $parameternode->appendChild($parametertype);
        //increment type
        $incrementtype = $dom->createElement('incrementtype', $incrementtypevalue);
        $parameternode->appendChild($incrementtype);
        //increment value - multi
        $incrementvalue = $dom->createElement('incrementvalue', $incrementvaluevalue);
        $incrementsnode->appendChild($incrementvalue);
        //increment minimum
        $incrementminimum = $dom->createElement('incrementminimum', $incrementminimumvalue);
        $parameternode->appendChild($incrementminimum);
        //option value - multi
        $optionsvalue = $dom->createElement('optionsvalue', $optionsvaluevalue);
        //option description - multi
        $optionsdescriptionvalue = $dom->createElement('optionsdescription', $optionsdescriptionvalue);
        //~ options group - multi
        $optionsgroup = $dom->createElement('optionsgroup');
        $optionsgroup->appendChild($optionsvalue);
        $optionsgroup->appendChild($optionsdescriptionvalue);
        $optionsnode->appendChild($optionsgroup);
        //unit
        $unit = $dom->createElement('unit', $unitvalue);
        $parameternode->appendChild($unit);
        //data type
        $datatype = $dom->createElement('datatype', $datatypevalue);
        $parameternode->appendChild($datatype);
        //required for upload
        $requiredforupload = $dom->createElement('requiredforupload', $requiredforuploadvalue);
        $parameternode->appendChild($requiredforupload);
        //required for data analysis
        $requiredfordataanalysis = $dom->createElement('requiredfordataanalysis', $requiredfordataanalysisvalue);
        $parameternode->appendChild($requiredfordataanalysis);
        //derived formula
        $derivedformula = $dom->createElement('derivedformula', $derivedformulavalue);
        $parameternode->appendChild($derivedformula);
        //image media
        $imagemedia = $dom->createElement('imagemedia', $imagemediavalue);
        $parameternode->appendChild($imagemedia);
        //annotation
        $annotation = $dom->createElement('annotation', $annotationvalue);
        $parameternode->appendChild($annotation);
        //graph type
        $graphtype = $dom->createElement('graphtype', $graphtypevalue);
        $parameternode->appendChild($graphtype);
        //notes
        $notes = $dom->createElement('notes', $notesvalue);
        $parameternode->appendChild($notes);
        //qc min
        $qcmin = $dom->createElement('qcmin', $qcminvalue);
        $parameternode->appendChild($qcmin);
        //qc max
        $qcmax = $dom->createElement('qcmax', $qcmaxvalue);
        $parameternode->appendChild($qcmax);
        //qc notes
        $qcnotes = $dom->createElement('qcnotes', $qcnotesvalue);
        $parameternode->appendChild($qcnotes);
        //Deal with ontology info to add tp ontologies node
        //increased id
        $increasedid = $dom->createElement('increasedid', $increasedidvalue);
        //$parameternode->appendChild($increasedid);
        //increased term
        $increasedterm = $dom->createElement('increasedterm', $increasedtermvalue);
        //$parameternode->appendChild($increasedterm);
        //decreased id
        $decreasedid = $dom->createElement('decreasedid', $decreasedidvalue);
        //$parameternode->appendChild($decreasedid);
        //decreased term
        $decreasedterm = $dom->createElement('decreasedterm', $decreasedtermvalue);
        //$parameternode->appendChild($decreasedterm);
        //abnormal id
        $abnormalid = $dom->createElement('abnormalid', $abnormalidvalue);
        //$parameternode->appendChild($abnormalid);
        //abnormal term
        $abnormalterm = $dom->createElement('abnormalterm', $abnormaltermvalue);
        //$parameternode->appendChild($abnormalterm);
        //sex
        $sex = $dom->createElement('sex', $sexvalue);
        //$parameternode->appendChild($sex);
        //add ontologies to ontologiesnode
        //increased
        if ( ! empty($increasedidvalue) || ! empty($increasedtermvalue)) {
            $ontologyitem = $dom->createElement('ontologyitem');
            $ontologyitem->appendChild($dom->createElement('option'));
            $ontologyitem->appendChild($dom->createElement('increment'));
            $ontologyitem->appendChild($dom->createElement('sex', $sexvalue));
            $ontologyitem->appendChild($dom->createElement('selectionoutcome', 'INCREASED'));
            $ontologyitem->appendChild($dom->createElement('mpid', $increasedidvalue));
            $ontologyitem->appendChild($dom->createElement('mpterm', $increasedtermvalue));
            $ontologiesnode->appendChild($ontologyitem);
        }
        //decreased
        if ( ! empty($decreasedidvalue) || ! empty($decreasedtermvalue)) {
            $ontologyitem = $dom->createElement('ontologyitem');
            $ontologyitem->appendChild($dom->createElement('option'));
            $ontologyitem->appendChild($dom->createElement('increment'));
            $ontologyitem->appendChild($dom->createElement('sex', $sexvalue));
            $ontologyitem->appendChild($dom->createElement('selectionoutcome', 'DECREASED'));
            $ontologyitem->appendChild($dom->createElement('mpid', $decreasedidvalue));
            $ontologyitem->appendChild($dom->createElement('mpterm', $decreasedtermvalue));
            $ontologiesnode->appendChild($ontologyitem);
        }
        //abnormal
        if ( ! empty($abnormalidvalue) || ! empty($abnormaltermvalue)) {
            $ontologyitem = $dom->createElement('ontologyitem');
            $ontologyitem->appendChild($dom->createElement('option'));
            $ontologyitem->appendChild($dom->createElement('increment'));
            $ontologyitem->appendChild($dom->createElement('sex', $sexvalue));
            $ontologyitem->appendChild($dom->createElement('selectionoutcome', 'ABNORMAL'));
            $ontologyitem->appendChild($dom->createElement('mpid', $abnormalidvalue));
            $ontologyitem->appendChild($dom->createElement('mpterm', $abnormaltermvalue));
            $ontologiesnode->appendChild($ontologyitem);
        }
    }


    //2
    else if (empty($parameternamevalue) && empty($increasedidvalue) && empty($decreasedidvalue) && empty($abnormalidvalue) && ( strlen($incrementvaluevalue) > 0 || strlen($optionsvaluevalue) > 0 )) {
        //print "Block 2\n";
        //push the increments to increment parent in parameter
        //increment value - multi
        $incrementvalue = $dom->createElement('incrementvalue', $incrementvaluevalue);
        $incrementsnode->appendChild($incrementvalue);
        //push the options to options parent in parameter
        //option value - multi
        $optionsvalue = $dom->createElement('optionsvalue', $optionsvaluevalue);
        //option description - multi
        $optionsdescriptionvalue = $dom->createElement('optionsdescription', $optionsdescriptionvalue);
        //~ options group - multi
        $optionsgroup = $dom->createElement('optionsgroup');
        $optionsgroup->appendChild($optionsvalue);
        $optionsgroup->appendChild($optionsdescriptionvalue);
        $optionsnode->appendChild($optionsgroup);
    }

    //3
    else if (empty($parameternamevalue) && (strlen($incrementvaluevalue) > 0 || strlen($optionsvaluevalue) > 0)) { //and one or more ontologies are filled out
        //print "Block 3\n";
        //increased
        if ( ! empty($increasedidvalue)) {
            $ontologyitem = $dom->createElement('ontologyitem');
            $ontologyitem->appendChild($dom->createElement('option', $optionsvaluevalue));
            $ontologyitem->appendChild($dom->createElement('increment', $incrementvaluevalue));
            $ontologyitem->appendChild($dom->createElement('sex', $sexvalue));
            $ontologyitem->appendChild($dom->createElement('selectionoutcome', 'INCREASED'));
            $ontologyitem->appendChild($dom->createElement('mpid', $increasedidvalue));
            $ontologyitem->appendChild($dom->createElement('mpterm', $increasedtermvalue));
            $ontologiesnode->appendChild($ontologyitem);
        }
        //decreased
        if ( ! empty($decreasedidvalue)) {
            $ontologyitem = $dom->createElement('ontologyitem');
            $ontologyitem->appendChild($dom->createElement('option', $optionsvaluevalue));
            $ontologyitem->appendChild($dom->createElement('increment', $incrementvaluevalue));
            $ontologyitem->appendChild($dom->createElement('sex', $sexvalue));
            $ontologyitem->appendChild($dom->createElement('selectionoutcome', 'DECREASED'));
            $ontologyitem->appendChild($dom->createElement('mpid', $decreasedidvalue));
            $ontologyitem->appendChild($dom->createElement('mpterm', $decreasedtermvalue));
            $ontologiesnode->appendChild($ontologyitem);
        }
        //abnormal
        if ( ! empty($abnormalidvalue)) {
            $ontologyitem = $dom->createElement('ontologyitem');
            $ontologyitem->appendChild($dom->createElement('option', $optionsvaluevalue));
            $ontologyitem->appendChild($dom->createElement('increment', $incrementvaluevalue));
            $ontologyitem->appendChild($dom->createElement('sex', $sexvalue));
            $ontologyitem->appendChild($dom->createElement('selectionoutcome', 'ABNORMAL'));
            $ontologyitem->appendChild($dom->createElement('mpid', $abnormalidvalue));
            $ontologyitem->appendChild($dom->createElement('mpterm', $abnormaltermvalue));
            $ontologiesnode->appendChild($ontologyitem);
        }
    }

    //4
    else {
        //print "Block 3\n";
        $emptycounter++;
    }
}

$dom->appendChild($procroot);

print PHP_EOL . 'Completed successfully' . // in ' . (int)(microtime(TRUE) - $start) . 's' . PHP_EOL . 
PHP_EOL . 'Created file ' . $xmlfileout . ' (' . $dom->save($xmlfileout) . ' bytes)';
exit(0);
