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
Program Usage: ' . PHP_EOL . 'php xmltotable.php -h | -help' . PHP_EOL . 'php xmltotable.php <$xmlfilein> <$htmlfileout>
';
if ( ! defined('STDIN') || @$argv[1] == '-h' || @$argv[1] == '-help') {
    print $message;
    exit(0);
}

/**
 * Commandline arguments have precedence over hardcoded arguments so please supply the program with arguments
 * Please set these variables if you plan on running the program with no arguments
 */
$xmlfilein = './assets/xmlfiles/output.xml';       //name of input file
$htmlfileout = './assets/htmlfiles/outtable.html'; //name of output file



/*****************************************************************************/

if( ! empty($argv[1])) $xmlfilein = $argv[1];
if( ! empty($argv[2])) $htmlfileout = $argv[2];

$content  = "<html><head><style>table tr{vertical-align:top;}.oblock,.ioblock{display:block;margin:0}.ioblock:nth-child(even){background-color:#F0F0E0}";
$content .= ".oblock.I{background-color:lightpink}.oblock.A{background-color:lightblue}.oblock.D{background-color:lightgreen}</style></head><body>\n";

try {
    if ( !file_exists($xmlfilein))
        throw new Exception('The XML file does not exist - ' . $xmlfilein);
    exec("php ./validateparameters.php ./$xmlfilein", $validationcheck, $returnvar);
    if ($returnvar == 1) {
        $content .= '<p>' . nl2br(join("\n", $validationcheck)) . '</p>';
    } else if ($returnvar == 126) {
        $content .= 'It appears validateparameters.php needs to be made executable. Attempting to CHMOD the file now... ';
        $content .= (chmod('./validateparameters.php', 0755)) ? 'Success! Please reload the page.' : 'Failed! Please make this file writeable manually.';
        exit;
    } else if ($returnvar != 0) {
        throw new Exception('An error (' . $returnvar . ') occured while trying to run the validator on the commandline. Please consult a geek. ' . $validationcheck);
    }
    $proc = simplexml_load_file($xmlfilein);
    if ($proc === FALSE)
        throw new Exception('An error occured while trying to load in XML file');
}
catch(Exception $e){
    print "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$content .= "\n<h1>" . $proc['name'] . "</h1>\n";
$content .= "<table border='1'><tr>\n";
$content .= "<th>Parameter</th>\n";
$content .= "<th>parametertype</th>\n";
$content .= "<th>incrementtype</th>\n";
$content .= "<th>incrementminimum</th>\n";
$content .= "<th>unit</th>\n";
$content .= "<th>datatype</th>\n";
$content .= "<th>requiredforupload</th>\n";
$content .= "<th>requiredfordataanalysis</th>\n";
$content .= "<th>derivedformula</th>\n";
$content .= "<th>imagemedia</th>\n";
$content .= "<th>annotation</th>\n";
$content .= "<th>graphtype</th>\n";
$content .= "<th>notes</th>\n";
$content .= "<th>qcmin</th>\n";
$content .= "<th>qcmax</th>\n";
$content .= "<th>qcnotes</th>\n";
$content .= "<th>increments</th>\n";
$content .= "<th>optionsvalue&#8209;description</th>\n";
$content .= "<th>ontologies</th>\n";
$content .= "</tr>\n";

foreach ($proc->parameter as $parameter) {
    $content .= "<tr>\n";
    foreach ($parameter->children() as $child) {
        if ($child->getName() == 'increments') {
            $content .= "<td>";
            foreach ($child->incrementvalue as $incval)
                $content .= '<span class="ioblock">' . (string) $incval . '</span>';
            $content .= "</td>\n";
        } else if ($child->getName() == 'options') {
            $content .= "<td>";
            foreach ($child->optionsgroup as $og) {
                $content .= '<span class="ioblock">' . (string) $og->optionsvalue . ' - ';
                $content .= (string) $og->optionsdescription . '</span>';
            }
            $content .= "</td>\n";
        } else if ($child->getName() == 'ontologies') {
            $content .= "<td>";
            foreach ($child->ontologyitem as $oi) {
                $content .= "\n<span class='oblock " . substr((string) $oi->selectionoutcome, 0, 1) . "'>\n";
                $content .= "Option: " . (string) $oi->option . "<br>\n";
                $content .= "Increment: " . (string) $oi->increment . "<br>\n";
                $content .= "Sex: " . (string) $oi->sex . "<br>\n";
                $content .= "Outcome:&nbsp;" . (string) $oi->selectionoutcome . "<br>\n";
                $content .= (string) $oi->mpid . " " . (string) $oi->mpterm . "\n";
                $content .= '</span>';
            }
            $content .= "&nbsp;</td>\n";
        } else {
            $content .= "<td>" . (string) $child . " &nbsp;</td>\n";
        }
    }
    $content .= "</tr>\n";
}

$content .= "</body></html>";

if (FALSE === $fh = fopen($htmlfileout, 'w')) {
    print "Failed to open $htmlfileout for writing. Is it currently in use?\n";
    exit(1);
}

try {
    fwrite($fh, $content);
    fclose($fh);
} catch (Exception $e) {
    print "Error: " . $e->getMessage() . "\n";
    exit(1);
}

print "Output written to file $htmlfileout\n";
exit(0);
