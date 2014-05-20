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
 * This class generates the example XML for data Submissions and validates it
 * against the XSD before it displays it. It displays XML error message if the
 * generated XML is invalid
 */
class ExampleProcedureXMLGeneratorModel extends CI_Model
{
    /**
     * @var string $xsd url location of xsd
     */
    public $xsd = 'http://www.mousephenotype.org/dcc/exportlibrary/datastructure/core/procedure/procedure_definition.xsd';
    /**
     * @var Procedure $procedure
     */
    protected $procedure;
    /**
     * @var Pipeline $pipeline
     */
    protected $pipeline;
    /**
     * @var XMLDocument $doc
     */
    protected $doc;
    /**
     * @var DOMElement $centre
     */
    protected $centre;
    /**
     * @var DOMElement $procedureNode The Procedure Node onto which Parameter leafs are added
     */
    protected $procedureNode;
    /**
     * Ontologies are stored in the structure:
     * 
     * <pre>
     * $ontologyCache = [
     *      OntologyGroupId => array(
     *          'MP' => array(
     *              'MP:1234567:foo',
     *              'MP:8901234:bar',
     *              ...
     *          ),
     *          'NO' => array(
     *              'PATO:12345:bin',
     *              'MPATH:6789:baz',
     *              ...
     *          )
     *      ),
     *      ...
     * ]
     * </pre>
     * 
     * @var array $ontologyCache
     */
    protected $ontologyCache = array();
    /**
     * @var string[] $parameterKeyCache A bunch of measured parameter Keys suitable for use with Series Media Parameters
     */
    protected $parameterKeyCache = array();

    /**
     * @param Procedure $procedure
     * @param Pipeline $pipeline
     */
    public function __construct(Procedure $procedure = null, Pipeline $pipeline = null)
    {
        //suppress stupid domdocument warnings
        libxml_use_internal_errors(true);
        
        if ($procedure)
            $this->procedure = $procedure;
        if ($pipeline)
            $this->pipeline = $pipeline;
    }
    
    /**
     * @param Procedure $procedure
     */
    public function setProcedure(Procedure $procedure)
    {
        $this->procedure = $procedure;
    }
    
    /**
     * @return Procedure
     */
    public function getProcedure()
    {
        return $this->procedure;
    }
    
    /**
     * @param Pipeline $pipeline
     */
    public function setPipeline(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }
    
    /**
     * @return Pipeline
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }
    
    /**
     * @param LibXMLError $error
     * @return string Error message
     */
    protected function libXMLDisplayError(LibXMLError $error)
    {
        if ($error->level == LIBXML_ERR_WARNING) {
            $s = "Warning $error->code: ";
        } else if ($error->level == LIBXML_ERR_ERROR) {
            $s = "Error $error->code: ";
        } else if (LIBXML_ERR_FATAL) {
            $s = "Fatal Error $error->code: ";
        }
        $s .= trim($error->message);
        if ($error->file) {
            $s .= " in $error->file";
        }
        $s .= " on line $error->line";

        return $s;
    }
    
    /**
     * @return string Generated XML text
     */
    public function generate()
    {
        $this->generateDocument();
        $xml = new DOMDocument();
        $xml->loadXML($this->doc->saveXML());
        if ( ! $xml->schemaValidate($this->xsd)) {
            $s = '<?xml version="1.0" encoding="utf-8"?' . '><errors>';
            foreach (libxml_get_errors() as $error) {
                $s .= '<error>' . $this->libXMLDisplayError($error) . '</error>' . PHP_EOL;
            }
            return $s . '</errors>';
        }
        return $xml->saveXML();
    }
    
    /**
     * Generates the whole document
     * A call is made to ExampleProcedureXMLGeneratorModel::generateLevel() to
     * create the level node and that in turn calls the method to create the body
     */
    protected function generateDocument()
    {
        $this->doc = new DomDocument('1.0', 'utf-8');
        $this->doc->formatOutput = true;
        $root = $this->doc->createElementNS('http://www.mousephenotype.org/dcc/exportlibrary/datastructure/core/procedure', 'centreProcedureSet');
        $this->doc->appendChild($root);
//        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.mousephenotype.org/dcc/exportlibrary/datastructure/core/procedure');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.mousephenotype.org/dcc/exportlibrary/datastructure/core/procedure ' . $this->xsd);
        $this->centre = $this->doc->createElement('centre');
        $this->centre->setAttribute('project', $this->getRandomProject());
        $this->centre->setAttribute('pipeline', $this->pipeline->getItemKey());
        $this->centre->setAttribute('centreID', $this->getRandomCentre());
        $root->appendChild($this->centre);
        $this->generateLevel();
    }
    
    /**
     * Generates the body (meaty filling) of the document, i.e. children of the
     * level node in a predefined sequence of Parameter types
     * @see ExampleProcedureXMLGeneratorModel::generateLevel()
     */
    protected function generateBody()
    {
        $paramTypeOrder = array(
            EParamType::SIMPLE,
            EParamType::ONTOLOGY,
            EParamType::SERIES,
            EParamType::MEDIA,
            EParamType::MEDIA_SAMPLE,
            EParamType::SERIES_MEDIA,
            EParamType::METADATA
        );
        
        foreach ($paramTypeOrder as $type) {
            foreach ($this->procedure->getParameters() as $param) {
                //derived parameters need not be submitted
                if ($param->getType() != $type || $param->isDerived())
                    continue;

                $parameter = $this->doc->createElement($param->getType());
                $parameter->setAttribute('parameterID', $param->getItemKey());

                //simple and metadata parameters
                if ($param->getType() == EParamType::SIMPLE || $param->getType() == EParamType::METADATA)
                {
                    $this->generateSimpleParameter($param, $parameter);
                }

                //media parameter
                else if ($param->getType() == EParamType::MEDIA)
                {
                    $this->generateMediaParameter($parameter);
                }

                //media sample parameter
                else if ($param->getType() == EParamType::MEDIA_SAMPLE)
                {
                    $this->generateMediaSampleParameter($parameter);
                }

                //series media parameter
                else if ($param->getType() == EParamType::SERIES_MEDIA)
                {
                    $this->generateSeriesMediaParameter($parameter);
                }

                //ontology parameter
                else if ($param->getType() == EParamType::ONTOLOGY)
                {
                    $this->generateOntologyParameter($param, $parameter);
                }

                //series parameter
                else if ($param->getType() == EParamType::SERIES)
                {
                    $increments = $param->getIncrements();

                    //in a number of ABR parameters, the parameter type is
                    //seriesParameter but the increments have yet to be defined
                    if (count($increments) == 0)
                        continue;

                    foreach ($increments as $increment) {
                        //simple repeat increment
                        if (strlen($increment->getIncrementString()) == 0 && $increment->getIncrementMin() >= 0)
                        {
                            $this->generateSimpleRepeatIncrementalParameter($increment, $param, $parameter);
                        }
                        //defined increment
                        else
                        {
                            $this->generateDefinedIncrementalParameter($increment, $param, $parameter);
                        }
                    }

                    if ($param->getUnit())
                        $parameter->setAttribute('unit', $param->getUnit());
                }

                $this->procedureNode->appendChild($parameter);
            }
        }
    }
    
    /**
     * The Body Weight Procedure is a special case and multiple level-nodes are
     * generated with sequenceIDs and an 7 day interval between each experiment
     * @see ExampleProcedureXMLGeneratorModel::generateLevel()
     */
    protected function generateBodyWeightProcedure()
    {
        $dateOfExperiment = new DateTime('7 days ago');
        $specimenID = $this->getRandomSpecimen();
        
        for ($count = 1, $limit = rand(2, 7) + 1; $count < $limit; $count++) {
            $experiment = $this->doc->createElement(EProcedureLevel::EXPERIMENT);
            $dateOfExperiment->add(new DateInterval('P7D'));
            $experiment->setAttribute('dateOfExperiment', $dateOfExperiment->format('Y-m-d'));
            $experiment->setAttribute('experimentID', $this->getRandomExperimentId());
            $experiment->setAttribute('sequenceID', $count);
            $this->centre->appendChild($experiment);
            $experiment->appendChild($this->doc->createElement('specimenID', $specimenID));
            $this->generateProcedureNode($experiment);
            $this->generateBody();
        }
    }

    /**
     * Generates the level node (experiment/line/housing) and makes the call to
     * generate the body of the document
     * @see ExampleProcedureXMLGeneratorModel::generateBody()
     * @see ExampleProcedureXMLGeneratorModel::generateBodyWeightProcedure()
     * @return void
     */
    protected function generateLevel()
    {
        $procedureLevel = $this->procedure->getLevel();
        $level = $this->doc->createElement($procedureLevel);
        if ($procedureLevel == EProcedureLevel::EXPERIMENT)
        {
            //handle Body Weight seperately
            if ($this->procedure->getId() == 103) {
                $this->generateBodyWeightProcedure();
                return;
            } else {
                $level->setAttribute('dateOfExperiment', date('Y-m-d'));
                $level->setAttribute('experimentID', uniqid());
                $this->centre->appendChild($level);
                $specimen = $this->doc->createElement('specimenID', $this->getRandomSpecimen());
                $level->appendChild($specimen);
            }
        }
        else if ($procedureLevel == EProcedureLevel::HOUSING)
        {
            $level->setAttribute('fromLIMS', $this->getRandomValue(EParamValueType::BOOL));
            $this->centre->appendChild($level);
        }
        else if ($procedureLevel == EProcedureLevel::LINE)
        {
            $level->setAttribute('colonyID', uniqid());
            $this->centre->appendChild($level);
        }
        
        $this->generateProcedureNode($level);
        
        $this->generateBody();
    }
    
    /**
     * @param DOMElement $level
     */
    protected function generateProcedureNode(DOMElement &$level)
    {
        $this->procedureNode = $this->doc->createElement('procedure');
        $this->procedureNode->setAttribute('procedureID', $this->procedure->getItemKey());
        $level->appendChild($this->procedureNode);
    }
    
    /**
     * @param Parameter $param
     * @param DOMElement $parent
     */
    protected function generateSimpleParameter(Parameter $param, DOMElement &$parent)
    {
        if ($param->isOption()) {
            $v = $this->getRandomOption($param);
        } else {
            $v = $this->getRandomValue($param->getValueType(), $param->getQCMin(), $param->getQCMax());
        }
        $value = $this->doc->createElement('value', $v);
        if ($param->getUnit() && $param->getType() != EParamType::METADATA) {
            $parent->setAttribute('unit', $param->getUnit());
        }
        $parent->appendChild($value);
    }
    
    /**
     * @param DOMElement $parent
     */
    protected function generateMediaParameter(DOMElement &$parent)
    {
        $imageURI = $this->getRandomValue(EParamValueType::IMAGE);
        $parent->setAttribute('URI', $imageURI);
        $parent->setAttribute('fileType', $this->getImageType($imageURI));
    }
    
    /**
     * @param DOMElement $parent
     */
    protected function generateMediaSampleParameter(DOMElement &$parent)
    {
        for ($i = 0, $limit = rand(1, 3); $i < $limit; $i++) {
            $mediaSample = $this->doc->createElement('mediaSample');
            $mediaSample->setAttribute('localId', 'sample' . $i);
            $parent->appendChild($mediaSample);
            for ($j = 0, $mimit = rand(1, 5); $j < $mimit; $j++) {
                $mediaSection = $this->doc->createElement('mediaSection');
                $mediaSection->setAttribute('localId', 'section' . $j);
                $mediaSample->appendChild($mediaSection);
                $mediaFile = $this->doc->createElement('mediaFile');
                $mediaFile->setAttribute('localId', 'img' . $i . '.' . $j);
                $imageURI = $this->getRandomValue(EParamValueType::IMAGE);
                $mediaFile->setAttribute('URI', $imageURI);
                $mediaFile->setAttribute('fileType', $this->getImageType($imageURI));
                $mediaSection->appendChild($mediaFile);
            }
        }
    }

    /**
     * @param DOMElement $parent
     */
    protected function generateSeriesMediaParameter(DOMElement &$parent)
    {
        foreach (range(1, 3) as $inc) {
            $value = $this->doc->createElement('value');
            $value->setAttribute('incrementValue', $inc);
            $imageURI = $this->getRandomValue(EParamValueType::IMAGE);
            $value->setAttribute('URI', $imageURI);
            $value->setAttribute('fileType', $this->getImageType($imageURI));
            $parent->appendChild($value);
            $this->generateSeriesMediaParameterAssociations($value);
        }
    }
    
    /**
     * If the seriesMediaParameter is an Adult LacZ type of Procedure it will add
     * child branches to the parent value (image) node of specific image info
     * @param DOMElement $parent
     */
    protected function generateSeriesMediaParameterAssociations(DOMElement &$parent)
    {
        if ($this->procedure->getType()->getKey() == 'ALZ') {
            $defaultValues['x'] = 50;
            $defaultValues['y'] = 50;
            $defaultValues['z'] = 50;
            $defaultValues['width']  = 500;
            $defaultValues['height'] = 500;
            $useZDim = false; //(bool) rand(0, 1);
            for ($paID = 1, $stop = rand(2, 4); $paID < $stop; $paID++) {
                $paramAssoc = $this->doc->createElement('parameterAssociation');
                $paramAssoc->setAttribute('parameterID', $this->getRandomParameterKeyForSeriesMediaParameter());
                $parent->appendChild($paramAssoc);
                foreach (array_keys($defaultValues) as $d) {
                    if ($d == 'z' && $useZDim === false)
                        continue;
                    $dim = $this->doc->createElement('dim', $defaultValues[$d]);
                    $dim->setAttribute('id', $d);
                    $dim->setAttribute('origin', 'topLeft');
                    $dim->setAttribute('unit', 'px');
                    $paramAssoc->appendChild($dim);
                }
            }
        }
    }

    /**
     * @return string A random Simple Parameter key like IMPC_ALZ_001_001 from
     * the parameters present in the current Procedure.
     * This method uses the $parameterKeyCache class variable
     * @see ExampleProcedureXMLGeneratorModel::generateSeriesMediaParameterAssociations()
     */
    protected function getRandomParameterKeyForSeriesMediaParameter()
    {
        if (empty($this->parameterKeyCache)) {
            foreach ($this->procedure->getParameters() as $param) {
                if ($param->getType() == EParamType::SIMPLE) {
                    $this->parameterKeyCache[] = $param->getItemKey();
                }
            }
        }
        
        return $this->parameterKeyCache[array_rand($this->parameterKeyCache)];
    }

    /**
     * This method extracts all the ontology options for the current Parameter
     * and splits them up into MP Terms and NOn-MP Terms. It then randomly picks
     * at least one MP Term and up to 3 Non-MP Terms to display.
     * This method uses the $ontologyCache class variable
     * @param Parameter $param
     * @param DOMElement $parent
     */
    protected function generateOntologyParameter(Parameter $param, DOMElement &$parent)
    {
        $ontologies = array('MP' => array(), 'NO' => array());
        
        foreach ($param->getOntologyGroups() as $group)
        {
            if ( ! isset($this->ontologyCache[$group->getId()])) {
                $this->ontologyCache[$group->getId()]['MP'] = array();
                $this->ontologyCache[$group->getId()]['NO'] = array();
                foreach ($group->getOntologyOptions() as $option) {
                    $val = $option->getOntologyId() . ':' . $option->getOntologyTerm();
                    $key = (0 === strripos($option->getOntologyId(), 'MP')) ? 'MP' : 'NO';
                    $this->ontologyCache[$group->getId()][$key][] = $val;
                }
            }
            
            $ontologies['MP'] = array_merge($ontologies['MP'], $this->ontologyCache[$group->getId()]['MP']);
            $ontologies['NO'] = array_merge($ontologies['NO'], $this->ontologyCache[$group->getId()]['NO']);
        }
        
        $ontologies['MP'] = array_unique($ontologies['MP']);
        $ontologies['NO'] = array_unique($ontologies['NO']);
        
        $chosenOntologies = array();
        if ( ! empty($ontologies['MP']))
            $chosenOntologies[] = $ontologies['MP'][array_rand($ontologies['MP'])];
        if ( ! empty($ontologies['NO'])) {
            $num = (count($ontologies['NO']) >= 3) ? 3 : count($ontologies['NO']);
            foreach (array_rand($ontologies['NO'], $num) as $i) {
                $chosenOntologies[] = $ontologies['NO'][$i];
            }
        }
        
        foreach ($chosenOntologies as $ont) {
            $term = $this->doc->createElement('term', $ont);
            $parent->appendChild($term);
        }
    }
    
    /**
     * It looks at the Increment of the Parameter and determines the type of the
     * incrementValue attribute. If the attribute is of datetime unit then it
     * looks at the unit and if it finds /h (per hour) it increments the date by
     * 1 hour at a time. Otherwise, it increments the date in 5 second intervals
     * @param ParamIncrement $increment
     * @param Parameter $param
     * @param DOMElement $parent
     */
    protected function generateSimpleRepeatIncrementalParameter(ParamIncrement $increment, Parameter $param, DOMElement &$parent)
    {
        $d = new DateTime();
        $i = 0;
        while ($i < $increment->getIncrementMin()) {
            $value = null;
            if ($param->isOption()) {
                $value = $this->doc->createElement('value', $this->getRandomOption($param));
            } else {
                $value = $this->doc->createElement(
                    'value',
                    $this->getRandomValue($param->getValueType(), $param->getQCMin(), $param->getQCMax())
                );
            }
            if ($increment->getIncrementType() == EIncrementType::DATETIME) {
                if (preg_match('/\/[hH]/', $param->getUnit())) {
                    //1 hour between repeats
                    $d->add(new DateInterval('PT1H'));
                } else if ($increment->getIncrementUnit() == EIncrementUnit::MINUTES) {
                    $d->add(new DateInterval('PT1M'));
                } else if ($increment->getIncrementUnit() == EIncrementUnit::SECONDS) {
                    $d->add(new DateInterval('PT1S'));
                } else {
                    //default is 5 seconds between repeats
                    $d->add(new DateInterval('PT5S'));
                }
                $value->setAttribute('incrementValue', $d->format(DateTime::W3C));
            } else {
                $value->setAttribute('incrementValue', $i);
            }
            $parent->appendChild($value);
            $i++;
        }
    }
    
    /**
     * @param ParamIncrement $increment
     * @param Parameter $param
     * @param DOMElement $parent
     */
    protected function generateDefinedIncrementalParameter(ParamIncrement $increment, Parameter $param, DOMElement &$parent)
    {
        if ($param->isOption()) {
            $v = $this->getRandomOption($param);
        } else {
            $v = $this->getRandomValue($param->getValueType(), $param->getQCMin(), $param->getQCMax());
        }
        $value = $this->doc->createElement('value', $v);
        $value->setAttribute('incrementValue', $increment->getIncrementString());
        $parent->appendChild($value);
    }

    /**
     * @return string ILARCode
     */
    protected function getRandomCentre()
    {
        $centres = array(
            'J',    'Ucd',  'Krb',
            'Bcm',  'Wtsi', 'Ics',
            'Tcp',  'Rbrc', 'Ning',
            'H',    'Gmc'
        );
        return $centres[array_rand($centres)];
    }
    
    /**
     * @return string e.g. BaSH
     */
    protected function getRandomProject()
    {
        $projects = array(
            'BaSH',             'DTCC',             'DTCC-Legacy',
            'EUCOMM-EUMODIC',   'EUCOMMToolsCre',   'Helmholtz GMC',
            'Infrafrontier-S3', 'JAX',              'KMPC',
            'MARC',             'MGP',              'MGP Legacy',
            'MGP-KOMP',         'MRC',              'Monterotondo',
            'NorCOMM2',         'Phenomin',         'Monterotondo R&amp;D',
            'RIKEN BRC',        'UCD-KOMP'
        );
        return $projects[array_rand($projects)];
    }

    /**
     * @return string mouse_1243cjkdf34
     */
    protected function getRandomSpecimen()
    {
        return 'mouse_' . rand(1, 10000);
    }
    
    /**
     * @return string gif, png or tiff currently
     */
    protected function getRandomImageSuffix()
    {
        $suffixes = array('gif', 'png', 'tiff'); //'jpg', 'bmp', 'mov', 'mpg', 'avi'
        return $suffixes[array_rand($suffixes)];
    }
    
    /**
     * @return string experiment_data123kjvH0943
     */
    protected function getRandomExperimentId()
    {
        $robots = array('r2d2', 'c3po', 'hal', 't800', 'data');
        return 'experiment_' . $robots[array_rand($robots)] . uniqid();
    }

    /**
     * @param string $imageURI protocol://foo.bar/image.jpg or file name
     * @return string mime type
     */
    protected function getImageType($imageURI)
    {
        $suffix = substr($imageURI, strrpos($imageURI, '.', -1) + 1);
        switch ($suffix) {
            case 'jpg':
            case 'bmp':
            case 'gif':
            case 'png':
                return 'image/' . $suffix;
                break;
            case 'tiff':
            case 'tif':
                return 'image/tiff';
                break;
            case 'avi':
                return 'video/x-msvideo';
                break;
            case 'flv':
                return 'video/x-flv';
                break;
            case 'mov':
                return 'video/quicktime';
                break;
            case 'ogg':
                return 'video/ogg';
                break;
            case 'mpeg':
            case 'mpg':
                return 'video/mpeg';
                break;
            default:
                return 'type/container';
        }
    }

    /**
     * Chooses a random option from the ones present for the Parameter. For some
     * Parameters this method returns a hard-wired choice as decided by a data
     * wrangler for their procedure beause a random choice may confuse people
     * @param Parameter $param
     * @return string option string
     */
    protected function getRandomOption(Parameter $param)
    {
        $options = ($param->isOption()) ? $param->getOptions() : array();
        //for some parameters return the first option - not random
        $parametersToReturnFirstOptionFor = array(
            'IMPC_FER_001_001', //Gross Findings Male
            'IMPC_FER_019_001'  //Gross Findings Female
        );
        if (in_array($param->getItemKey(), $parametersToReturnFirstOptionFor)) {
            return (empty($options)) ? '' : e(current($options)->getName());
        }
        return (empty($options)) ? '' : e($options[array_rand($options)]->getName());
    }

    /**
     * Returns a value of a specific Parameter Type within the permitted range
     * if given. If no range is present it has some sensible global defaults
     * @param string $type Parameter Value Type EParameterValueType
     * @param int $min
     * @param int $max
     * @return mixed
     */
    protected function getRandomValue($type, $min = null, $max = null)
    {
        if ( ! is_null($min) && strlen($min) > 0 && $min === $max && 
            ($type == EParamValueType::FLOAT || $type == EParamValueType::INT)
        ) {
            return $min;
        }
        $min = (is_null($min) || strlen($min) == 0) ? 0   : $min;
        $max = (is_null($max) || strlen($max) == 0) ? 100 : $max;
        if ($min > $max) {
            $max = $min + 100;
        }
        
        if ($type == EParamValueType::BOOL)
        {
            return (rand(0, 1)) ? 'true' : 'false';
        }
        else if ($type == EParamValueType::DATE)
        {
            return date('Y-m-d');
        }
        else if ($type == EParamValueType::DATETIME)
        {
            return date(DateTime::W3C);
        }
        else if ($type == EParamValueType::FLOAT)
        {
            return number_format((mt_rand()/mt_getrandmax()) * ($max - $min) + $min, 2, '.', '');
        }
        else if ($type == EParamValueType::IMAGE)
        {
            return 'protocol://accessible.path.to/images/img_' . uniqid() . '.' . $this->getRandomImageSuffix();
        }
        else if ($type == EParamValueType::INT)
        {
            return (int)rand($min, $max);
        }
        else if ($type == EParamValueType::TEXT)
        {
            return 'Text';
        }
        else if ($type == EParamValueType::TIME)
        {
            return date('H:i:s');
        }
    }
}
