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

class Ajax extends CI_Controller
{
    private $_controller = null;

    /**
     * All actions must be accessed using Ajax and responses are returned as JSON
     */
    public function __construct()
    {
        parent::__construct();
        $this->_controller = $this->router->class;
        header('Content-type: application/json');
        if ( ! $this->input->is_ajax_request()) {
            die('');
        }
    }

    /**
     * Echos array of procedure objects with procedure_id, procedure_key and
     * name as properties:
     * 
     * <pre> 
     * [
     * {"procedure_id":"105", "procedure_key":"IMPC_FER_001", "name":"Fertility"},
     * {"procedure_id":"81",  "procedure_key":"IMPC_OFD_001", "name":"Open Field"}
     * ]
     * </pre>
     * 
     * @param int $pipelineId Pipeline Id
     * @param int $notAlreadyInPipelineId Do not display procedures if they are
     * in this pipeline
     */
    public function getProcedures($pipelineId = null, $notAlreadyInPipelineId = null)
    {
        $pip = new Pipeline((int)$pipelineId);
        if ( ! $pip->exists() ||  ! should_display($pip)) {
            die(json_encode(array()));
        }
        $procedures = new ArrayIterator(PipelineHasProcedures::fetchAll($pip->getId()));
        $procs = array();
        $skip = array();
        if ($notAlreadyInPipelineId) {
            $np = new Pipeline($notAlreadyInPipelineId);
            if ($np->exists()) {
                foreach (PipelineHasProcedures::fetchAll($np->getId()) as $proc) {
                    $skip[] = $proc->getId();
                }
            }
        }
        foreach ($procedures as $proc) {
            if (in_array($proc->getId(), $skip)) {
                continue;
            }
            $procs[] = array(
                'procedure_id' => $proc->getId(),
                'procedure_key' => $proc->getItemKey(),
                'name' => $proc->getItemName()
            );
        }

        echo json_encode($procs);
    }

    /**
     * Echos array of parameter objects present in the given procedure with
     * parameter_id, parameter_key and name as properties:
     * 
     * <pre> 
     * [
     * {"parameter_id":"2365", "parameter_key":"IMPC_FER_001_001", "name":"Gross Findings Male"},
     * {"parameter_id":"4012", "parameter_key":"IMPC_OFD_001_001", "name":"Whole arena resting time series"}
     * ]
     * </pre>
     * 
     * @param int $procedureId Procedure Id
     * @param bool $optionsOnly If set to 1 (true) it return only parameters with options
     */
    public function getParameters($procedureId = null, $optionsOnly = false)
    {
        $proc = new Procedure((int) $procedureId);
        if ( ! $proc->exists() || ! should_display($proc)) {
            die(json_encode(array()));
        }
        $parameters = new ArrayIterator(ProcedureHasParameters::fetchAll($proc->getId()));
        $params = array();
        foreach ($parameters as $param) {
            if ($optionsOnly && ! $param->isOption()) {
                continue;
            }
            $params[] = array(
                'parameter_id' => $param->getId(),
                'parameter_key' => $param->getItemKey(),
                'name' => $param->getItemName()
            );
        }
        echo json_encode($params);
    }
    
    /**
     * Echos array of option objects of the given parameter with the keys
     * param_option_id and name
     * 
     * <pre>
     * [
     * {"param_option_id":"3", "name":"yes"},
     * {"param_option_id":"4", "name":"no"}
     * ]
     * </pre>
     * 
     * @param int $parameterId Parameter Id
     */
    public function getParameterOptions($parameterId = null)
    {
        $param = new Parameter((int) $parameterId);
        if ( ! $param->exists() || !should_display($param)) {
            die(json_encode(array()));
        }
        $options = new ArrayIterator($param->getOptions());
        $ops = array();
        foreach ($options as $option) {
            $ops[] = array(
                'param_option_id' => $option->getId(),
                'name' => $option->getName()
            );
        }
        echo json_encode($ops);
    }

    /**
     * Echos list of relationships associated with the selected item(s)
     * @param string $itemType either paramoption, parameter or procedure can have relations
     * @param int $from Id
     * @param int $to Id
     */
    public function getRelations($itemType, $from, $to = null)
    {
        if ( ! in_array($itemType, array('paramoption', 'parameter', 'procedure'))) {
            die(json_encode(array()));
        }
        $model = $itemType . 'relationsmodel';
        $this->load->model($model);
        if ($itemType == 'paramoption') {
            $result = (empty($to)) ? $this->$model->getByOptionOrParent($from) : $this->$model->getByOptionAndParentId($from, $to);
        } else if ($itemType == 'parameter') {
            $result = (empty($to)) ? $this->$model->getByParameterOrParent($from) : $this->$model->getByParameterAndParentId($from, $to);
        } else if ($itemType == 'procedure') {
            $result = (empty($to)) ? $this->$model->getByProcedureOrParent($from) : $this->$model->getByProcedureAndParentId($from, $to);
        }
        echo json_encode($result);
    }
    
    /**
     * Deletes a relationship. If the item type is not in expected list it echos
     * an empty string
     * @param int $id Relationship record Id
     * @param string $itemType procedure, parameter or paramoption
     * @return string json: ["success": true] or ["success": false]
     */
    public function deleteRelationship($id, $itemType)
    {
        if ( ! in_array($itemType, array('procedure', 'parameter', 'paramoption'))) {
            die('');
        }
        
        $model = $itemType . 'relationsmodel';
        $this->load->model($model);
        $response = array('success' => (bool)$this->$model->delete($id));
        echo json_encode($response);
    }

    /**
     * Autocomplete for ontology search. Expects these $_GET parameters be sent:
     * 
     * maxRows - Number of results to return, default is 25
     * name_startsWith - the word we're searching, needs to be 2 chars or longer
     * JSON returned in this form:
     *
     * <pre>
     * [
     * {"label":"abnormal adipose tissue distribution","value":"MP:0000013"},
     * {"label":"big ears","value":"MP:0000017"}
     * ]
     * </pre>
     */
    public function autocomplete()
    {
        //number of rows to return
        $limit = (int) $this->input->get('maxRows');
        $limit = (empty($limit)) ? 25 : abs((int) $limit);

        //get search term
        $term = trim($this->input->get('name_startsWith'));
        if (strlen($term) < 2) {
            die(json_encode(array()));
        }

        //search term in tables
        $this->load->model('parammptermmodel');
        $this->load->model('parameqtermmodel');
        $arr = array_slice(
            array_merge(
                $this->parameqtermmodel->searchEntity($term),
                $this->parameqtermmodel->searchQuality($term),
                $this->parammptermmodel->search($term)
            ),
            0,
            $limit,
            true
        );

        //remove duplicates
        $duparr = $arr;
        for ($i = 0; $i < count($arr); $i++) {
            $value = $arr[$i]['value'];
            for ($j = $i + 1; $j < count($arr); $j++) {
                if ($value == $arr[$j]['value']) {
                    unset($duparr[$j]['value']);
                }
            }
        }
        unset($arr);
        $uniqarr = array();
        foreach ($duparr as $i) {
            if (!empty($i['value'])) {
                $uniqarr[] = $i;
            }
        }

        //output json
        echo json_encode($uniqarr);
    }

    /**
     * Ontology search results, expects valid ontology via $_GET['term'].
     * Invalid terms return an empty string.
     */
    public function search()
    {
        $term = trim($this->input->get('term'));
        if (preg_match('/^(MP|CHEBI|BSPO|CL|ENVO|GO|IMR|MA|PATO):([0-9]{5}|[0-9]{7,8})$/', $term) == 0) {
            die('');
        }

        $results = array();

        //look for MP ontologies in mpterm table
        if (preg_match('/^MP/', $term)) {
            $this->load->model('parammptermmodel');
            $results = $this->parammptermmodel->searchresult($term);
        } else {
            $this->load->model('parameqtermmodel');

            //if PATO was searched only search quality columns as only PATO is found in quality cols
            if (preg_match('/^PATO/', $term)) {
                $results = $this->parameqtermmodel->resultQuality($term);
            } else {
                $results = $this->parameqtermmodel->resultEntity($term);
            }
        }

        //an empty array means no matches found
        echo json_encode($results);
    }

    /**
     * Returns Ontology Options for the given Ontology Group in this format:
     *
     * <pre>
     * [
     * {"id":1, "ontology_id":"PATO:0000017", "ontology_term":"Increased Salivation"}
     * {"id":2, "ontology_id":"PATO:0000019", "ontology_term":"Decreased Salivation"}
     * ]
     * </pre>
     * 
     * @param int $ontologyGroupId Ontology Group Id
     */
    public function getOntologyOptions($ontologyGroupId = null)
    {
        $og = new OntologyGroup((int) $ontologyGroupId);

        $results = array();

        foreach ($og->getOntologyOptions() as $option) {
            $results[] = array(
                'id' => $option->getId(),
                'ontology_id' => $option->getOntologyId(),
                'ontology_term' => $option->getOntologyTerm()
            );
        }

        echo json_encode($results);
    }

    /**
     * Just toggles the version_triggering value in the database
     * @return string json: [status: 'on'] or [status: 'off']
     */
    public function toggleVersionTriggering()
    {
        if (User::isSuperAdmin()) {
            $this->load->model('overridesettingsmodel');
            $this->overridesettingsmodel->updateRunningConfig();
            $toggledSetting = ! ($this->config->item('version_triggering'));
            $this->overridesettingsmodel->updateValue('version_triggering', $toggledSetting);
        }
        $status = ($this->config->item('version_triggering')) ? 'on' : 'off';
        echo json_encode(array('status' => $status));
    }
}
