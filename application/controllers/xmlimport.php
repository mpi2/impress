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
 * This controller handles the importing of parameters defined in the XML format
 */
class XMLImport extends CI_Controller
{

	private $_controller = null;
	private $_xmldir = null;
        const FILE_EXECUTE_MODE = 0755;

	public function __construct()
	{
            parent::__construct();
            $this->_controller = $this->router->class;
            $this->_xmldir = $this->config->item('importxmlpath');
            if( ! User::hasPermission(User::IMPORT_ITEM))
                die('Access Denied. You do not have permission to import items into IMPReSS.');

            //update config from database
            $this->load->model('overridesettingsmodel');
            $this->overridesettingsmodel->updateRunningConfig();
	}

	public function index()
	{
		redirect( $this->_controller . '/selectxml' );
	}
	
	public function selectxml($pipelineId = null, $procedureId = null)
	{
		//open up the xml folder and get list of xml files and the names of their procedures
		$this->load->helper('directory');
		$allfiles = directory_map($this->_xmldir, 1);
		$xmlfiles = array();
		foreach($allfiles AS $af){
			if(preg_match('/\.xml$/', $af)){
				$proc = @simplexml_load_file($this->_xmldir . $af);
				if( ! $proc)
					continue;
				$xmlfiles[] = array('filename' => $af, 'procname' => (string)$proc['name']);
			}
		}
		
		//when pipeline and procedure id passed in
		$pipeline = new Pipeline((int)$pipelineId);
		
		$this->load->helper('form');
		$pipelines = array();
		foreach (PipelinesFetcher::fetchAll() AS $p) {
			if ($p->isDeprecated() && ($this->config->item('modify_deprecated') === FALSE))
				continue;
			$pipelines[] = $p;
		}
		$content = $this->load->view(
			'admin/xmlimport',
			array(
				'controller' => $this->_controller, 
				'pipelines' => $pipelines,
				'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()), //$pipeline->getProcedures()
				'xmlfiles' => $xmlfiles, 
				'xmldir' => $this->_xmldir,
				'selectedPipeline' => $pipeline->getId(),
				'selectedProcedure' => (int)$procedureId
			),
			true
		);
		
		$this->load->view('impress', array('content' => $content, 'title' => 'Import XML Procedures'));
	}
	
	public function importxml()
	{
		// die(var_export($_POST, true));
		
		//form fields
		$xmlfile    = $this->input->post('xmlfile');
		$pipid      = $this->input->post('pipid');
		$procid     = $this->input->post('procid');
		$importmode = $this->input->post('importmode');
		
		$content  = "<h1>XML Import</h1>\n";
		
		//checks
		if(empty($xmlfile) || empty($procid) || empty($pipid) || empty($importmode))
			die('All form fields are required and must be completed');
		if( ! file_exists($this->_xmldir . $xmlfile))
			die('XML File missing');
		$pipeline  = new Pipeline((int)$pipid);
		$procedure = new Procedure((int)$procid, $pipeline->getId());
		if($pipeline->exists() === false || $procedure->exists() === false)
			die('Pipeline/Procedure not found');
		exec("php " . realpath(FCPATH) . "/validateparameters.php " . $this->_xmldir . $xmlfile, $validationcheck, $returnvar);
		if ($returnvar == 1) { 
			die('<p>Please fix these validation errors in order to continue.</p>' . nl2br(join("\n", $validationcheck)));
		} else if ($returnvar == 126) {
			print 'It appears validateparameters.php needs to be made executable. Attempting to CHMOD the file now... ';
			print (chmod(realpath(FCPATH) . '/validateparameters.php', self::FILE_EXECUTE_MODE)) ? 'Success! Please reload the page.' : 'Failed! Please contact the administrator.';
			exit;
		} else if ($returnvar != 0) {
			die('An uncaught error occured while trying to run the validator on the commandline. Please contact the administrator. ' . $validationcheck);
		}
		
		
		//delete all parameters in procedure first?
		if ($importmode == 'delete') {	
			$paramcount = 0;
			$this->load->model('procedurehasparametersmodel');
			$params = $this->procedurehasparametersmodel->getByProcedure($procid, false);
			foreach($params AS $param){
				$rowsaffected = $this->parametermodel->delete($param[ParameterModel::PRIMARY_KEY], $procid, array('procedure_id' => $procid, 'pipeline_id' => $pipid));
				$paramcount += (int)$rowsaffected;
			}
			$content .= '<p>' . $paramcount . ' of ' . count($params) . ' Parameters in current ' . e($procedure->getItemName()) . ' Procedure Successfully Deleted.</p>';
			unset($params);
		}
		
		//loop through xml to insert parameters and stuff
		$proc = simplexml_load_file($this->_xmldir . $xmlfile);
		$paramcount = 0;
		foreach($proc->parameter AS $parameter){
			
			//grab increments
			$increments = array();
			$insertedIncrements = array();
			foreach($parameter->increments->children() AS $incs){
				$v = html_entity_decode(trim((string)$incs));
				if(strlen($v) > 0) $increments[] = $v;
			}
			//grab options
			$options = array();
			$insertedOptions = array();
			foreach($parameter->options AS $o){
				foreach($o->optionsgroup AS $og){
					$ov = html_entity_decode(trim((string)$og->optionsvalue));
					$od = trim((string)$og->optionsdescription);
					if(strlen($ov) > 0)
						$options[] = array('value' => $ov, 'description' => $od);
				}
			}
			//grab option/parameter ontologies
			$opontologies = array();
			foreach($parameter->ontologies AS $onts){
				foreach($onts->ontologyitem AS $oi){
					$opontologies[] = array(
						'option' => html_entity_decode(trim((string)$oi->option)),
						'increment' => html_entity_decode(trim((string)$oi->increment)),
						'sex' => (string)$oi->sex,
						'selectionoutcome' => (string)$oi->selectionoutcome,
						'mpid' => (string)$oi->mpid,
						'mpterm' => (string)$oi->mpterm
					);
				}
			}
		
			//prepare parameter values for insertion
			$param = array();
			$param['pipeline_id'] = $pipeline->getId();
			$param['procedure_id'] = $procedure->getId();
			$param['parameter_key'] = $this->parametermodel->getNewParameterKeyForProcedure($procid);
			$param['name'] = trim((string)$parameter->parametername);
			$param['major_version'] = 1;
			$param['minor_version'] = 0;
			$param['derivation'] = trim((string)$parameter->derivedformula);
			$param['derivation'] = (empty($param['derivation'])) ? NULL : $param['derivation'];
			$param['description'] = strtolower(trim(preg_replace('/_+/', '_', preg_replace('/[^a-zA-Z0-9]/', '_', (string)$parameter->parametername)), '_ '));
			$param['description'] = (empty($param['description'])) ? NULL : $param['description'];
			$param['is_annotation'] = ('Yes' == (string)$parameter->annotation) ? 1 : 0;
			$param['is_derived'] = ((string)$parameter->derivedformula) ? 1 : 0;
			$param['is_important'] = ('Yes' == (string)$parameter->requiredfordataanalysis) ? 1 : 0;
			$param['is_increment'] = ((int)$parameter->incrementminimum > 0 || sizeof($increments) > 0) ? 1 : 0;
			$param['is_media'] = ('Yes' == (string)$parameter->imagemedia) ? 1 : 0;
			$param['is_meta'] = ('MetaData' == (string)$parameter->parametertype) ? 1 : 0;
			$param['is_option'] = (empty($options)) ? 0 : 1;
			$param['is_required'] = ('Yes' == (string)$parameter->requiredforupload) ? 1 : 0;
			$param['unit'] = trim((string)$parameter->unit);
			$param['unit'] = (empty($param['unit'])) ? NULL : $param['unit'];
			$qcmin = trim((string)$parameter->qcmin);
			$qcmax = trim((string)$parameter->qcmax);
			$qcnotes = trim((string)$parameter->qcnotes);
			$param['qc_check'] = (strlen($qcmin)==0 && strlen($qcmax)==0) ? 0 : 1;
			$param['qc_min'] = ($param['qc_check'] == 1) ? (float)$qcmin : NULL;
			$param['qc_max'] = ($param['qc_check'] == 1) ? (float)$qcmax : NULL;
			$param['qc_notes'] = (empty($qcnotes)) ? NULL : $qcnotes;
			$valuetype = trim((string)$parameter->datatype);
			if($valuetype == 'Text')
				$param['value_type'] = EParamValueType::TEXT;
			else if($valuetype == 'Float')
				$param['value_type'] = EParamValueType::FLOAT;
			else if($valuetype == 'Boolean')
				$param['value_type'] = EParamValueType::BOOL;
			else if($valuetype == 'Date Time')
				$param['value_type'] = EParamValueType::DATETIME;
			else if($valuetype == 'Date')
				$param['value_type'] = EParamValueType::DATE;
			else if($valuetype == 'Time')
				$param['value_type'] = EParamValueType::TIME;
			else if($valuetype == 'Integer')
				$param['value_type'] = EParamValueType::INT;
			else if($valuetype == 'Image')
				$param['value_type'] = EParamValueType::IMAGE;
			else{
				$content .= '<p>Unknown value type... using TEXT instead</p>';
				$param['value_type'] = EParamValueType::TEXT;
			}
			$graphtype = trim((string)$parameter->graphtype);
			if(empty($graphtype))
				$param['graph_type'] = EParamGraphType::NULL;
			else if($graphtype == '1D')
				$param['graph_type'] = EParamGraphType::ONE_D;
			else if($graphtype == '2D')
				$param['graph_type'] = EParamGraphType::TWO_D;
			else if($graphtype == 'Categorical')
				$param['graph_type'] = EParamGraphType::CATEGORICAL;
			else if($graphtype == 'Image')
					$param['graph_type'] = EParamGraphType::IMAGE;
			else{
				$content .= '<p>Unknown graph type... using NULL instead</p>';
				$param['graph_type'] = EParamGraphType::NULL;
			}
			$param['data_analysis_notes'] = trim((string)$parameter->notes);
			$param['data_analysis_notes'] = (empty($param['data_analysis_notes'])) ? NULL : $param['data_analysis_notes'];
			$param['time_modified'] = $this->config->item('timestamp');
			$param['user_id'] = User::getId();
			$param['internal'] = 0;
			$param['type'] = EParamType::SIMPLE;
			if($param['is_meta']){
				$param['type'] = EparamType::METADATA;
			}
			if($param['is_media']){
				$param['type'] = EParamType::SERIES_MEDIA;
			}
			if($param['is_increment'] && $param['type'] == EParamType::SIMPLE){
				$param['type'] = EParamType::SERIES;
			}
			$param['weight'] = $paramcount;
                        
			//get and set the id for the unit used
			$this->load->model('unitmodel');
			$unit = $this->unitmodel->getByUnit($param['unit']);
			if ( ! empty($unit)) {
				$param['unit'] = $unit[UnitModel::PRIMARY_KEY];
			} else {
				//a new unit needs to be added to the units table
				$unitId = $this->unitmodel->insert(array('unit' => $param['unit']));
				if($unitId)
					$param['unit'] = $unitId;
				else
					die('An error occured while attempting to add a new unit.');
			}
			
			// die(var_export($param, TRUE));
			
			//insert parameter values
			$paramid = $this->parametermodel->insert($param, ChangeLogger::ACTION_IMPORT);
			if(FALSE === $paramid)
				die('Insertion halted. An error occured while trying to insert ' . var_export($param, TRUE));
			
			//insert options
			$this->load->model('paramoptionmodel');
			foreach($options AS $option){
				$oi = $this->paramoptionmodel->insert(
					array(
						'pipeline_id' => $param['pipeline_id'],
						'procedure_id' => $param['procedure_id'],
						'parameter_id' => (int)$paramid,
						'name' => $option['value'],
						'is_default' => 0,
						'is_active' => 1,
						'parent_id' => NULL,
						'description' => (empty($option['description'])) ? NULL : $option['description'],
						'deleted' => 0,
						'user_id' => $param['user_id'],
						'time_modified' => $param['time_modified']
					),
					ChangeLogger::ACTION_IMPORT
				);
				if(FALSE === $oi)
					die('Insertion halted. An error occured while trying to insert option for parameter ' . $paramid . PHP_EOL . var_export($option, TRUE));
				else 
					$insertedOptions[$oi] = $option['value'];
			}
			
			//insert increments
			$this->load->model('paramincrementmodel');
			$unit = null;
			$ii = null;
			$weight = 0;
			//work out increment type - hours/minutes represent a defined increment while number represents a simple repeat type of increment
			if('Time Point' == (string)$parameter->incrementtype)
				$unit = EIncrementUnit::MINUTES; //'minutes';
			else if('Time Point Hours' == (string)$parameter->incrementtype)
				$unit = EIncrementUnit::TIHRTLO; //'hours'; 'Time in hours relative to lights out';
			else if('Age In Days' == (string)$parameter->incrementtype)
				$unit = EIncrementUnit::AGE_IN_DAYS;
			else if('Date Time' == (string)$parameter->incrementtype)
				$unit = EIncrementUnit::NULL;
			else
				$unit = EIncrementUnit::NUMBER; //'number'; //Simple Repeat
			//if increment minimum is supplied create a record for it in increments table
			if((int)$parameter->incrementminimum > 0){
				$ii = $this->paramincrementmodel->insert(
					array(
						'pipeline_id' => $param['pipeline_id'],
						'procedure_id' => $param['procedure_id'],
						'parameter_id' => $paramid,
						'weight' => $weight,
						'is_active' => 1,
						'increment_string' => NULL,
						'increment_type' => ($unit == EIncrementUnit::NUMBER) ? EIncrementType::REPEAT : EIncrementType::DEFINED,
						'increment_unit' => $unit,
						'increment_min' => (strlen($parameter->incrementminimum) == 0) ? NULL : $parameter->incrementminimum,
						'deleted' => 0,
						'user_id' => $param['user_id'],
						'time_modified' => $param['time_modified']
					),
					ChangeLogger::ACTION_IMPORT
				);
				if(FALSE === $ii)
					die('Insertion halted. An error occured while trying to insert increment minimum for parameter ' . $paramid . PHP_EOL . ' ' . $increment);
				else 
					$insertedIncrements[$ii] = $ii;
			}
			//if there are actual increments then insert each of them into increments table
			foreach($increments AS $increment){
				$ii = $this->paramincrementmodel->insert(
					array(
						'pipeline_id' => $param['pipeline_id'],
						'procedure_id' => $param['procedure_id'],
						'parameter_id' => $paramid,
						'weight' => $weight,
						'is_active' => 1,
						'increment_string' => $increment,
						'increment_type' => ($unit == EIncrementUnit::NUMBER) ? EIncrementType::REPEAT : EIncrementType::DEFINED,
						'increment_unit' => $unit,
						'increment_min' => (strlen($parameter->incrementminimum) == 0) ? NULL : $parameter->incrementminimum,
						'deleted' => 0,
						'user_id' => $param['user_id'],
						'time_modified' => $param['time_modified']
					),
					ChangeLogger::ACTION_IMPORT
				);
				if(FALSE === $ii)
					die('Insertion halted. An error occured while trying to insert increment for parameter ' . $paramid . PHP_EOL . ' ' . print_r($increment, true));
				else
					$insertedIncrements[$ii] = $increment;
				$weight++;
			}
			
			//insert ontologies
			$this->load->model('parammptermmodel');
			$weight = 0;
			foreach($opontologies AS $ontology){
				//find option id
				$optionid = array_search($ontology['option'], $insertedOptions);
				if($optionid === FALSE) $optionid = NULL;
				//find increment id
				$incrementid = array_search($ontology['increment'], $insertedIncrements);
				if($incrementid === FALSE) $incrementid = NULL;
				//identify sex
				if(empty($ontology['sex'])) $sex = ESexType::NULL;
				else if($ontology['sex'] == 'Male') $sex = ESexType::MALE;
				else $sex = ESexType::FEMALE;
				//identify selection outcome id
				if($ontology['selectionoutcome'] == 'INCREASED') $outcome = ESelectionOutcome::INCREASED;
				else if($ontology['selectionoutcome'] == 'DECREASED') $outcome = ESelectionOutcome::DECREASED;
				else $outcome = ESelectionOutcome::ABNORMAL;
				//insert MP ontology
				$mi = $this->parammptermmodel->insert(
					array(
						'pipeline_id' => $param['pipeline_id'],
						'procedure_id' => $param['procedure_id'],
						'parameter_id' => $paramid,
						'mp_term' => $ontology['mpterm'],
						'mp_id' => $ontology['mpid'],
						'weight' => $weight,
						'option_id' => $optionid,
						'increment_id' => $incrementid,
						'sex' => $sex,
						'selection_outcome' => $outcome,
						'deleted' => 0,
						'user_id' => $param['user_id'],
						'time_modified' => $param['time_modified']
					),
					ChangeLogger::ACTION_IMPORT
				);
				
				if(FALSE === $mi) die('Insertion halted. An error occured while trying to insert MP Term for parameter ' . $paramid . PHP_EOL . var_export($ontology, TRUE));
				
				$weight++;
			}
			
			$paramcount++;
		}
		
		$content .= "<p>Successfully inserted $paramcount new parameters for Procedure " . anchor('impress/listParameters/' . $procedure->getId() . '/' . $pipeline->getId(), e($procedure->getItemName())) . ".</p>\n";
		$this->load->view('impress', array('content' => $content, 'title' => 'XML Import Complete'));
	}
}
