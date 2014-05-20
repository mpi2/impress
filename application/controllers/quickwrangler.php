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
 * A class to make the data wranglers lives a little easier
 */
class QuickWrangler extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        
        //update config from database
        $this->load->model('overridesettingsmodel');
        $this->overridesettingsmodel->updateRunningConfig();
        
        //only allow running on internal server
        if ($this->config->item('server') != 'internal')
            die('Access Denied. QuickWrangler can only be run internally.');
    }
    
    public function insertEmbryoLacZOptions()
    {
        die('This process has already been run.');
        
        $pipelineId = 7;
        $procedureId = 172;
        $procedure = new Procedure($procedureId, $pipelineId);
        $parameters = $procedure->getParameters();
        
        $input = array(
            'param_option_id' => null,
            'parameter_id'    => null, /*filled in below*/ 
            'name'            => null, /*filled in below*/ 
            'parent_id'       => null,
            'is_default'      => 0,
            'is_active'       => 1,
            'description'     => null,
            'time_modified'   => $this->config->item('timestamp'),
            'user_id'         => 1,
            'pipeline_id'     => $pipelineId,
            'procedure_id'    => $procedureId,
            'nvoption_relation'     => null,
            'nvrelation'            => null,
            'nvpipeline'            => null,
            'nvprocedure'           => null,
            'nvforkprocedure'       => 0,
            'nvuseoldpipelinekey'   => 0,
            'nvrelationdescription' => null,
            'nvoption_relationdescription' => null
        );
        
        $optionTitles = array(
            'expression',
            'no expression',
            'ambiguous',
            'imageOnly',
            'tissue not available'
        );
        
        $this->load->model('paramoptionmodel');
        
        foreach ($parameters as $parameter) {
            $currentParameterOptions = $parameter->getOptions();
            if (count($currentParameterOptions) > 0 || $parameter->getType() == EParamType::METADATA)
                continue;
            foreach ($optionTitles as $optionTitle) {
                $newOption = $input;
                $newOption['name'] = $optionTitle;
                $newOption['parameter_id'] = $parameter->getId();
                if ( ! $this->paramoptionmodel->insert($newOption)) {
                    $errorMessage = 'An error occured while trying to insert an option into Parameter ' . $parameter->getId();
                    echo $errorMessage;
                    log_message('error', $errorMessage);
                    return false;
                }
                echo "Parameter " . $parameter->getItemName() . ", added Option {$optionTitle}<br>\n";
            }
        }
        
        echo "<p>Done</p>\n";
    }
    
    public function createHistopathologyParameters()
    {
        die('This process has already been run.');
        
        //load up IMPC Gross Pathology parameters
        $pipelineId = 7;
        $gpProcedureId = 99;
        $newHpProcedureId = 102;
        $gpProcedure = new Procedure($gpProcedureId, $pipelineId);
        $newHpProcedure = new Procedure($newHpProcedureId, $pipelineId);
        
        //create equivalent parameters for tissue collection
        $postfixes = array(
            array('text' => 'MPATH diagnostic term',     'type' => EParamType::ONTOLOGY),
            array('text' => 'Free text diagnostic term', 'type' => EParamType::SIMPLE),
            array('text' => 'MPATH process term',        'type' => EParamType::ONTOLOGY),
            array('text' => 'Severity score',            'type' => EParamType::SIMPLE),
            array('text' => 'Descriptor PATO',           'type' => EParamType::ONTOLOGY),
            array('text' => 'Description',               'type' => EParamType::SIMPLE)
        );
        
        $defaultParameter = array(
            'pipeline_id'   => $pipelineId,
            'procedure_id'  => $newHpProcedureId,
            'parameter_id'  => null,
            'parameter_key' => null,
            'type'          => null, //will be set for each row
            'name'          => null, //will be set for each row
            'visible'       => 1,
            'active'        => 1,
            'internal'      => 0,
            'deprecated'    => 0,
            'major_version' => 1,
            'minor_version' => 0,
            'description'   => null,
            'time_modified' => $this->config->item('timestamp'),
            'user_id'       => User::getId(),
            'value_type'    => EParamValueType::TEXT,
            'unit'          => null,
            'derivation'    => null,
            'data_analysis_notes' => null,
            'graph_type'    => null, //will be set for each row?
            'qc_min'        => null,
            'qc_max'        => null,
            'qc_notes'      => null,
            'qc_check'      => 0,
            'is_derived'    => 0,
            'is_increment'  => 0,
            'is_option'     => 0,
            'is_required'   => null, //will be set for each row
            'is_important'  => 0,
            'is_annotation' => null, //will be set for each row
            //now the fields only needed with creating a new version
            'nvrelation'            => null,
            'nvrelationdescription' => null,
            'nvpipeline'            => null,
            'nvprocedure'           => null,
            'nvforkprocedure'       => 0,
            'nvuseoldpipelinekey'   => 0,
        );
        
        
        //check the new hp procedure parameters haven't been generated yet and if it hasn't then create them
        $hpParameters = $newHpProcedure->getParameters();
        
        if (empty($hpParameters)) {

            //loop through each of gross pathology procedures to create histopathology procedures
            foreach ($gpProcedure->getParameters() as $parameter)
            {
                if ($parameter->getType() != EParamType::ONTOLOGY)
                    continue;

                foreach ($postfixes as $postfix)
                {
                    $myParam = $defaultParameter;
                    $myParam['type'] = $postfix['type'];
                    $myParam['name'] = trim($parameter->getItemName() . ' - ' . $postfix['text'], ' -');
                    $myParam['graph_type'] = $parameter->getGraphType();
                    $myParam['is_required'] = ($parameter->isRequired()) ? 1 : 0;
                    $myParam['is_annotation'] = ($parameter->isAnnotation()) ? 1 : 0;
                    if ( ! $this->parametermodel->insert($myParam))
                        die('Something went wrong when trying to insert ' . print_r($myParam, true));
                    else
                        echo 'Created Parameter: ' . $myParam['name'] . '<br>' . PHP_EOL;
                }
            }
        }
        
        echo '<p>Done</p>';
    }
    
    
    public function createHistopathologyOntologyGroups()
    {
        die('This process has already been run.');
        
        $pipelineId = 7;
        $procedureId = 102;
        $procedure = new Procedure($procedureId, $pipelineId);
        $ontologyParameters = array_filter($procedure->getParameters(), function($param) {
            return ($param->getType() == EParamType::ONTOLOGY);
        });
        
        //Define Groups and defaults
        $groups = array(
            array(
                'name'   => 'Histopathology MPATH Diagnosis Terms',
                'method' => 'getHistMPATHDiagnosisTerms'
            ),
            array(
                'name'   => 'Histopathology MPATH Process Terms',
                'method' => 'getHistMPATHProcessTerms'
            ),
            array(
                'name'   => 'Histopathology PATO Terms',
                'method' => 'getHistPATOTerms'
            )
        );
        
        $this->load->model('ontologygroupmodel');
        $this->load->model('paramontologyoptionmodel');
        $this->load->model('parameterhasontologygroupsmodel');
        
        $defaultGroup = array(
            'ontology_group_id' => null,
            'pipeline_id'       => $pipelineId,
            'procedure_id'      => $procedureId,
            'parameter_id'      => current($ontologyParameters)->getId(),
            'name'              => '' //to be set below
        );
        $defaultTerm = array(
            'param_ontologyoption_id' => null,
            'ontology_term'     => '', //to be set below
            'ontology_id'       => '', //to be set below
            'ontology_group_id' => '', //to be set below
            'is_default'        => 0,
            'is_active'         => 1,
            'is_collapsed'      => 1,
            'pipeline_id'       => $pipelineId,
            'procedure_id'      => $procedureId,
            'parameter_id'      => current($ontologyParameters)->getId()
        );
        
        //create the groups if they don't exist and set their id        
        foreach ($groups as $id => $group)
        {
            //check to see if the group hasn't already been created by checking it's name
            $groupFound = $this->ontologygroupmodel->getByName($group['name']);
            
            //create group
            if (empty($groupFound))
            {
                $myGroup = $defaultGroup;
                $myGroup['name'] = $group['name'];
                $nid = $this->ontologygroupmodel->insert($myGroup);
                if ( ! $nid) {
                    die('An error occured while trying to create the group ' . print_r($myGroup, true));
                } else {
                    $groups[$id]['id'] = $nid;
                    echo 'Created new group ' . $myGroup['name'] . '<br>' . PHP_EOL;
                }
            }
            else
            {
                $groups[$id]['id'] = $groupFound[OntologyGroupModel::PRIMARY_KEY];
            }
        }
        
        //add the ontology options for each group
        foreach ($groups as $group)
        {
            $optionsInGroup = $this->paramontologyoptionmodel->getByOntologyGroup($group['id']);
            
            //check the group hasn't already been filled in with ontologies
            if (empty($optionsInGroup)) {
                foreach ($this->{$group['method']}() as $groupTerms) {
                    foreach ($groupTerms as $ontId => $ontTerm) {
                        $myTerm = $defaultTerm;
                        $myTerm['ontology_id'] = $ontId;
                        $myTerm['ontology_term'] = $ontTerm;
                        $myTerm['ontology_group_id'] = $group['id'];
                        if ( ! $this->paramontologyoptionmodel->insert($myTerm)) {
                            die('An error occured while trying to insert the ontology ' . print_r($myTerm, true));
                        } else {
                            echo 'Created option ' . $myTerm['ontology_id'] . ' ' . $myTerm['ontology_term']
                               . ' in group ' . $myTerm['ontology_group_id'] . '<br>' . PHP_EOL;
                        }
                    }
                }
            }
        }
        
        //add the ontology groups to their respective parameters        
        foreach ($ontologyParameters as $parameter)
        {
            $pho = $this->parameterhasontologygroupsmodel->getByParameter($parameter->getId());
            $existingGroups = array_map(function($g){return $g[OntologyGroupModel::PRIMARY_KEY];}, $pho);
            
            
            if (stristr($parameter->getItemName(), 'MPATH diagnostic term') &&
                 ! in_array($groups[0]['id'], $existingGroups)
            ) {
                if ($this->parameterhasontologygroupsmodel->insert($parameter->getId(), $groups[0]['id'])) {
                    echo 'Added Group ' . $groups[0]['name'] . ' to Parameter '
                       . $parameter->getItemKey() . '<br>' . PHP_EOL;
                } else {
                    die('An error occured while trying to add group ' . $groups[0]['name']
                      . ' to Parameter ' . $parameter->getItemKey() . '<br>' . PHP_EOL);
                }
            }
            else if (stristr($parameter->getItemName(), 'MPATH process term') &&
                      ! in_array($groups[1]['id'], $existingGroups)
            ) {
                if ($this->parameterhasontologygroupsmodel->insert($parameter->getId(), $groups[1]['id'])) {
                    echo 'Added Group ' . $groups[1]['name'] . ' to Parameter '
                       . $parameter->getItemKey() . '<br>' . PHP_EOL;
                } else {
                    die('An error occured while trying to add group ' . $groups[1]['name']
                      . ' to Parameter ' . $parameter->getItemKey() . '<br>' . PHP_EOL);
                }
            }
            else if (stristr($parameter->getItemName(), 'Descriptor PATO') &&
                      ! in_array($groups[2]['id'], $existingGroups)
            ) {
                if ($this->parameterhasontologygroupsmodel->insert($parameter->getId(), $groups[2]['id'])) {
                    echo 'Added Group ' . $groups[2]['name'] . ' to Parameter '
                       . $parameter->getItemKey() . '<br>' . PHP_EOL;
                } else {
                    die('An error occured while trying to add group ' . $groups[2]['name']
                      . ' to Parameter ' . $parameter->getItemKey() . '<br>' . PHP_EOL);
                }
            }
        }
        
        echo '<p>Done</p>';
    }
    
    private function getHistMPATHProcessTerms()
    {
        return array(
            array('MPATH:458' => 'normal'),
            array('MPATH:1' => 'cell and tissue damage'),
            array('MPATH:2' => 'cell death'),
            array('MPATH:4' => 'necrosis'),
            array('MPATH:14' => 'degenerative change'),
            array('MPATH:16' => 'myxoid/myxomatous degeneration'),
            array('MPATH:25' => 'tissue specific degenerative process'),
            array('MPATH:33' => 'intracellular and extracellular accumulation'),
            array('MPATH:34' => 'amyloid deposition'),
            array('MPATH:36' => 'calcium deposition'),
            array('MPATH:37' => 'ceroid deposition'),
            array('MPATH:38' => 'copper deposition'),
            array('MPATH:39' => 'glycogen deposition'),
            array('MPATH:40' => 'hyalinosis'),
            array('MPATH:41' => 'iron deposition'),
            array('MPATH:42' => 'lipid deposition'),
            array('MPATH:43' => 'lipofuscin deposition'),
            array('MPATH:44' => 'melanin deposition'),
            array('MPATH:45' => 'protein deposition'),
            array('MPATH:46' => 'uric acid deposition'),
            array('MPATH:47' => 'intracellular and extracellular depletion'),
            array('MPATH:48' => 'decalcification'),
            array('MPATH:49' => 'demyelination'),
            array('MPATH:50' => 'glycogen depletion'),
            array('MPATH:51' => 'hypocalcification'),
            array('MPATH:52' => 'lipid depletion'),
            array('MPATH:55' => 'developmental and structural abnormality'),
            array('MPATH:57' => 'agenesis'),
            array('MPATH:58' => 'aplasia'),
            array('MPATH:59' => 'branching morphogenesis defect'),
            array('MPATH:60' => 'communication defect'),
            array('MPATH:61' => 'curvature defect'),
            array('MPATH:63' => 'depletion '),
            array('MPATH:64' => 'dysplasia'),
            array('MPATH:66' => 'dilatation'),
            array('MPATH:67' => 'displacement and deformity'),
            array('MPATH:71' => 'fusion defect'),
            array('MPATH:72' => 'growth acceleration'),
            array('MPATH:73' => 'growth arrest'),
            array('MPATH:76' => 'heterotopia'),
            array('MPATH:77' => 'homoeotic change'),
            array('MPATH:78' => 'incomplete closure'),
            array('MPATH:79' => 'malposition'),
            array('MPATH:80' => 'obstruction and stenosis'),
            array('MPATH:81' => 'perforation'),
            array('MPATH:82' => 'persistent embryonic structure'),
            array('MPATH:83' => 'septation defect'),
            array('MPATH:84' => 'supernumerary structure'),
            array('MPATH:85' => 'transdifferentiation'),
            array('MPATH:86' => 'organ specific developmental defect'),
            array('MPATH:87' => 'germ cell defect'),
            array('MPATH:89' => 'cardiovascular developmental defect'),
            array('MPATH:105' => 'circulation disorder'),
            array('MPATH:106' => 'fluid accumulation'),
            array('MPATH:107' => 'congestion'),
            array('MPATH:108' => 'disseminated intravascular coagulation'),
            array('MPATH:119' => 'hemorrhage and non-specified extravasation'),
            array('MPATH:120' => 'ecchymosis'),
            array('MPATH:122' => 'petechia'),
            array('MPATH:124' => 'infarction'),
            array('MPATH:125' => 'thrombosis'),
            array('MPATH:126' => 'growth and differentiation defect'),
            array('MPATH:127' => 'atrophy'),
            array('MPATH:133' => 'hypoplasia'),
            array('MPATH:134' => 'hyperplasia'),
            array('MPATH:159' => 'hypertrophy'),
            array('MPATH:160' => 'metaplasia'),
            array('MPATH:175' => 'healing and repair'),
            array('MPATH:176' => 'connective tissue replacement'),
            array('MPATH:177' => 'angiogenesis'),
            array('MPATH:178' => 'extracellular matrix deposition'),
            array('MPATH:179' => 'fibrin deposition'),
            array('MPATH:180' => 'fibroblast proliferation'),
            array('MPATH:181' => 'fibrosis'),
            array('MPATH:182' => 'gliosis'),
            array('MPATH:184' => 'sclerosis'),
            array('MPATH:185' => 'parenchymal regeneration'),
            array('MPATH:186' => 'complete parenchymal regeneration'),
            array('MPATH:187' => 'incomplete parenchymal regeneration'),
            array('MPATH:194' => 'immune mediated disease'),
            array('MPATH:212' => 'inflammation'),
            array('MPATH:213' => 'acute inflammation'),
            array('MPATH:214' => 'chronic inflammation'),
            array('MPATH:215' => 'granulomatous inflammation'),
            array('MPATH:216' => 'necrotic inflammation'),
            array('MPATH:217' => 'ulcerative inflammation'),
            array('MPATH:218' => 'neoplasia'),
            array('MPATH:469' => 'symmetry defect'),
            array('MPATH:470' => 'left/right axis defect'),
            array('MPATH:471' => 'anterior/posterior axis defect'),
            array('MPATH:472' => 'dorsal/ventral axis defect'),
            array('MPATH:473' => 'developmental cystic dysplasia'),
            array('MPATH:474' => 'ectasia'),
            array('MPATH:475' => 'ductal ectasia'),
            array('MPATH:478' => 'angiogenesis defect'),
            array('MPATH:479' => 'vasculogenesis defect'),
            array('MPATH:480' => 'decidual defect'),
            array('MPATH:547' => 'mucous secretions'),
            array('MPATH:554' => 'dystrophy'),
            array('MPATH:555' => 'mineralisation'),
            array('MPATH:580' => 'erosion'),
            array('MPATH:591' => 'fatty infiltration'),
            array('MPATH:597' => 'cell and tissue damage process'),
            array('MPATH:600' => 'branching defect'),
            array('MPATH:601' => 'developmental hypertrophy'),
            array('MPATH:604' => 'defective growth and differentiation process'),
            array('MPATH:607' => 'healing and repair structure'),
            array('MPATH:613' => 'pigmentation'),
            array('MPATH:614' => 'concretion'),
            array('MPATH:624' => 'involution'),
            array('MPATH:625' => 'avulsion'),
            array('MPATH:640' => 'osteoid deposition')
        );
    }
    
    private function getHistMPATHDiagnosisTerms()
    {
        return array(
            array('MPATH:458' => 'normal'),
            array('MPATH:5' => 'bridging necrosis'),
            array('MPATH:6' => 'caseous necrosis'),
            array('MPATH:7' => 'coagulation necrosis'),
            array('MPATH:8' => 'fat necrosis'),
            array('MPATH:9' => 'fibrinoid necrosis'),
            array('MPATH:10' => 'gangrene'),
            array('MPATH:11' => 'hemorrhagic necrosis'),
            array('MPATH:12' => 'liquefactive necrosis'),
            array('MPATH:13' => 'piecemeal necrosis'),
            array('MPATH:16' => 'myxoid/myxomatous degeneration'),
            array('MPATH:26' => 'alopecia'),
            array('MPATH:27' => 'arthrosis'),
            array('MPATH:28' => 'atherosclerosis'),
            array('MPATH:29' => 'cataract'),
            array('MPATH:30' => 'cystic medial necrosis'),
            array('MPATH:31' => 'emphysema'),
            array('MPATH:32' => 'glaucoma'),
            array('MPATH:53' => 'osteopenia'),
            array('MPATH:54' => 'osteoporosis'),
            array('MPATH:62' => 'cyst'),
            array('MPATH:68' => 'diverticulum'),
            array('MPATH:70' => 'fistula'),
            array('MPATH:74' => 'hamartoma'),
            array('MPATH:75' => 'hernia'),
            array('MPATH:89' => 'cardiovascular developmental defect'),
            array('MPATH:90' => 'aneurysm'),
            array('MPATH:91' => 'arteriovenous anastomosis'),
            array('MPATH:92' => 'cardiac defect'),
            array('MPATH:93' => 'cardiac septation defect'),
            array('MPATH:94' => 'valve defect'),
            array('MPATH:95' => 'ventricular hypertrophy'),
            array('MPATH:96' => 'ventricular hypoplasia'),
            array('MPATH:97' => 'dissecting aneurysm'),
            array('MPATH:98' => 'lymphangiectasis'),
            array('MPATH:99' => 'varices'),
            array('MPATH:100' => 'placental abnormality'),
            array('MPATH:101' => 'glycogen cell defect'),
            array('MPATH:102' => 'labyrinth zone defect'),
            array('MPATH:103' => 'placental vascular defect'),
            array('MPATH:104' => 'spongiotrophoblast defect'),
            array('MPATH:109' => 'edema'),
            array('MPATH:110' => 'embolus'),
            array('MPATH:112' => 'amniotic fluid embolism'),
            array('MPATH:113' => 'atrial embolism'),
            array('MPATH:114' => 'bone marrow embolism'),
            array('MPATH:115' => 'fat embolism'),
            array('MPATH:116' => 'pulmonary embolism'),
            array('MPATH:117' => 'skin embolism'),
            array('MPATH:118' => 'systemic embolism'),
            array('MPATH:123' => 'purpura'),
            array('MPATH:128' => 'intra-epithelial neoplasia'),
            array('MPATH:130' => 'mild intra-epithelial neoplasia'),
            array('MPATH:131' => 'moderate intra-epithelial epithelial neoplasia'),
            array('MPATH:132' => 'severe intra-epithelial neoplasia'),
            array('MPATH:135' => 'epithelial and stromal hyperplasia'),
            array('MPATH:136' => 'fibroepithelial polyp'),
            array('MPATH:137' => 'fibroglandular hyperplasia'),
            array('MPATH:138' => 'epithelial hyperplasia'),
            array('MPATH:139' => 'goblet cell hyperplasia'),
            array('MPATH:140' => 'hyperplastic polyp'),
            array('MPATH:141' => 'inflammatory polyp'),
            array('MPATH:142' => 'intraductal hyperplasia'),
            array('MPATH:143' => 'microglandular hyperplasia'),
            array('MPATH:144' => 'myoepithelial hyperplasia'),
            array('MPATH:145' => 'fat cell hyperplasia'),
            array('MPATH:146' => 'histiocytosis'),
            array('MPATH:147' => 'lymphoid hyperplasia'),
            array('MPATH:148' => 'mesothelial hyperplasia'),
            array('MPATH:149' => 'squamous hyperplasia'),
            array('MPATH:150' => 'acanthosis'),
            array('MPATH:151' => 'actinic keratosis'),
            array('MPATH:152' => 'basal cell hyperplasia'),
            array('MPATH:153' => 'hypergranulosis'),
            array('MPATH:154' => 'hyperkeratosis'),
            array('MPATH:155' => 'orthokeratosis'),
            array('MPATH:156' => 'parakeratosis'),
            array('MPATH:157' => 'pseudoepithelioma/pseudocarcinoma'),
            array('MPATH:158' => 'seborrheic keratosis'),
            array('MPATH:161' => 'cartilaginous metaplasia'),
            array('MPATH:162' => 'epithelial metaplasia'),
            array('MPATH:163' => 'apocrine metaplasia'),
            array('MPATH:164' => 'gastric metaplasia'),
            array('MPATH:165' => 'glandular metaplasia'),
            array('MPATH:166' => 'goblet cell metaplasia'),
            array('MPATH:167' => 'intestinal metaplasia'),
            array('MPATH:168' => 'lipomatous metaplasia'),
            array('MPATH:169' => 'nephrogenic metaplasia'),
            array('MPATH:170' => 'oncocytic metaplasia'),
            array('MPATH:171' => 'squamous metaplasia'),
            array('MPATH:172' => 'transitional cell metaplasia'),
            array('MPATH:173' => 'myeloid metaplasia'),
            array('MPATH:174' => 'osseous metaplasia'),
            array('MPATH:183' => 'granulation tissue'),
            array('MPATH:188' => 'immunopathology'),
            array('MPATH:189' => 'hypersensitivity disease'),
            array('MPATH:190' => 'type i hypersensitivity'),
            array('MPATH:191' => 'type ii hypersensitivity'),
            array('MPATH:192' => 'type iii hypersensitivity'),
            array('MPATH:193' => 'type iv hypersensitivity'),
            array('MPATH:194' => 'immune mediated disease'),
            array('MPATH:195' => 'arthritis'),
            array('MPATH:196' => 'dermatitis'),
            array('MPATH:197' => 'glomerulonephritis'),
            array('MPATH:198' => 'immune mediated hemolytic anaemia'),
            array('MPATH:199' => 'immune mediated thrombocytopenia'),
            array('MPATH:200' => 'myositis'),
            array('MPATH:201' => 'vasculitis'),
            array('MPATH:202' => 'immunodeficiency disease'),
            array('MPATH:205' => 'immunodeficiency associated infection'),
            array('MPATH:206' => 'primary immunodeficiency disease'),
            array('MPATH:207' => 'immunodeficiency - lymphoid defect'),
            array('MPATH:208' => 'immunodeficiency - myeloid/phagocytic defect'),
            array('MPATH:209' => 'secondary immunodeficiency disease'),
            array('MPATH:210' => 'acquired immunodeficiency'),
            array('MPATH:211' => 'failed transfer of maternal immunoglobulin'),
            array('MPATH:219' => 'adnexal and skin appendage tumor'),
            array('MPATH:220' => 'apocrine adenocarcinoma'),
            array('MPATH:221' => 'apocrine adenoma'),
            array('MPATH:222' => 'ceruminous adenocarcinoma'),
            array('MPATH:223' => 'ceruminous adenoma'),
            array('MPATH:224' => 'clear cell hidradenoma'),
            array('MPATH:225' => 'eccrine gland adenocarcinoma'),
            array('MPATH:226' => 'eccrine gland adenoma'),
            array('MPATH:227' => 'eccrine papillary adenoma'),
            array('MPATH:228' => 'papillary hidradenoma'),
            array('MPATH:229' => 'sebaceous adenocarcinoma'),
            array('MPATH:230' => 'sebaceous adenoma'),
            array('MPATH:231' => 'syringoma'),
            array('MPATH:232' => 'trichoepithelioma'),
            array('MPATH:233' => 'basal cell tumor'),
            array('MPATH:234' => 'basal cell carcinoma'),
            array('MPATH:235' => 'blood vessel tumor'),
            array('MPATH:236' => 'angiofibroma'),
            array('MPATH:237' => 'angiokeratoma'),
            array('MPATH:238' => 'hemangioblastoma'),
            array('MPATH:240' => 'hemangioma'),
            array('MPATH:241' => 'hemangiopericytoma'),
            array('MPATH:242' => 'hemangiosarcoma'),
            array('MPATH:243' => 'central nervous system tumor'),
            array('MPATH:244' => 'astrocytoma'),
            array('MPATH:245' => 'choroid plexus carcinoma'),
            array('MPATH:246' => 'choroid plexus papilloma'),
            array('MPATH:247' => 'ependymoma'),
            array('MPATH:249' => 'glioma'),
            array('MPATH:250' => 'medulloblastoma'),
            array('MPATH:251' => 'meningioma'),
            array('MPATH:252' => 'oligodendroglioma'),
            array('MPATH:253' => 'sub-ependymoma'),
            array('MPATH:254' => 'complex or mixed tumor'),
            array('MPATH:255' => 'adenoacanthoma'),
            array('MPATH:256' => 'carcinosarcoma'),
            array('MPATH:257' => 'craniopharyngioma'),
            array('MPATH:258' => 'mesodermal mixed tumor'),
            array('MPATH:259' => 'mucoepidermoid carcinoma'),
            array('MPATH:260' => 'mullerian mixed tumor'),
            array('MPATH:261' => 'nephroblastoma'),
            array('MPATH:262' => 'pulmonary blastoma'),
            array('MPATH:263' => 'rhabdoid sarcoma'),
            array('MPATH:264' => 'fibroepithelial tumor'),
            array('MPATH:265' => 'adenofibroma'),
            array('MPATH:266' => 'fibroadenoma'),
            array('MPATH:267' => 'glandular tumor'),
            array('MPATH:268' => 'adenocarcinoma'),
            array('MPATH:269' => 'adenocarcinoma in situ'),
            array('MPATH:270' => 'adenoma'),
            array('MPATH:271' => 'adenomatous polyposis coli'),
            array('MPATH:272' => 'bronchioloalveolar adenoma'),
            array('MPATH:273' => 'bronchioloalveolar carcinoma'),
            array('MPATH:274' => 'clear cell adenocarcinoma'),
            array('MPATH:275' => 'comedocarcinoma'),
            array('MPATH:276' => 'cystadenocarcinoma'),
            array('MPATH:277' => 'cystadenoma'),
            array('MPATH:278' => 'follicular adenocarcinoma'),
            array('MPATH:279' => 'follicular adenoma'),
            array('MPATH:280' => 'harderian gland adenoma'),
            array('MPATH:281' => 'harderian gland carcinoma'),
            array('MPATH:282' => 'infiltrating duct carcinoma'),
            array('MPATH:284' => 'intraductal papillary carcinoma'),
            array('MPATH:285' => 'intraductal papilloma'),
            array('MPATH:286' => 'intraductal papillomatosis'),
            array('MPATH:287' => 'islet cell adenoma'),
            array('MPATH:288' => 'islet cell carcinoma'),
            array('MPATH:289' => 'lobular carcinoma'),
            array('MPATH:290' => 'lobular carcinoma in situ'),
            array('MPATH:291' => 'medullary carcinoma'),
            array('MPATH:292' => 'mucinous cystadenocarcinoma'),
            array('MPATH:293' => 'mucinous cystadenoma'),
            array('MPATH:294' => 'myoepithelioma'),
            array('MPATH:295' => 'papillary adenocarcinoma'),
            array('MPATH:296' => 'papillary adenoma'),
            array('MPATH:297' => 'papillary cystadenocarcinoma'),
            array('MPATH:298' => 'papillary cystadenoma'),
            array('MPATH:299' => 'pinealoma'),
            array('MPATH:300' => 'pineoblastoma'),
            array('MPATH:301' => 'pineocytoma'),
            array('MPATH:302' => 'pituicytoma'),
            array('MPATH:303' => 'pseudomyxoma peritonei'),
            array('MPATH:304' => 'serous adenocarcinoma'),
            array('MPATH:305' => 'serous cystadenoma'),
            array('MPATH:306' => 'signet ring cell carcinoma'),
            array('MPATH:307' => 'thyroid c-cell adenoma'),
            array('MPATH:308' => 'thyroid c-cell carcinoma'),
            array('MPATH:309' => 'gonadal tumor'),
            array('MPATH:310' => 'germ cell tumor'),
            array('MPATH:311' => 'dermoid cyst'),
            array('MPATH:312' => 'dysgerminoma'),
            array('MPATH:313' => 'embryonal carcinoma'),
            array('MPATH:314' => 'endodermal sinus tumor'),
            array('MPATH:315' => 'gonadoblastoma'),
            array('MPATH:316' => 'mixed germ cell tumor'),
            array('MPATH:317' => 'seminoma'),
            array('MPATH:318' => 'classical seminoma'),
            array('MPATH:319' => 'spermatocytic seminoma'),
            array('MPATH:320' => 'struma ovarii'),
            array('MPATH:321' => 'teratocarcinoma'),
            array('MPATH:322' => 'teratoma'),
            array('MPATH:323' => 'gonadal somatic cell tumor'),
            array('MPATH:326' => 'granulosa cell tumor'),
            array('MPATH:328' => 'leydig cell tumor'),
            array('MPATH:329' => 'luteal cell tumor'),
            array('MPATH:330' => 'ovarian stromal tumor'),
            array('MPATH:331' => 'sertoli cell tumor'),
            array('MPATH:332' => 'sertoli-leydig cell tumor'),
            array('MPATH:334' => 'thecoma'),
            array('MPATH:335' => 'hematopoietic/lymphoid malignancies/disorder'),
            array('MPATH:337' => 'erythroid leukaemia'),
            array('MPATH:340' => 'megakaryocytic leukaemia'),
            array('MPATH:341' => 'myelodysplastic disorder'),
            array('MPATH:342' => 'myeloid leukaemia'),
            array('MPATH:351' => 'thymoma'),
            array('MPATH:352' => 'hepatic tumor'),
            array('MPATH:353' => 'hepatocellular adenoma'),
            array('MPATH:354' => 'cholangiocarcinoma'),
            array('MPATH:355' => 'cholangiofibroma'),
            array('MPATH:356' => 'hepatoblastoma'),
            array('MPATH:357' => 'hepatocellular carcinoma'),
            array('MPATH:358' => 'melanocytic tumor'),
            array('MPATH:359' => 'melanoma'),
            array('MPATH:360' => 'melanoma in situ'),
            array('MPATH:361' => 'naevus'),
            array('MPATH:362' => 'mesonephroma'),
            array('MPATH:363' => 'mesonephric tumor'),
            array('MPATH:365' => 'mesothelioma'),
            array('MPATH:366' => 'neuroendocrine tumor'),
            array('MPATH:367' => 'carcinoid tumor'),
            array('MPATH:370' => 'primitive neurectodermal tumor'),
            array('MPATH:371' => 'small cell carcinoma'),
            array('MPATH:372' => 'tumorlet'),
            array('MPATH:373' => 'cns progenitor tumor'),
            array('MPATH:374' => 'esthesioneuroblastoma'),
            array('MPATH:375' => 'ganglioneuroma'),
            array('MPATH:376' => 'neuroblastoma'),
            array('MPATH:377' => 'phaeochromocytoma'),
            array('MPATH:378' => 'retinoblastoma'),
            array('MPATH:379' => 'odontogenic tumor'),
            array('MPATH:380' => 'ameloblastic fibroma'),
            array('MPATH:381' => 'ameloblastoma'),
            array('MPATH:382' => 'cementifying fibroma'),
            array('MPATH:383' => 'cementoblastoma'),
            array('MPATH:384' => 'cementoma'),
            array('MPATH:385' => 'dentinoma'),
            array('MPATH:387' => 'odontoma'),
            array('MPATH:388' => 'osseous and chondromatous tumor'),
            array('MPATH:389' => 'chondroblastoma'),
            array('MPATH:391' => 'chondroma'),
            array('MPATH:392' => 'chondrosarcoma'),
            array('MPATH:393' => 'osteoblastoma'),
            array('MPATH:394' => 'osteochondroma'),
            array('MPATH:395' => 'osteofibroma'),
            array('MPATH:396' => 'osteoma'),
            array('MPATH:397' => 'osteosarcoma'),
            array('MPATH:398' => 'paragangliomas and glomus tumor'),
            array('MPATH:399' => 'aortic body tumor'),
            array('MPATH:400' => 'carotid body tumor'),
            array('MPATH:401' => 'glomus jugulare tumor'),
            array('MPATH:403' => 'paraganglioma'),
            array('MPATH:404' => 'soft tissue tumor'),
            array('MPATH:405' => 'chordoma'),
            array('MPATH:406' => 'dermatofibrosarcoma'),
            array('MPATH:407' => 'soft tissue fibroma'),
            array('MPATH:408' => 'soft tissue fibrosarcoma'),
            array('MPATH:409' => 'fibrous histiocytoma'),
            array('MPATH:410' => 'giant cell tumor of soft tissue'),
            array('MPATH:412' => 'alveolar soft part sarcoma'),
            array('MPATH:413' => 'granular cell tumor'),
            array('MPATH:415' => 'lipomatous tumor'),
            array('MPATH:416' => 'angiomyolipoma'),
            array('MPATH:417' => 'lipoma'),
            array('MPATH:418' => 'liposarcoma'),
            array('MPATH:419' => 'lymphatic vessel tumor'),
            array('MPATH:420' => 'lymphangioma'),
            array('MPATH:421' => 'lymphangiomyomatosis'),
            array('MPATH:422' => 'lymphangiosarcoma'),
            array('MPATH:423' => 'myomatous tumor'),
            array('MPATH:424' => 'intravascular leiomyomatosis'),
            array('MPATH:425' => 'leiomyoma'),
            array('MPATH:426' => 'leiomyosarcoma'),
            array('MPATH:428' => 'rhabdomyosarcoma'),
            array('MPATH:429' => 'myxomatous tumor'),
            array('MPATH:430' => 'angiomyxoma'),
            array('MPATH:431' => 'myxoma'),
            array('MPATH:432' => 'myxosarcoma'),
            array('MPATH:433' => 'nerve sheath tumor'),
            array('MPATH:435' => 'neurofibroma'),
            array('MPATH:436' => 'neurofibromatosis'),
            array('MPATH:437' => 'neurofibrosarcoma'),
            array('MPATH:438' => 'schwannoma'),
            array('MPATH:439' => 'synovial-like tumor'),
            array('MPATH:440' => 'synovial sarcoma'),
            array('MPATH:442' => 'squamous cell tumor'),
            array('MPATH:443' => 'inverted squamous papilloma'),
            array('MPATH:444' => 'keratoacanthoma'),
            array('MPATH:445' => 'papilloma'),
            array('MPATH:446' => 'squamous cell carcinoma'),
            array('MPATH:447' => 'squamous cell carcinoma in situ'),
            array('MPATH:448' => 'squamous cell papilloma'),
            array('MPATH:449' => 'verrucous carcinoma'),
            array('MPATH:450' => 'transitional cell tumor'),
            array('MPATH:451' => 'transitional cell carcinoma'),
            array('MPATH:452' => 'transitional cell carcinoma in situ'),
            array('MPATH:453' => 'transitional cell papilloma'),
            array('MPATH:454' => 'trophoblastic tumor'),
            array('MPATH:455' => 'choriocarcinoma'),
            array('MPATH:456' => 'hydatidiform mole'),
            array('MPATH:457' => 'placental site trophoblastic tumor'),
            array('MPATH:460' => 'nuclear defect'),
            array('MPATH:461' => 'cataract; capsular-epithelial'),
            array('MPATH:462' => 'cataract; nuclear and cortical'),
            array('MPATH:463' => 'cataract; cortical liquefactive'),
            array('MPATH:464' => 'cataract; lens extrusion'),
            array('MPATH:465' => 'glaucoma developmental'),
            array('MPATH:466' => 'glaucoma; open angle'),
            array('MPATH:467' => 'glaucoma; angle closure'),
            array('MPATH:468' => 'pseudocyst'),
            array('MPATH:476' => 'telangiectasia'),
            array('MPATH:477' => 'choristoma'),
            array('MPATH:482' => 'focal hyperplasia'),
            array('MPATH:484' => 'nodular hyperplasia'),
            array('MPATH:486' => 'ductal intra-epithelial neoplasia'),
            array('MPATH:487' => 'intestinal intra-epithelial neoplasia'),
            array('MPATH:488' => 'prostate intra-epithelial neoplasia'),
            array('MPATH:490' => 'adenomatous polyp'),
            array('MPATH:491' => 'polyp'),
            array('MPATH:492' => 'membraneous glomerulonephritis'),
            array('MPATH:493' => 'membraneoproliferative glomerulonephritis'),
            array('MPATH:494' => 'autoimmune glomerulonephritis'),
            array('MPATH:495' => 'glomangioma'),
            array('MPATH:496' => 'non-lymphoid leukaemias'),
            array('MPATH:497' => 'myeloid leukaemia without maturation'),
            array('MPATH:498' => 'myeloid leukaemia with maturation'),
            array('MPATH:500' => 'myeloproliferative disease-like myeloid leukaemia'),
            array('MPATH:501' => 'myelomonocytic leukaemia'),
            array('MPATH:502' => 'monocytic leukaemia'),
            array('MPATH:503' => 'biphenotypic leukaemia'),
            array('MPATH:504' => 'non-lymphoid hematopoietic sarcomas'),
            array('MPATH:505' => 'granulocytic sarcoma'),
            array('MPATH:506' => 'histiocytic sarcoma'),
            array('MPATH:507' => 'mast cell sarcoma'),
            array('MPATH:508' => 'myeloid dysplasias'),
            array('MPATH:509' => 'cytopenia with increased blasts'),
            array('MPATH:510' => 'myeloid proliferations (non-reactive)'),
            array('MPATH:511' => 'myeloproliferation (genetic)'),
            array('MPATH:512' => 'myeloproliferative disease'),
            array('MPATH:513' => 'lymphoid neoplasms'),
            array('MPATH:515' => 'non-lymphoid neoplasias'),
            array('MPATH:516' => 'b-cell neoplasms'),
            array('MPATH:517' => 'precursor b cell neoplasms'),
            array('MPATH:518' => 'precursor b -cell lymphoblastic lymphoma/leukaemia'),
            array('MPATH:519' => 'mature b-cell neoplasms'),
            array('MPATH:520' => 'small b-cell lymphoma'),
            array('MPATH:521' => 'splenic marginal zone lymphoma'),
            array('MPATH:522' => 'follicular b cell lymphoma'),
            array('MPATH:523' => 'diffuse large b-cell lymphoma'),
            array('MPATH:524' => 'diffuse large b-cell lymphoma, centroblastic type'),
            array('MPATH:525' => 'diffuse large b-cell lymphoma, immunoblastic type'),
            array('MPATH:526' => 'diffuse large b-cell lymphoma, histiocyte associated'),
            array('MPATH:527' => 'primary mediastinal (thymic) diffuse large b cell lymphoma'),
            array('MPATH:528' => 'classic burkitt lymphoma'),
            array('MPATH:529' => 'burkitt-like lymphoma'),
            array('MPATH:530' => 'plasma cell neoplasms'),
            array('MPATH:531' => 'b-natural killer cell lymphoma'),
            array('MPATH:532' => 'plasmacytoma'),
            array('MPATH:533' => 'extraosseous plasmacytoma'),
            array('MPATH:534' => 'anaplastic plasmacytoma'),
            array('MPATH:535' => 't-cell neoplasms'),
            array('MPATH:536' => 'precursor t-cell neoplasms'),
            array('MPATH:537' => 'precursor t-cell lymphoblastic lymphoma/leukaemia'),
            array('MPATH:538' => 'mature t-cell neoplasms'),
            array('MPATH:539' => 'small t-cell lymphoma'),
            array('MPATH:540' => 't cell neoplasms character undetermined'),
            array('MPATH:541' => 't large cell anaplastic lymphoma'),
            array('MPATH:542' => 't natural killer cell lymphoma'),
            array('MPATH:543' => 'cholangioma'),
            array('MPATH:544' => 'hepatocholangiocellular adenoma'),
            array('MPATH:545' => 'hepatocholangiocellular carcinoma'),
            array('MPATH:548' => 'papillary transitional cell carcinoma'),
            array('MPATH:549' => 'carcinoma'),
            array('MPATH:550' => 'sarcoma'),
            array('MPATH:552' => 'clara cell adenoma [use MPATH:272]'),
            array('MPATH:553' => 'neuritis'),
            array('MPATH:556' => 'potentially cancerous lesions'),
            array('MPATH:557' => 'aberrant crypt foci [use MPATH:130]'),
            array('MPATH:558' => 'adenomyoepithelioma'),
            array('MPATH:559' => 'acinar adenocarcinoma'),
            array('MPATH:560' => 'cribriform adenocarcinoma'),
            array('MPATH:561' => 'adenosquamous carcinoma'),
            array('MPATH:563' => 'mucinous carcinoma'),
            array('MPATH:564' => 'glioblastoma'),
            array('MPATH:568' => 'mixed glioma'),
            array('MPATH:569' => 'granular cell tumor'),
            array('MPATH:570' => 'peripheral nervous system tumors'),
            array('MPATH:571' => 'pilomatricoma'),
            array('MPATH:572' => 'ossifying fibroma'),
            array('MPATH:573' => 'inverted transitional cell papilloma'),
            array('MPATH:575' => 'basaloid follicular neoplasms'),
            array('MPATH:576' => 'trichofolliculoma'),
            array('MPATH:577' => 'spiradenoma'),
            array('MPATH:578' => 'trichoblastoma'),
            array('MPATH:579' => 'ulcer'),
            array('MPATH:582' => 'cicatricial alopecia'),
            array('MPATH:583' => 'scarring alopecia'),
            array('MPATH:584' => 'myelofibrosis'),
            array('MPATH:585' => 'acidophilic macrophage pneumonia'),
            array('MPATH:587' => 'cribriform epididymal hyperplasia'),
            array('MPATH:590' => 'fibro-osseous lesion'),
            array('MPATH:592' => 'osteopetrosis'),
            array('MPATH:594' => 'cystic hyperplasia'),
            array('MPATH:595' => 'extramedullary hemopoiesis'),
            array('MPATH:608' => 'abscess'),
            array('MPATH:609' => 'empyema'),
            array('MPATH:610' => 'angiectasia'),
            array('MPATH:611' => 'glossitis'),
            array('MPATH:615' => 'cardiomyopathy'),
            array('MPATH:616' => 'hyperostosis'),
            array('MPATH:617' => 'focus of cellular alteration'),
            array('MPATH:618' => 'myeloid hyperplasia'),
            array('MPATH:619' => 'truncoconal septal defect'),
            array('MPATH:620' => 'inter-atrial septal defect'),
            array('MPATH:621' => 'inter-ventricular septal defect'),
            array('MPATH:622' => 'steatosis'),
            array('MPATH:626' => 'prolapse'),
            array('MPATH:627' => 'pyogranulomatous inflammation'),
            array('MPATH:630' => 'karyomegaly'),
            array('MPATH:631' => 'hypertrophic tissue'),
            array('MPATH:632' => 'Hepatocytomegaly'),
            array('MPATH:633' => 'cholelithiasis'),
            array('MPATH:634' => 'furunculosis'),
            array('MPATH:635' => 'hydronephrosis'),
            array('MPATH:636' => 'steatitis'),
            array('MPATH:638' => 'cellulitis'),
            array('MPATH:639' => 'hydrocephalus'),
            array('MPATH:641' => 'epidermal inclusion cyst'),
            array('MPATH:642' => 'cholesterol deposition'),
            array('MPATH:643' => 'serous exudates'),
            array('MPATH:644' => 'crystalloids formation')
        );
    }
    
    private function getHistPATOTerms()
    {
        return array(
            array('PATO:0000461' => 'normal'),
            array('PATO:0000394' => 'mild'),
            array('PATO:0000395' => 'moderate'),
            array('PATO:0000465' => 'marked'),
            array('PATO:0000396' => 'severe'),
            array('PATO:0002387' => 'peracute'),
            array('PATO:0000389' => 'acute'),
            array('PATO:0002091' => 'subacute'),
            array('PATO:0001863' => 'chronic'),
            array('PATO:0002414' => 'chronic-active'),
            array('PATO:0000627' => 'focal'),
            array('PATO:0002415' => 'focally extensive'),
            array('PATO:0001791' => 'multi-focal'),
            array('PATO:0002402' => 'multifocal to coalescing'),
            array('PATO:0002401' => 'random'),
            array('PATO:0001566' => 'diffuse'),
            array('PATO:0002403' => 'generalized'),
            array('PATO:0000634' => 'unilateral'),
            array('PATO:0000618' => 'bilateral'),
            array('PATO:0002404' => 'segmental'),
            array('PATO:0000632' => 'symmetrical'),
            array('PATO:0002417' => 'transmural')
        );
    }
    
    
    /**
     * Luis Santos requested the adding of Abnormal Body Weight MP Term for
     * Parameters with Increased and Decreased terms for the sake of the new
     * mixed model behaving correctly when there is gender-related dimorphism
     * @process-run 2014-01-21 13:16
     */
    public function addAbnormalBodyWeightMPTerm()
    {
        die('This process has already been run.');
	
        //get list of parameters that have abnormal body weight to exclude below
        $exclude = $this->db->select('parameter_id')
                            ->from('param_mpterm')
                            ->where('mp_id', 'MP:0001259') //abnormal
                            ->get()
                            ->result_array();
        $excludeList = array_map(function($r){return array_shift($r);}, $exclude);
        
        //find parameters which have de/in-creased body weight but not abnormal body weight
        $params = $this->db->select('m.parameter_id, p.procedure_id, p.pipeline_id, a.parameter_key')
                           ->from('param_mpterm m')
                           ->join('original_pathways p', 'p.parameter_id = m.parameter_id', 'inner')
                           ->join('parameter a', 'a.parameter_id = m.parameter_id', 'inner')
                           ->where_not_in('m.parameter_id', $excludeList)
                           ->where_in('mp_id', array('MP:0001262', 'MP:0001260')) //decreased, increased
                           ->group_by('m.parameter_id')
                           ->get()
                           ->result_array();
        
        //define the new mp term to insert
        $arr = array(
            'param_mpterm_id'   => null,
            'mp_term'           => 'abnormal body weight',
            'mp_id'             => 'MP:0001259',
            'selection_outcome' => ESelectionOutcome::ABNORMAL,
            'parameter_id'      => null, //to be set below
            'procedure_id'      => null, //to be set below
            'pipeline_id'       => null, //to be set below
            'increment_id'      => null,
            'option_id'         => null,
            'sex'               => null,
            'time_modified'     => $this->config->item('timestamp'),
            'user_id'           => User::getId()
        );
        
        //insert the abnormal body weight mp term for each of those parameters
        echo '<p>Inserting abnormal body weight for parameters:</p>' . PHP_EOL;
        $this->load->model('parammptermmodel');
        foreach ($params as $param) {
            if ($this->parammptermmodel->insert(array_merge($arr, $param))) {
                echo 'Inserted for ';
            } else {
                echo 'Error inserting for ';
            }
            echo $param['parameter_key'] . "<br>\n";
        }
        
        echo '<br>Done!';
    }
}
