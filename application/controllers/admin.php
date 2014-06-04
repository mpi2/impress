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

class Admin extends CI_Controller
{
    private $_controller = null;
    private $_generalTitle = null;

    public function __construct()
    {
        parent::__construct();

        //update config from database
        $this->load->model('overridesettingsmodel');
        $this->overridesettingsmodel->updateRunningConfig();

        //load language file for tooltips in admin interface
        $this->lang->load('tooltips');
        $this->load->helper('language');
        $this->load->helper('tooltip');
        $this->load->helper('admin_flash');
        $this->load->helper('admin_breadcrumb');
        $this->load->helper('permission_denied');
        $this->load->helper('get_return_path_to');
        
        //Only logged in users may see the admin section
        if (false === User::hasPermission(User::ACCESS_ADMIN)) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'Attempting to access Admin pages without logging in ' . User::getUser('name'));
            $message = 'You do not have permission to access the admin interface. ';
            if (User::isLoggedIn()) {
                $message .= 'Please contact the administrator to seek further assitance.';
            } else {
                $message .= 'Are you ' . anchor($this->config->item('mousephenotypeurl') . 'user/login', 'logged in') . '?';
            }
            permissionDenied($message);
        }

        if (stristr($this->config->item('base_url'), 'mousephenotype')) {
            $this->load->helper('httpsify_url');
            $this->config->set_item('base_url', httpsify_url($this->config->item('base_url')));
        }

        $this->_controller = $this->router->class;
        $this->_generalTitle = '<h1>' . anchor($this->_controller, 'Administer IMPReSS', array('id' => 'adminimpress')) . '</h1>' . PHP_EOL;
        $this->_generalTitle .= $this->load->view('admin/toggleversiontriggering', null, true);

        //for flash memory when filling out forms
        $this->load->library('session');

        //to stop the http_referer missing warning message appearing when starting from scratch
        if ( ! isset($_SERVER['HTTP_REFERER'])) {
            $_SERVER['HTTP_REFERER'] = base_url() . $this->_controller;
        }
    }

    /**
     * List Pipelines
     */
    public function index()
    {
        $this->load->helper('item_flags');
        $content = $this->load->view('admin/listpipelines', array('controller' => $this->_controller, 'pipelines' => PipelinesFetcher::fetchAll(), 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb() . $content));
    }

    /**
     * Redirects to index
     * @see Admin::index()
     */
    public function pipeline()
    {
        redirect($this->_controller);
    }

    /**
     * Checks MP Terms are valid and up to date
     */
    public function checkmps()
    {
        $this->load->model('mptermcheckingmodel');

        $obsoleteMPs = $this->mptermcheckingmodel->checkAnyMPIdsObsolete();
        $obsoleteParams = array();
        if ( ! empty($obsoleteMPs))
            $obsoleteParams = $this->parametermodel->getByMPId($obsoleteMPs);

        $invalidMPs = $this->mptermcheckingmodel->checkInvalidIds();
        $invalidParams = array();
        if ( ! empty($invalidMPs))
            $invalidParams = $this->parametermodel->getByMPId($invalidMPs);

        $updatedParams = array();
        $updatedTerms = $this->mptermcheckingmodel->findUpdatedTerms();
        $updatedMPs = array_map(function($t) {return $t['id'];}, $updatedTerms);
        if (!empty($updatedMPs))
            $updatedParams = $this->parametermodel->getByMPId($updatedMPs);

        $content = $this->load->view(
            'admin/checkmps',
            array(
                'invalidMPs' => $invalidMPs,
                'invalidParams' => $invalidParams,
                'obsoleteMPs' => $obsoleteMPs,
                'obsoleteParams' => $obsoleteParams,
                'updatedMPs' => $updatedMPs,
                'updatedParams' => $updatedParams,
                'updatedTerms' => $updatedTerms,
                'controller' => $this->_controller
            ),
            true
        );

        $this->load->view('impress', array(
            'content' => $this->_generalTitle . admin_breadcrumb() . $content,
            'controller' => $this->_controller,
            'title' => 'Invalid and Obsolete MP Ontologies in IMPReSS'
        ));
    }

    /**
     * List Procedures
     */
    public function procedure($pipelineId = null)
    {
        $pipeline = new Pipeline($pipelineId);
        $content = '';

        if ($pipeline->exists()) {
            $this->load->helper('item_flags');
            $content = $this->load->view(
                'admin/listprocedures',
                array(
                    'pipeline' => $pipeline,
                    'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()),
                    'flash' => getFlashMessage(),
                    'controller' => $this->_controller
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The pipeline selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Pipeline id does not exist');
        }

        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipeline->getId())) . $content));
    }

    /**
     * List Parameters
     */
    public function parameter($procedureId = null, $pipelineId = null)
    {
        $procedure = new Procedure($procedureId, $pipelineId);
        $content = '';

        if ($procedure->exists()) {
            $this->load->helper('item_flags');
            $content = $this->load->view(
                'admin/listparameters',
                array(
                    'parameters' => ProcedureHasParameters::fetchAll($procedure->getId()),
                    'procedure' => $procedure,
                    'pipelineId' => $procedure->getPipelineId(),
                    'flash' => getFlashMessage(),
                    'controller' => $this->_controller
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The procedure selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Procedure id does not exist');
        }

        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $procedure->getPipelineId(), 'procedure_id' => $procedure->getId())) . $content));
    }

    /**
     * List Increments
     */
    public function increment($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        $parameter = new Parameter((int) $parameterId, (int) $procedureId);
        $content = '';

        if ($parameter->exists()) {
            $this->load->helper('tick_or_cross');
            $content = $this->load->view(
                'admin/listincrements',
                array(
                    'parameter' => $parameter,
                    'procedureId' => (int) $procedureId,
                    'pipelineId' => (int) $pipelineId,
                    'flash' => getFlashMessage(),
                    'controller' => $this->_controller
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The parameter selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Parameter id does not exist');
        }

        $this->load->view(
            'impress',
            array(
                'content' => $this->_generalTitle . admin_breadcrumb(array(
                    'pipeline_id' => $pipelineId,
                    'procedure_id' => $procedureId,
                    'parameter_id' => $parameter->getId()
                )) . $content
            )
        );
    }

    /**
     * List Options
     */
    public function option($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        $parameter = new Parameter((int) $parameterId, (int) $procedureId);
        $content = '';

        if ($parameter->exists()) {
            $this->load->helper('tick_or_cross');
            $content = $this->load->view(
                'admin/listoptions',
                array(
                    'parameter' => $parameter,
                    'procedureId' => (int) $procedureId,
                    'pipelineId' => (int) $pipelineId,
                    'flash' => getFlashMessage(),
                    'controller' => $this->_controller
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The parameter selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Parameter id does not exist');
        }

        $this->load->view(
            'impress',
            array(
                'content' => $this->_generalTitle . admin_breadcrumb(array(
                    'pipeline_id' => $pipelineId,
                    'procedure_id' => $procedureId,
                    'parameter_id' => $parameter->getId()
                )) . $content
            )
        );
    }

    /**
     * List Ontology Groups
     */
    public function ontologyGroup($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        $parameter = new Parameter($parameterId, $procedureId);
        $content = '';

        if ($parameter->exists()) {
            $this->load->helper('tick_or_cross');
            $content = $this->load->view(
                'admin/listontologygroups',
                array(
                    'parameter' => $parameter,
                    'procedureId' => (int) $procedureId,
                    'pipelineId' => (int) $pipelineId,
                    'flash' => getFlashMessage(),
                    'controller' => $this->_controller
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The parameter selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Parameter id does not exist');
        }

        $this->load->view(
            'impress',
            array(
                'content' => $this->_generalTitle . admin_breadcrumb(array(
                    'pipeline_id' => $pipelineId,
                    'procedure_id' => $procedureId,
                    'parameter_id' => $parameter->getId()
                )) . $content
            )
        );
    }

    /**
	* Recycle Bin
	*/
	public function recyclebin($m = null, $pipelineId = null, $procedureId = null, $parameterId = null)
	{
		$model = $m . 'deletedmodel';
		if($this->load->model($model) === false || false === ($this->$model instanceof IRecyclable))
			die('Error. Invalid model supplied');

		// if($this->config->item('delete_mode') != 'hard')
			// permissionDenied('Error: Invalid mode. You must enable hard delete mode to view this page.');

		$this->load->helper('form');
		$this->load->helper('titlize');
		$content = '';

		//generate a suitable heading title for the recycle bin page
		$title = 'Recycle Bin: Deleted ';
		switch ($m) {
			case 'sop':            $title .= 'Protocol'; break;
			case 'section':        $title .= 'Protocol Section'; break;
			case 'paramoption':    $title .= 'Option'; break;
			case 'parammpterm':    $title .= 'MP Term'; break;
			case 'paramincrement': $title .= 'Increment'; break;
			case 'parameqterm':    $title .= 'EQ Term'; break;
			case 'paramontologyoption':  $title .= 'Ontology Option'; break;
			default:               $title .= ucfirst($m); break;
		}
		$title .= 's';

		//respond to buttons clicked on the form
		$message = '';
		$itemsSelected = (isset($_POST['item_id'])) ? count($this->input->post('item_id')) : 0;
		$count = 0;
		if ( ! empty($_POST['buttonclicked']) && $itemsSelected == 0) {
			$message .= '<p class="error">Please select an item before clicking a button.</p>';
		} else if ( ! empty($_POST['buttonclicked']) && $_POST['buttonclicked'] == 'purge') {
			foreach ($this->input->post('item_id') as $id)
				$count += $this->$model->purgeRecord($id);
			$message .= '<p class="success">Successfully purged ' . $count . ' item(s) from the database.</p>';
		} else if ( ! empty($_POST['buttonclicked']) && $_POST['buttonclicked'] == 'restore') {
			$destination['pipeline_id']  = $this->input->post('pipeline_id');
			$destination['procedure_id'] = $this->input->post('procedure_id');
			$destination['parameter_id'] = $this->input->post('parameter_id');
			$error = false;
			switch ($m) {
				case 'pipeline':
				case 'paramontologyoption':
					break;
				case 'procedure':
					if(empty($destination['pipeline_id']))
						$error = true;
					break;
				case 'parameter':
				case 'sop':
				case 'section':
					if(empty($destination['pipeline_id']) || empty($destination['procedure_id']))
						$error = true;
					break;
				default:
					if(empty($destination['pipeline_id'])  ||
					   empty($destination['procedure_id']) ||
					   empty($destination['parameter_id']))
						$error = true;
			}
			if ($error) {
				$message .= '<p class="error">Please select a valid pathway to restore your item to.</p>';
			} else {
				foreach ($this->input->post('item_id') as $id) {
					$count += (int)(bool)$this->$model->restore($id, array(
						'pipeline_id'  => $this->input->post('pipeline_id'),
						'procedure_id' => $this->input->post('procedure_id'),
						'parameter_id' => $this->input->post('parameter_id')
					));
				}
				if ($count == 0)
					$message .= '<p class="error">An error occured. Either the location you are restoring to is deprecated or does not exist or something else is wrong.</p>';
				else if ($count != $itemsSelected)
					$message .= '<p class="error">Warning: Some items could not be restored. Please contact the administrator.</p>';
				else
					$message .= '<p class="success">Successfully restored ' . $count . '/' . $itemsSelected . ' item(s) into the database.</p>';
			}
		}
		
		//display the form
		$content .= $this->load->view(
			'admin/recyclebin',
			array(
				'model'   => $m,
				'items'   => $this->$model->fetchAll(),
				'fields'  => $this->$model->getRecyclableFields(),
				'title'   => $title,
				'message' => $message,
				'controller' => $this->_controller
			),
			true
		);

		$this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array(
			'pipeline_id' => $pipelineId,
			'procedure_id' => $procedureId,
			'parameter_id' => $parameterId
		)) . $content, 'controller' => $this->_controller));
	}	
	
	/**
	* The item we want to clone is called Source (src) and the location we want
	* to clone it to is called Destination (dest).
	* Given a source item in the item_id part of the URI, it will identify the
	* original pathway of the source item, and allow the user to select where
	* they wish to clone it to. The destination can be preseeded by giving the
	* pipline_id in the URI.
	*/
	public function cloneProcedure()
	{
            $assoc = $this->uri->uri_to_assoc(3);
            $srcId = (int)@$assoc['item_id'];
            $pipelineId = (int)@$assoc['pipeline_id'];
            $srcPipeline = new Pipeline();
            $srcProcedure = new Procedure($srcId);
            $returnLocation = null;
            $content = '';

            if ($srcProcedure->exists()) {
                $this->load->model('originalpathwaysmodel');
                $origPathways = $this->originalpathwaysmodel->getPathwaysByOrigin(array('procedure_id' => $srcProcedure->getId()));
                if (empty($origPathways)) {
                    $content = '<p>A procedure can only be cloned if it contains one or more of its own Parameters.</p>'
                             . '<p>You should instead create a new Procedure and import the Parameters you want.</p>';
                } else {
                    $srcPipeline = new Pipeline($origPathways[0]['pipeline_id']);
                    $this->load->library('form_validation');
                    $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
                    $this->form_validation->set_rules('destPipeline', 'Pipeline', 'required|callback_admin_pipeline_exists');
                    $this->form_validation->set_rules('nvrelation', 'Relationship', 'required');
                    if ( ! $srcProcedure->getType()->exists())
                        $this->form_validation->set_rules('destType', 'New Procedure Type', 'required');
                    if ($this->form_validation->run() === false) {
                        $content = $this->load->view(
                            'admin/cloneprocedureform',
                            array(
                                'srcPipeline' => $srcPipeline,
                                'srcProcedure' => $srcProcedure,
                                'destPipelineId' => $pipelineId,
                                'errors' => validation_errors(),
                                'flash' => getFlashMessage(),
                                'controller' => $this->_controller
                            ),
                            true
                        );
                    } else {
                        $source = array(
                            'pipeline_id' => ($pipelineId) ? $pipelineId : $srcPipeline->getId(),
                            'procedure_id' => $srcProcedure->getId()
                        );
                        $destination = array(
                            'pipeline_id' => $this->input->post('destPipeline'),
                            'type' => $this->input->post('destType'),
                            'cloneProtocol' => (bool) $this->input->post('cloneProtocol'),
                            'cloneParameters' => (bool) $this->input->post('cloneParameters'),
                            'nvrelation' => $this->input->post('nvrelation'),
                            'nvrelationdescription' => (empty($_POST['nvrelationdescription'])) ? null : trim($this->input->post('nvrelationdescription'))
                        );
                        if ( ! $this->proceduremodel->cloneProcedure($source, $destination)) {
                            $message = '<p>An error occured. Failed to clone this item. Please contact the administrator.</p>';
                            ImpressLogger::log(ImpressLogger::WARNING, 'Cloning of Procedure failed.');
                            displayFlashMessage($message, false, $returnLocation);
                        } else {
                            $destPipeline = new Pipeline($destination[PipelineModel::PRIMARY_KEY]);
                            $message = '<p>Successfully cloned Procedure ' . $srcProcedure->getItemKey() . ' ' . e($srcProcedure->getItemName())
                                     . ' to Pipeline ' . $destPipeline->getItemKey() . ' ' . e($destPipeline->getItemName()) . '.</p>';
                            ImpressLogger::log(ImpressLogger::INFO, $message);
                            $returnLocation = getReturnPathTo('procedure', $destination);
                            displayFlashMessage($message, true, $returnLocation);
                        }
                    }
                }
            } else {
                $content = '<p>An error occured. Procedure not found.</p>';
            }

            $this->load->view(
                    'impress',
                    array(
                        'content' => $this->_generalTitle . admin_breadcrumb(array(
                            'pipeline_id' => $srcPipeline->getId(),
                            'procedure_id' => $srcProcedure->getId()
                        )) . $content
                    )
            );
	}
	
	/**
	* The item we want to clone is called Source (src) and the location we want
	* to clone it to is called Destination (dest).
	* Given a source item in the item_id part of the URI, it will identify the
	* original pathway of the source item, and allow the user to select where
	* they wish to clone it to. The destination can be preseeded by giving the
	* pipline_id and procedure_id in the URI.
	*/
	public function cloneParameter()
	{
		$assoc = $this->uri->uri_to_assoc(3);
		$srcId = (isset($assoc['item_id'])) ? $assoc['item_id'] : null;
		$destPipelineId = (isset($assoc['pipeline_id'])) ? $assoc['pipeline_id'] : null;
		$destProcedureId = (isset($assoc['procedure_id'])) ? $assoc['procedure_id'] : null;
		$srcPipeline = new Pipeline();
		$srcProcedure = new Procedure();
		$srcParameter = new Parameter($srcId);
		$returnLocation = null;
		$content = '';
		
		if ($srcParameter->exists()) {
			$this->load->model('originalpathwaysmodel');
			$origPathways = $this->originalpathwaysmodel->getPathwaysByOrigin(array('parameter_id'=>$srcParameter->getId()));
			if (empty($origPathways)) {
				$content .= '<p>Error. Failed to load original pathway.</p>';
			} else {
				$srcPipeline = new Pipeline($origPathways[0]['pipeline_id']);
				$srcProcedure = new Procedure($origPathways[0]['procedure_id']);
				$this->load->library('form_validation');
				$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
				$this->form_validation->set_rules('destPipeline', 'Pipeline', 'required|callback_admin_pipeline_exists');
				$this->form_validation->set_rules('destProcedure', 'Procedure', 'required|callback_admin_procedure_exists');
				$this->form_validation->set_rules('nvrelation', 'Relationship', 'required');
				if ($this->form_validation->run() === false)
				{
					$content .= $this->load->view(
						'admin/cloneparameterform',
						array(
							'srcPipeline'     => $srcPipeline,
							'srcProcedure'    => $srcProcedure,
							'srcParameter'    => $srcParameter,
							'destPipelineId'  => $destPipelineId,
							'destProcedureId' => $destProcedureId,
							'errors' 		  => validation_errors(),
							'flash'			  => getFlashMessage(),
							'controller'      => $this->_controller
						),
						true
					);
				}
				else
				{
					$source = array(
						'pipeline_id'  => $srcPipeline->getId(),
						'procedure_id' => $srcProcedure->getId(),
						'parameter_id' => $srcParameter->getId()
					);
					$destination = array(
						'pipeline_id'     => $this->input->post('destPipeline'),
						'procedure_id'    => $this->input->post('destProcedure'),
						'cloneMPs'        => (bool)$this->input->post('cloneMPs'),
						'cloneEQs'        => (bool)$this->input->post('cloneEQs'),
						'cloneOptions'    => (bool)$this->input->post('cloneOptions'),
						'cloneIncrements' => (bool)$this->input->post('cloneIncrements'),
						'nvrelation'      => $this->input->post('nvrelation'),
						'nvrelationdescription' => (empty($_POST['nvrelationdescription'])) ? null : trim($this->input->post('nvrelationdescription'))
					);
					if ( ! $this->parametermodel->cloneParameter($source, $destination)) {
						$message = '<p>An error occured. Failed to clone this item. Please contact the administrator.</p>';
						ImpressLogger::log(ImpressLogger::WARNING, 'Cloning of Parameter failed.');
						displayFlashMessage($message, false, $returnLocation);
					} else {
						$destPipeline = new Pipeline($destination['pipeline_id']);
						$destProcedure = new Procedure($destination['procedure_id']);
						$message = '<p>Successfully cloned Parameter ' . $srcParameter->getItemKey() . ' ' . e($srcParameter->getItemName())
								 . ' to Procedure ' . $destProcedure->getItemKey() . ' ' . e($destProcedure->getItemName()) . ' in'
								 . ' Pipeline ' . $destPipeline->getItemKey() . ' ' . e($destPipeline->getItemName()) . '.</p>';
						ImpressLogger::log(ImpressLogger::INFO, $message);
                                                $returnLocation = getReturnPathTo('parameter', $destination);
						displayFlashMessage($message, true, $returnLocation);
					}
				}
			}
		} else {
			$content .= '<p>An error occured. Procedure not found.</p>';
		}
		
		$this->load->view(
			'impress',
			array(
				'content' => $this->_generalTitle . admin_breadcrumb(array(
					'pipeline_id' => $srcPipeline->getId(),
					'procedure_id' => $srcProcedure->getId(),
					'parameter_id' => $srcParameter->getId()
				)) . $content
			)
		);
	}

	/**
	* Replace a parameter with a new version of itself in it's place
	*/
	public function replaceParameterWithNewVersion()
	{
		$assoc = $this->uri->uri_to_assoc(3);
		$parameterId = (isset($assoc['parameter_id'])) ? $assoc['parameter_id'] : die('Parameter id not supplied');
		$parameter = new Parameter($parameterId);
		$destPipelineId = (isset($assoc['pipeline_id'])) ? $assoc['pipeline_id'] : die('Pipeline not supplied');
		$destPipeline = new Pipeline($destPipelineId);
		$destProcedureId = (isset($assoc['procedure_id'])) ? $assoc['procedure_id'] : die('Procedure not supplied');
		$destProcedure = new Procedure($destProcedureId);
                $oldOptionId = (isset($assoc['option_id'])) ? (int)$assoc['option_id'] : null;
		$location = $assoc;
		unset($location['option_id']);
		$content = $this->_generalTitle . admin_breadcrumb($location);
		if ( ! ($parameter->exists() && $destPipeline->exists() && $destProcedure->exists()))
		{
			$content .= 'Error. Pipeline, Procedure or Parameter not found.';
		}
		else
		{
		
			$this->load->library('form_validation');
			
			if ( ! isset($_POST['nvsubmitbuttonclicked'])) {
				$content .= validation_errors();
				
				$content .= $this->load->view('admin/replaceparameterversionform', array(
					'controller'  => $this->_controller,
					'parameter'   => $parameter,
					'procedure'   => $destProcedure,
					'pipeline'    => $destPipeline,
					'oldoptionid' => $oldOptionId,
					'itemType'    => (isset($assoc['option_id'])) ? 'paramoption' : 'parameter'
				), true);
			} else {
                                $arr['deleteolditem'] = (bool)$this->input->post('deleteolditem');
                                $arr['oldoptionid'] = $this->input->post('oldoptionid'); //(isset($assoc['option_id'])) ? (int)$assoc['option_id'] : null;
				$arr['nvpipeline'] = $this->input->post('nvpipeline'); //$destPipeline->getId();
				$arr['nvprocedure'] = $this->input->post('nvprocedure'); //$destProcedure->getId();
				$arr['nvrelation'] = $this->input->post('nvrelation'); //ERelationType::EQUIVALENT;
				$arr['nvrelationdescription'] = 'Versioning Copy' . (( ! empty($_POST['nvrelationdescription'])) ? ': ' . $this->input->post('nvrelationdescription') : '');
				$arr['nvuseoldpipelinekey'] = (isset($_POST['nvuseoldpipelinekey'])) ? 1 : 0;
				$arr['nvforkprocedure'] = (isset($_POST['nvforkprocedure'])) ? 1 : 0;
				
				$nv = $this->parametermodel->createNewVersionAndDeleteOldItem($parameterId, $arr);
				if ($nv) {
					$message = 'Successfully created a new version of the item';
					displayFlashMessage($message, true, getReturnPathTo('parameter', $assoc));
				} else {
					$message = 'An error occured while attempting to create a new version of the item';
					displayFlashMessage($message, false, getReturnPathTo('parameter', $assoc));
				}
			}
		
		}
		
		$this->load->view('impress', array('content' => $content));
	}
	
    /**
     * Manage Procedure Types
     */
    public function manageProcedureTypes($pipelineId = null)
    {
        $this->load->model('proceduretypemodel');
        $content = $this->load->view('admin/listproceduretypes', array('controller' => $this->_controller, 'proctypes' => $this->proceduretypemodel->fetchAll(), 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipelineId)) . $content));
    }

    /**
     * Manage Procedure Weeks
     */
    public function manageProcedureWeeks($pipelineId = null)
    {
        $this->load->model('procedureweekmodel');
        $content = $this->load->view('admin/listprocedureweeks', array('controller' => $this->_controller, 'procweeks' => ProcedureWeeksFetcher::fetchAll(), 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipelineId)) . $content));
    }

    /**
     * Manage Glossary
     */
    public function manageGlossary()
    {
        $this->load->model('glossarymodel');
        $content = $this->load->view('admin/listglossary', array('controller' => $this->_controller, 'glossary' => $this->glossarymodel->fetchAll(), 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb() . $content));
    }

    /**
     * Manage Parameter Units
     */
    public function manageUnits($pipelineId = null, $procedureId = null)
    {
        $this->load->model('unitmodel');
        $content = $this->load->view('admin/listunits', array('controller' => $this->_controller, 'units' => $this->unitmodel->fetchAll(), 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipelineId, 'procedure_id' => $procedureId)) . $content));
    }

    /**
     * Manage Ontology Groups
     */
    public function manageOntologyGroups($pipelineId = null, $procedureId = null, $parameterId = null)
    {
        $this->load->model('ontologygroupmodel');
        $origin = array('pipeline_id' => $pipelineId, 'procedure_id' => $procedureId, 'parameter_id' => $parameterId);
        $groups = array();
        foreach ($this->ontologygroupmodel->fetchAll() as $group)
            $groups[] = new OntologyGroup($group[OntologyGroupModel::PRIMARY_KEY]);
        $content = $this->load->view('admin/listontologygroups', array('controller' => $this->_controller, 'groups' => $groups, 'origin' => $origin, 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb($origin) . $content));
    }

    /**
     * Manage Releases
     */
    public function manageReleases()
    {
        $this->load->model('changelogmodel');
        $content = $this->_generalTitle . admin_breadcrumb() . '<h2>Manage Releases</h2>';
        if (User::isSuperAdmin()) {
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<div id="flashfailure">', '</div>');
            $this->form_validation->set_rules('date', 'Date', 'callback_managereleases_validateDate');
            if ($this->form_validation->run() === false) {
                $content .= validation_errors();
            } else {
                $message = $this->input->post('message');
                $success = $this->changelogmodel->insertRelease((empty($message)) ? null : $message, $this->input->post('date'));
                if ($success)
                    $content .= '<div id="flashsuccess">Successfully created a new release.</div>';
                else
                    $content .= '<div id="flashfailure">An error occured while trying to insert the Release.</div>';
            }
            $content .= $this->load->view('admin/releasesform', array('controller' => $this->_controller), true);
        }
        $releases = $this->changelogmodel->getReleases();
        $content .= $this->load->view('admin/listreleases', array('controller' => $this->_controller, 'releases' => $releases), true);

        $this->load->view('impress', array('content' => $content, 'title' => 'Manage Releases'));
    }

    /**
     * Delete Release
     * @deprecated
     */
    public function deleteRelease($id = null)
    {
        if (User::isSuperAdmin()) {
            $this->load->model('changelogmodel');
            $this->changelogmodel->deleteRelease($id);
        }
        redirect($this->_controller . '/manageReleases');
    }

    /**
     * List Ontologies
     */
    public function ontology($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        $parameter = new Parameter((int) $parameterId, (int) $procedureId);
        $content = '';

        if ($parameter->exists()) {
            $content = $this->load->view(
                'admin/listontologies',
                array(
                    'parameter' => $parameter,
                    'procedureId' => (int) $procedureId,
                    'pipelineId' => (int) $pipelineId,
                    'flash' => getFlashMessage(),
                    'controller' => $this->_controller
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The parameter selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Parameter id does not exist');
        }

        $this->load->view(
            'impress',
            array(
                'content' => $this->_generalTitle . admin_breadcrumb(array(
                    'pipeline_id' => $pipelineId,
                    'procedure_id' => $procedureId,
                    'parameter_id' => $parameter->getId()
                )) . $content
            )
        );
    }

    /**
     * SOP Section Text + SOP PDF + Section Titles
     */
    public function sop($procedureId = null, $pipelineId = null)
    {
        $proc = new Procedure((int) $procedureId, (int) $pipelineId);
        $content = '';

        if ($proc->exists()) {
            //get the sop
            $sop = $proc->getSOP();

            //page title
            $content = "<fieldset><legend>Protocol</legend>\n"
                     . "<h2>Protocol for Procedure: " . anchor(base_url() . $this->_controller . '/procedure/' . $pipelineId, e($proc->getItemName()))
                     . " <span class='procedurekey'>" . $proc->getItemKey() . "</span></h2>\n";

            if ( ! $sop->exists()) {
                $content .= '<p>';
                $content .= anchor(
                    $this->_controller . '/iu/model/sop/procedure_id/' . $proc->getId() . '/procedure_id/' . $procedureId . '/pipeline_id/' . $pipelineId, 'Create a new Protocol', array('class' => 'admincreate')
                );
                $content .= '</p>';
            } else {
                //display protocol information
                //the PDF path + file name of the Protocol
                $pdffile = $this->config->item('pdfpath') . $proc->getItemKey() . '.pdf';
                $content .= $this->load->view(
                    'admin/protocoldetails',
                    array(
                        'sop' => $sop,
                        'pipeline_id' => $proc->getPipelineId(),
                        'procedure_id' => $proc->getId(),
                        'pdffile' => $pdffile,
                        'controller' => $this->_controller,
                        'flash' => getFlashMessage()
                    ),
                    true
                );
            }

            $content .= '</fieldset>';
        } else {
            $content = '<p>An error occured. The procedure selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Procedure id does not exist');
        }

        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipelineId, 'procedure_id' => $proc->getId())) . $content));
    }

    /**
     * SOP Section Titles
     */
    public function sectiontitles($pipelineId = null, $procedureId = null)
    {
        $this->load->model('sectiontitlemodel');
        $content = $this->load->view('admin/listsectiontitles', array('sectionTitles' => $this->sectiontitlemodel->fetchAll(), 'controller' => $this->_controller, 'flash' => getFlashMessage()), true);
        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipelineId, 'procedure_id' => $procedureId)) . $content));
    }

    /**
     * Delete SOP PDF
     */
    public function deletepdf($procId = null, $pipId = null)
    {
        $proc = new Procedure((int) $procId, (int) $pipId);
        $content = '<h1>Deletion of SOP PDF</h1>';

        //check permissions
        if (User::hasPermission(User::DELETE_ITEM)) {
            //continue deletion
        } else if (User::hasPermission(User::DELETE_OWN_ITEM)) {
            if ($proc->getUserId() != User::getId()) {
                ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to delete PDF');
                permissionDenied('Permission denied. You are not permitted to delete other people\'s items.');
            }
        } else {
            //User not allowed to delete anything
            permissionDenied('Permission denied. You are not allowed to delete any items.');
        }

        if ($proc->exists()) {
            $returnLocation = $this->_controller . '/sop/' . $proc->getId();
            if ($proc->isDeprecated() && $this->config->item('modify_deprecated') === false) {
                $message = '<p>You cannot delete the Protocol PDF file of a deprecated Procedure.</p>';
                ImpressLogger::log(
                    array(
                        'type' => ImpressLogger::WARNING,
                        'message' => 'Failed to delete Protocol PDF for Deprecated Procedure ' . $procId,
                        'item' => 'protocol pdf',
                        'item_id' => $procId,
                        'action' => ImpressLogger::ACTION_DELETE
                    )
                );
                displayFlashMessage($message, false, $returnLocation);
            } else {
                $proc->getSOP()->deletePDF();
                redirect($returnLocation);
            }
        } else {
            $content .= '<p>An error occured. Invalid Procedure given.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Procedure id does not exist');
        }

        $this->load->view('impress', array('content' => $content));
    }

    /**
     * Upload SOP PDF
     */
    public function uploadpdf($procId = null)
    {
        if (User::hasPermission(User::CREATE_ITEM) === false) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to upload a PDF (Create Items)');
            permissionDenied('Permission denied. You are not permitted to upload a file.');
        }

        $proc = new Procedure((int) $procId);
        $content = '<h1>Upload of SOP PDF</h1>';

        if ($proc->exists()) {
            $settings['upload_path'] = $this->config->item('pdfpath');
            $settings['allowed_types'] = 'pdf';
            $settings['max_size'] = $this->config->item('pdfmaxuploadsize');
            $settings['overwrite'] = FALSE;
            $settings['file_name'] = $proc->getItemKey() . '.pdf';
            $this->load->library('upload', $settings);
            if ( ! $this->upload->do_upload('pdffile')) {
                $content .= '<p>An error occured:</p>' . $this->upload->display_errors();
                ImpressLogger::log(
                    array(
                        'type' => ImpressLogger::ERROR,
                        'message' => 'Failed to upload a new PDF for ' . $proc->getItemKey() . ': ' . $this->upload->display_errors(),
                        'item' => 'sop',
                        'item_id' => $proc->getSOP()->getId(),
                        'action' => ImpressLogger::ACTION_CREATE,
                        'alsoerrorlogit' => true
                    )
                );
            } else {
                ImpressLogger::log(
                    array(
                        'type' => ImpressLogger::INFO,
                        'message' => 'Uploaded a new PDF for Procedure ' . $proc->getItemKey(),
                        'item' => 'sop',
                        'item_id' => $proc->getSOP()->getId(),
                        'action' => ImpressLogger::ACTION_CREATE
                    )
                );
                redirect($this->_controller . '/sop/' . $proc->getId());
                exit;
            }
        } else {
            $content .= '<p>An error occured. Invalid Procedure given.';
            ImpressLogger::log(ImpressLogger::WARNING, 'Procedure id does not exist');
        }

        $this->load->view('impress', array('content' => $content));
    }

    /**
	* Compare Revisions
	* @todo sort out return location
        * @todo sort out the pipeline id lookup for procedure and fix undefined week, visible, active, etc warnings
	*/
	public function comparerevisions($m = null, $id = null)
        {
            $uri = $this->uri->uri_to_assoc(3, array('pipeline_id', 'procedure_id', 'parameter_id'));
            $content = '';

            //set return location
            $returnLocation = getFormReturnLocation();

            if (empty($m) || empty($id)) {
                $content .= '<p>Error: Model and ID must be supplied</p>';
                ImpressLogger::log(ImpressLogger::WARNING, 'Invalid arguments when trying to compare revisions: model and/or id missing');
            }

            $from = $this->input->post('from');
            $to = $this->input->post('to');

            if ($from == $to)
                $content .= '<p>Error: You cannot compare a version with itself.</p>';

            //list of fields that need special display formatting. By default, fields are Uppercased for first letter in words and underscores are replaced with spaces. Fields starting with is_ are treated as boolean by default
            $common = array('visible' => array('is_bool' => true), 'active' => array('is_bool' => true), 'deprecated' => array('is_bool' => true), 'internal' => array('is_bool' => true), 'deleted' => array('is_bool' => true));
            $formatFields = array(
                'parameter' => array_merge(
                    $common,
                    array(
                        'qc_check' => array('is_bool' => true, 'title' => 'QC Check Enabled'),
                        'qc_min'   => array('title' => 'QC Minimum Value'),
                        'qc_max'   => array('title' => 'QC Maximum Value'),
                        'qc_notes' => array('title' => 'QC Notes')
                    )
                ),
                'procedure' => array(),
                'pipeline'  => $common
            );

            //list of fields that need to be hidden from displaying @todo review this
            $hideFields = array(
                'parameter' => array('old_parameter_key'),
                'procedure' => array_merge(
                    array_keys($common),
                    array(
                        'old_procedure_key', 'week', 'is_visible',
                        'is_active', 'is_deprecated', 'is_internal',
                        'is_deleted', 'is_mandatory', 'min_females',
                        'min_males', 'min_animals'
                    )
                )
            );

            if (empty($content)) {
                $model = $m . 'model';

                //load the model, get the revisions and assign to $from_arr and $to_arr where they match
                $this->load->model($model);
                $revs = $this->$model->getRevisionsById($id);
                $from_arr = array();
                $to_arr = array();
                foreach ($revs AS $rev) {
                    if (!array_key_exists('id', $rev))
                        $rev['id'] = '';
                    if (!array_key_exists('deleted', $rev))
                        $rev['deleted'] = '0';
                    if ($rev['id'] == $from)
                        $from_arr = $rev;
                    else if ($rev['id'] == $to)
                        $to_arr = $rev;
                }

                $content .= $this->load->view('admin/comparerevisionsmod', array(
                    'controller' => $this->_controller,
                    'model' => $m,
                    'from' => $from_arr,
                    'to' => $to_arr,
                    'id' => $id,
                    'formatFields' => (array_key_exists($m, $formatFields)) ? $formatFields[$m] : array(),
                    'hideFields' => (array_key_exists($m, $hideFields)) ? $hideFields[$m] : array(),
                    'flash' => getFlashMessage(),
                    'pipeline_id' => $uri['pipeline_id'],
                    'procedure_id' => $uri['procedure_id'],
                    'parameter_id' => $uri['parameter_id'],
                    'hideUnchanged' => true
                ), true);
            }

            $this->load->view('impress', array('content' => $this->_generalTitle . $content));
        }

	/**
	* Revert revisions
	*/
	public function revertrevision($m, $id, $revId)
	{
		if(User::hasPermission(User::REVERT_VERSION) === FALSE){
			ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to revert revisions');
			permissionDenied('Permission denied. You are not permitted to revert a revision.');
		}

		//set 3Ps for logging
		$uri = $this->uri->uri_to_assoc(2, array('pipeline_id','procedure_id','parameter_id'));
		if ($m == 'parameter') {
			$uri['parameter_id'] = $id;
		} else if ($m == 'procedure') {
			$uri['parameter_id'] = null;
			$uri['procedure_id'] = $id;
		} else if ($m == 'pipeline') {
			$uri['parameter_id'] = null;
			$uri['procedure_id'] = null;
			$uri['pipeline_id']  = $id;
		}

		$content = '';

		if(empty($m) || empty($id) || empty($revId)){
			ImpressLogger::log(ImpressLogger::WARNING, 'Invalid arguments when trying to revert revisions: model, id and/or revision id missing');
			$content = '<p>Error: Model, Id and Revision Id required</p>';
		}

		//set return location
		$returnLocation = getFReturnLocation();

		if(empty($content)){
			$model = $m . 'model';

			if($this->load->model($model) !== FALSE){

				$revs = $this->$model->getRevisionsById($id);
				$from_arr = array_shift($revs);
				$from = array_values($from_arr);
				$fromId = $from[2];
				$to_arr = array();
				foreach($revs AS $rev){
					if($rev['id'] == $revId)
						$to_arr = $rev;
				}
				unset($from, $revs);

				if($this->$model->revert($id, $revId, $uri)){
					$content = $message = '<p>Version successfully reverted to current.</p>';
					ImpressLogger::log(
						array(
							'type'    => ImpressLogger::INFO,
							'message' => 'Reverted ' . $m . ', id: ' . $fromId . ', from revision ' . $from_arr["major_version"] . '.' . $from_arr["minor_version"] . ' to ' . $to_arr["major_version"] . '.' . $to_arr["minor_version"],
							'item'    => $m,
							'item_id' => $fromId,
							'action'  => ImpressLogger::ACTION_VERSION
						)
					);
					displayFlashMessage($message, TRUE, $returnLocation);
				}else{
					$content = $message = '<p>Failed to revert revision id(' . $revId . ') into the model ' . $m . ' at row ' . $id . '. Please contact the administrator.</p>';
					ImpressLogger::log(
						array(
							'type'    => ImpressLogger::ERROR,
							'message' => 'Reverted ' . $m . ', id: ' . $fromId . ', from revision ' . $from_arr["major_version"] . '.' . $from_arr["minor_version"] . ' to ' . $to_arr["major_version"] . '.' . $to_arr["minor_version"],
							'item'    => $m,
							'item_id' => $fromId,
							'action'  => ImpressLogger::ACTION_VERSION
						)
					);
					displayFlashMessage($message, FALSE, $returnLocation);
				}

			}
			else{
				$content = '<p>Error: Invalid model supplied.</p>';
				ImpressLogger::log(ImpressLogger::WARNING, 'Invalid model given when trying to revert revisions');
			}
		}

		$this->load->view('impress', array('content' => $this->_generalTitle . $content));
	}

	private function _revisions($m, $id, $origin)
	{
		if(empty($m) || empty($id)){
			ImpressLogger::log(ImpressLogger::WARNING, 'Invalid arguments given for model and/or id when trying to obtain revision information');
			return 'Error: model and id required';
		}

		$model = $m . 'model';
		$id = (int) $id;

		if($this->load->model($model) === FALSE){
			ImpressLogger::log(ImpressLogger::WARNING, 'Invalid model argument supplied. Model not found when trying to obtain revision information');
			return 'Error: Invalid model supplied';
		}
		$revs = $this->$model->getRevisionsById($id);

		if(count($revs) > 1){
			$url = base_url() . $this->_controller . "/comparerevisions/$m/$id/pipeline_id/" . $origin['pipeline_id'] . "/procedure_id/" . $origin['procedure_id'];
			$content = "<form method='post' action='$url'>Compare changes from version <select name='from'>\n";
			foreach($revs AS $rev)
				$content .= '<option value="' . (isset($rev['id']) ? $rev['id'] : '') . '">' . (int)$rev['major_version'] . '.' . $rev['minor_version'] . '</option>' . PHP_EOL;
			$content .= '</select> to <select name="to">';
			foreach($revs AS $rev)
				$content .= '<option value="' . (isset($rev['id']) ? $rev['id'] : '') . '">' . (int)$rev['major_version'] . '.' . $rev['minor_version'] . '</option>' . PHP_EOL;
			$content .= '</select> <input type="submit" name="comparesubmit" value="Compare"></form><p></p>';

			foreach($revs AS $rev){
				$current = ( ! array_key_exists('id', $rev)) ? 'Current ' : '';
				$user = new Person($rev['user_id']);
				$content .= $current . 'Revision ' . (int)$rev['major_version'] . '.' . $rev['minor_version'] . ' Modified on ' . $rev['time_modified'] . ' by ' . e($user->getName()) . ' <br>'. PHP_EOL;
			}

			$content .= "<script type='text/javascript'>
						 $('select[name=\"to\"] option:first-child').attr('selected','selected');
						 $('select[name=\"from\"] option:first-child').next().attr('selected','selected');</script>\n";
		}
		else{
			$content = "<p>There are no revisions for this item.</p>\n";
		}

		return $content;
	}

    /**
     * Change sequence order in which items are displayed
     * @param string $direction up or dn
     * @param string $m model
     * @param int $id item id
     * @param int $parent parent id is optional
     */
    public function move($direction, $m, $id, $parentId = null)
    {
        if (User::hasPermission(User::REORDER_ITEMS) === false) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to change the display order of items (' . $m . ')');
            permissionDenied('Permission Denied. You are not permitted to reorder the display of items.');
        }

        if ($direction != 'dn')
            $direction = 'up';

        if (strlen($id) == 0 || ! is_numeric($id)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Invalid arguments given when trying to change the order of items - id missing');
            die('Id required');
        }

        $id = (int)$id;
        
        $model = $m . 'model';
        $this->load->model($model);
        if ($this->$model instanceof ISequenceable) {
            //move it
            if ($parentId != null) {
                $moved = $this->$model->move($id, (int) $parentId, $direction);
            } else {
                $moved = $this->$model->move($id, $direction);
            }
            //log it
            if ($moved) {
                ImpressLogger::log(array(
                    'type' => ImpressLogger::INFO,
                    'message' => 'Changed the display order of ' . $m . ', moved ' . $id . ' ' . $direction,
                    'item' => $m,
                    'item_id' => $id,
                    'action' => ImpressLogger::ACTION_REORDER
                ));
            }
        }

        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Resequences the display order of a model to allow reordering to work properly
     * @param string $m model
     * @param int $parentId
     */
    public function resequence($m, $parentId = null)
    {
        $model = $m . 'model';
        $this->load->model($model);
        if ($this->$model instanceof ISequenceable) {
            $this->$model->resequence($parentId);
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Soft-Link Procedures
     */
    public function softlinkprocedure($pipelineId = null)
    {
        if (User::hasPermission(User::IMPORT_ITEM) === false) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to import procedures into a pipeline');
            permissionDenied('Permission Denied. You are not permitted to import a procedure into this pipeline.');
        }

        $content = '<h2>Soft-Link Procedures</h2>';
        $destPipeline = new Pipeline($pipelineId);

        if ($destPipeline->exists())
        {
            if (isset($_POST['softlinkimportsubmit']) && $_POST['softlinkimportsubmit'] == 1) {
                $srcProcedureIds = $this->input->post('procedures');
                $srcPipelineId = $this->input->post('pipeline');
                if ( ! empty($srcProcedureIds)) {
                    $this->load->model('pipelinehasproceduresmodel');
                    $insertedProcedures = array();
                    foreach ((array) $srcProcedureIds as $i) {
                        $procedure = $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($srcPipelineId, $i, false);
                        $procedure[PipelineModel::PRIMARY_KEY] = $destPipeline->getId();
                        $this->pipelinehasproceduresmodel->insert($procedure);
                        $insertedProcedures[] = new Procedure($i, $destPipeline);
                    }
                    $content .= $this->load->view('admin/importeditems', array('insertedItems' => $insertedProcedures), true);
                    foreach ($insertedProcedures as $ip) {
                        ChangeLogger::log(array(
                            ChangeLogger::FIELD_ITEM_ID => $ip->getId(),
                            ChangeLogger::FIELD_ITEM_KEY => $ip->getItemKey(),
                            ChangeLogger::FIELD_ITEM_TYPE => 'Procedure',
                            ChangeLogger::FIELD_ACTION => ChangeLogger::ACTION_IMPORT,
                            ChangeLogger::FIELD_PIPELINE => $destPipeline->getId(),
                            ChangeLogger::FIELD_PROCEDURE => $ip->getId(),
                            ChangeLogger::FIELD_PARAMETER => null,
                            ChangeLogger::FIELD_MESSAGE => 'Soft-linked Procedure (' . $ip->getId() . ') ' . e($ip->getItemName()) . ' [' . $ip->getItemKey() . '] into Pipeline ('
                                                         . $destPipeline->getId() . ') ' . e($destPipeline->getItemName()) . ' [' . $destPipeline->getItemKey() . ']',
                            ChangeLogger::FIELD_INTERNAL => (int) (bool) $destPipeline->isInternal()
                        ));
                    }
                } else {
                    $content .= '<p>Please select at least one item to import</p>';
                }
            } else {
                $this->load->helper('form');
                $content .= $this->load->view('admin/softlinkprocedures', array('pipelines' => PipelinesFetcher::getPipelines(), 'pipeline' => $destPipeline), true);
            }
        }
        else
        {
            $content .= 'An error occured. Invalid Pipeline id supplied.';
            ImpressLogger::log(ImpressLogger::WARNING, 'Invalid Pipeline id supplied (' . $pipelineId . ') when trying to display Procedures to soft-link.');
        }

        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipelineId)) . $content));
    }

    /**
     * Soft-link Parameters
     */
    public function softlinkparameter($procedureId = null, $pipelineId = null)
    {
        if (User::hasPermission(User::IMPORT_ITEM) === false) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to import parameters into a procedure');
            permissionDenied('Permission Denied. You are not permitted to import Parameters into a procedure');
        }

        $content = '<h2>Soft-Link Parameters</h2>';
        $pipeline = new Pipeline((int) $pipelineId);
        $procedure = new Procedure((int) $procedureId, $pipeline->getId());

        if ($pipeline->exists() && $procedure->exists())
        {
            //should not be allowed to 'import' items from outside the original pathway
            $this->load->model('originalpathwaysmodel');
            $originalPathway = $this->originalpathwaysmodel->getPathwaysByOrigin(array('pipeline_id' => $pipeline->getId(), 'procedure_id' => $procedure->getId()));
            if (empty($originalPathway)) {
                $content .= '<p>You cannot soft-link into this Procedure from outside its original Pipeline.</p>';
            } else {
                if (isset($_POST['softlinkimportsubmit']) && $_POST['softlinkimportsubmit'] == 1) {
                    $arr = $this->input->post('parameters');
                    if ( ! empty($arr)) {
                        $this->load->model('procedurehasparametersmodel');
                        $insertedParameters = array();
                        foreach ((array) $arr as $i) {
                            $this->procedurehasparametersmodel->insert($procedure->getId(), (int) $i);
                            $insertedParameters[] = new Parameter($i, $procedure->getId());
                        }
                        $content .= $this->load->view('admin/importeditems', array('insertedItems' => $insertedParameters), true);
                        foreach ($insertedParameters as $ip) {
                            ChangeLogger::log(array(
                                ChangeLogger::FIELD_ITEM_ID => $procedure->getId(),
                                ChangeLogger::FIELD_ITEM_KEY => $procedure->getItemKey(),
                                ChangeLogger::FIELD_ITEM_TYPE => 'Parameter',
                                ChangeLogger::FIELD_ACTION => ChangeLogger::ACTION_IMPORT,
                                ChangeLogger::FIELD_PIPELINE => $pipeline->getId(),
                                ChangeLogger::FIELD_PROCEDURE => $procedure->getId(),
                                ChangeLogger::FIELD_PARAMETER => null,
                                ChangeLogger::FIELD_MESSAGE => 'Soft-linked Parameter (' . $ip->getId() . ') ' . e($ip->getItemName()) . ' [' . $ip->getItemKey() . '] into Procedure ('
                                                             . $procedure->getId() . ') ' . e($procedure->getItemName()) . ' [' . $procedure->getItemKey() . '] of Pipeline ('
                                                             . $pipeline->getId() . ') ' . e($pipeline->getItemName()) . ' [' . $pipeline->getItemKey() . ']',
                                ChangeLogger::FIELD_INTERNAL => (int) (bool) ($pipeline->isInternal() || $procedure->isInternal())
                            ));
                        }
                    } else {
                        $content .= '<p>Please select at least one item to import</p>';
                    }
                } else {
                    $this->load->helper('form');
                    $content .= $this->load->view(
                        'admin/softlinkparameters',
                        array(
                            'controller' => $this->_controller,
                            'pipelines'  => PipelinesFetcher::fetchAll(),
                            'pipelineId' => $pipeline->getId(),
                            'procedure'  => $procedure
                        ),
                        true
                    );
                }
            }
        }
        else
        {
            $content .= '<p>An error occured. Invalid Pipeline/Procedure id supplied.';
            ImpressLogger::log(ImpressLogger::WARNING, 'Invalid Pipeline/Procedure id supplied (' . $pipelineId . '/' . $procedureId . ') when trying to display parameters to soft-link');
        }

        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipeline->getId(), 'procedure_id' => $procedure->getId())) . $content));
    }

    /**
     * Soft-link Ontology Groups
     */
    public function softlinkontologygroup($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        if (User::hasPermission(User::IMPORT_ITEM) === false) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to import ontology groups into a parameter');
            permissionDenied('Permission Denied. You are not permitted to import ontology groups into a parameter');
        }

        $content = '<h2>Soft-Link Ontology Groups</h2>';
        $pipeline = new Pipeline($pipelineId);
        $procedure = new Procedure($procedureId, $pipeline->getId());
        $parameter = new Parameter($parameterId, $procedure->getId());

        if ($parameter->exists() && $procedure->exists() && $pipeline->exists())
        {
            //should not be allowed to 'import' items from outside the original pathway
            $this->load->model('originalpathwaysmodel');
            $originalPathway = $this->originalpathwaysmodel->getPathwaysByOrigin(array(
                'pipeline_id' => $pipeline->getId(),
                'procedure_id' => $procedure->getId(),
                'parameter_id' => $parameter->getId()
            ));
            if (empty($originalPathway)) {
                $content .= '<p>You cannot soft-link items into this Parameter from outside its original Pipeline.</p>';
            } else {
                if (isset($_POST['softlinkimportsubmit']) && $_POST['softlinkimportsubmit'] == 1) {
                    $arr = $this->input->post('groups');
                    if ( ! empty($arr)) {
                        $this->load->model('parameterhasontologygroupsmodel');
                        $insertedGroups = array();
                        foreach ((array) $arr as $i) {
                            $this->parameterhasontologygroupsmodel->insert($parameter->getId(), (int) $i);
                            $insertedGroups[] = new OntologyGroup($i);
                        }
                        $content .= $this->load->view('admin/importedontologygroups', array('insertedItems' => $insertedGroups), true);
                        foreach ($insertedGroups as $ig) {
                            ChangeLogger::log(array(
                                ChangeLogger::FIELD_ITEM_ID => $parameter->getId(),
                                ChangeLogger::FIELD_ITEM_KEY => $parameter->getItemKey(),
                                ChangeLogger::FIELD_ITEM_TYPE => 'Parameter',
                                ChangeLogger::FIELD_ACTION => ChangeLogger::ACTION_IMPORT,
                                ChangeLogger::FIELD_PIPELINE => $pipeline->getId(),
                                ChangeLogger::FIELD_PROCEDURE => $procedure->getId(),
                                ChangeLogger::FIELD_PARAMETER => $parameter->getId(),
                                ChangeLogger::FIELD_MESSAGE => 'Soft-linked Ontology Group (' . $ig->getId() . ') ' . e($ig->getName()) . ' into Parameter ('
                                                             . $parameter->getId() . ') ' . e($parameter->getItemName()) . ' [' . $parameter->getItemKey() . '] of Procedure('
                                                             . $procedure->getId() . ') ' . e($procedure->getItemName()) . ' [' . $procedure->getItemKey() . '] in Pipeline ('
                                                             . $pipeline->getId() . ') ' . e($pipeline->getItemName()) . ' [' . $pipeline->getItemKey() . ']',
                                ChangeLogger::FIELD_INTERNAL => (int) (bool) ($pipeline->isInternal() || $procedure->isInternal() || $parameter->isInternal())
                            ));
                        }
                    } else {
                        $content .= '<p>Please select at least one item to import</p>';
                    }
                } else {
                    $this->load->helper('form');
                    //find out what groups already belong to the parameter and make sure they are not included
                    $allGroups = OntologyGroupsFetcher::getGroups();
                    $paramGroups = $parameter->getOntologyGroups();
                    $i = 0;
                    foreach ($allGroups as $ag) {
                        foreach ($paramGroups as $pg) {
                            if ($pg->getId() == $ag->getId())
                                unset($allGroups[$i]);
                        }
                        $i++;
                    }
                    //load the form
                    $content .= $this->load->view(
                        'admin/softlinkontologygroups',
                        array(
                            'controller' => $this->_controller,
                            'pipeline'   => $pipeline,
                            'procedure'  => $procedure,
                            'parameter'  => $parameter,
                            'allGroups'  => $allGroups
                        ),
                        true
                    );
                }
            }
        }
        else
        {
            $content .= '<p>An error occured. Invalid Parameter/Procedure id supplied.';
            ImpressLogger::log(ImpressLogger::WARNING, 'Invalid Parameter/Procedure id supplied (' . $parameterId . '/' . $procedureId . ') when trying to display ontology groups to soft-link');
        }

        $this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb(array('pipeline_id' => $pipeline->getId(), 'procedure_id' => $procedure->getId(), 'parameter_id' => $parameter->getId())) . $content));
    }

    /**
         * Undelete items
         */
	public function undelete($m, $id, $origin = null)
	{
		//origin - if it hasn't been passed in as an argument then try to extract it from the url
		$originKeys = array('pipeline_id','procedure_id','parameter_id');
		if(empty($origin) || ! is_array($origin) || array_keys($origin) != $originKeys)
			$origin = $this->uri->uri_to_assoc(3, $originKeys);

		//check model
		if(empty($m) || empty($id)){
			ImpressLogger::log(ImpressLogger::WARNING, 'Missing arguments supplied when trying to undelete an item');
			die('<p>Both Model and ID are required</p>');
		}
		$model = $m . 'model';
		if(FALSE === $this->load->model($model)){
			ImpressLogger::log(ImpressLogger::WARNING, 'Invalid model (' . $model . ') supplied when trying to undelete an item');
			die('Failed to find and load model');
		}
		
		//check permissions
		if (User::hasPermission(User::DELETE_ITEM)) {
			//continue undeleting
		} else if (User::hasPermission(User::DELETE_OWN_ITEM)) {
			//check the user is undeleting their own record if the model has 
			//the ability to check the record belongs to the current user
			if ($this->$model instanceof IUserIdCheckable) {
				$record = $this->$model->getById($id);
				if ($record['user_id'] != User::getId()) {
					ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to undelete somebody else\'s items');
					permissionDenied('Permission denied. You are not permitted to undelete another person\'s items.');
				}
			}
		} else {
			ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to undelete items');
			permissionDenied('Permission denied. You are not permitted to undelete items.');
		}

		//deprecated items cannot be undeleted unless the modify_deprecated settings flag is on
		$undeletable = TRUE;
		if (in_array($m, array('pipeline','procedure','parameter','sop','section','parammpterm','parameqterm','paramincrement','paramoption'))) {
			//get the item
			$item = $this->$model->getById($id);
			// if the parent (Pipeline/Procedure/Parameter) of an item is deprecated then a message displays saying the item cannot be edited
			if ( ! empty($item) && ! in_array($m, array('pipeline','procedure','parameter'))) {
				if ($m == 'paramoption')
					$item['deprecated'] = ($this->$model->hasDeprecatedParent(@$origin['parameter_id'])) ? 1 : 0;
				else
					$item['deprecated'] = ($this->$model->hasDeprecatedParent($id)) ? 1 : 0;
			}
			//check if the item is deprecated and if it is allowed to undelete it
			if( ! empty($item)){
				if(TRUE === (bool)$item['deprecated'] && $this->config->item('modify_deprecated') === FALSE)
					$undeletable = FALSE;
			} else {
				return FALSE;
			}
		}

		if($undeletable){
			$rowsAffected = $this->$model->undelete($id, $origin); //$id, TRUE, $origin
			if($rowsAffected){
				$content = $message = '<p>Item Successfully Undeleted!</p>';
				ImpressLogger::log(
					array(
						'type'    => ImpressLogger::INFO,
						'message' => 'Successfully Undeleted ' . $m . ', id: ' . $id,
						'item'    => $m,
						'item_id' => $id,
						'action'  => ImpressLogger::ACTION_UNDELETE
					)
				);
				displayFlashMessage($message, TRUE, getReturnPathTo($m, $origin));
			}else{
				$content = $message = '<p>Undeletion Failed. Please contact the administrator.</p>';
				ImpressLogger::log(
					array(
						'type'    => ImpressLogger::ERROR,
						'message' => 'Failed undeleting ' . $m . ', id: ' . $id,
						'item'    => $m,
						'item_id' => $id,
						'action'  => ImpressLogger::ACTION_UNDELETE
					)
				);
				displayFlashMessage($message, FALSE);
			}
		}else{
			$content = $message = '<p>Sorry but deprecated items cannot be undeleted.</p>';
			displayFlashMessage($message, FALSE);
		}

		$this->load->view('impress', array('content' => $this->_generalTitle . $content));
	}

	/**
	* Define the relationship between parameter options, parameters and procedures
	* @param string $itemType either paramoption, parameter or procedure
        * @param int $pipelineId
        * @param int $procedureId
        * @param int $parameterId
	*/
	public function itemRelationship($itemType = 'parameter', $pipelineId = null, $procedureId = null, $parameterId = null)
	{
		if( ! in_array($itemType, array('paramoption', 'parameter', 'procedure')))
			die('Invalid item type supplied');
		$content = '';
                $pipeline  = new Pipeline($pipelineId);
                $procedure = new Procedure($procedureId, $pipeline->getId());
                $parameter = new Parameter($parameterId, $procedure->getId());
                $origin = array(
                    'pipeline_id'  => $pipelineId,
                    'procedure_id' => $procedureId,
                    'parameter_id' => $parameterId
                 );

		//load validation library
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
		$this->form_validation->set_rules('frompipeline', 'Pipelines', 'required|callback_admin_pipeline_exists');
		$this->form_validation->set_rules('topipeline', 'Pipelines', 'required|callback_admin_pipeline_exists');
		$this->form_validation->set_rules('fromprocedure', 'Procedures', 'required|callback_admin_procedure_exists');
		$this->form_validation->set_rules('toprocedure', 'Procedures', 'required|callback_admin_procedure_exists');
		$this->form_validation->set_rules('nvrelation', 'Relationship', 'required|callback_admin_validateRelationType');
                $this->form_validation->set_rules('nvrelationdescription', 'Description', 'trim');
		if($itemType == 'parameter' || $itemType == 'paramoption'){
			$this->form_validation->set_rules('fromparameter', 'Parameters', 'required|callback_admin_parameter_exists');
			$this->form_validation->set_rules('toparameter', 'Parameters', 'required|callback_admin_parameter_exists');
		}
                if($itemType == 'paramoption'){
                    $this->form_validation->set_rules('fromparamoption', 'Option', 'required|callback_admin_validateOptionId');
                    $this->form_validation->set_rules('toparamoption', 'Option', 'required|callback_admin_validateOptionId');
                }

		if($this->form_validation->run() === false){
                    $content = $this->load->view(
                        'admin/relationshipform',
                        array(
                            'controller' => $this->_controller,
                            'itemType' => $itemType,
                            'pipelines' => PipelinesFetcher::getPipelines(),
                            'selectedPipeline' => $pipeline->getId(),
                            'procedures' => PipelineHasProcedures::fetchAll($pipeline->getId()),
                            'selectedProcedure' => $procedure->getId(),
                            'parameters' => ($itemType != 'paramoption') ? ProcedureHasParameters::fetchAll($procedure->getId()) : array_filter( //$procedure->getParameters()
                                ProcedureHasParameters::fetchAll($procedure->getId()), //$procedure->getParameters(),
                                function($p) use ($itemType) {return $p->isOption();}
                            ),
                            'selectedParameter' => $parameter->getId(),
                            'options' => $parameter->getOptions(),
                            'errors' => validation_errors(), 
                            'flash' => getFlashMessage()
                        ),
                        true
                    );
		}else{
			$model = $itemType . 'relationsmodel';
			$this->load->model($model);
			$arr = array();
                        $arr['pipeline_id'] = $pipelineId;
                        $arr['procedure_id'] = $procedureId;
                        $arr['parameter_id'] = $parameterId;
			$arr['relationship'] = $this->input->post('nvrelation');
			$arr['description'] = (empty($_POST['nvrelationdescription'])) ? null : $this->input->post('nvrelationdescription');
			$arr['connection'] = ERelationConnection::ASSOCIATION;
			if($itemType == 'paramoption'){
                            $arr['param_option_id'] = $this->input->post('fromparamoption');
                            $arr['parent_id'] = $this->input->post('toparamoption');
                            $arr['from_parameter'] = $this->input->post('fromparameter');
                            $arr['to_parameter'] = $this->input->post('toparameter');
                            $from = new ParamOption($arr['param_option_id']);
                            $to = new ParamOption($arr['parent_id']);
                        }else if($itemType == 'parameter'){
				$arr['parameter_id'] = $this->input->post('fromparameter');
				$arr['parent_id'] = $this->input->post('toparameter');
				$from = new Parameter($arr['parameter_id']);
				$arr['parameter_key'] = $from->getItemKey();
				$to = new Parameter($arr['parent_id']);
				$arr['parent_key'] = $to->getItemKey();
			}else{
				$arr['procedure_id'] = $this->input->post('fromprocedure');
				$arr['parent_id'] = $this->input->post('toprocedure');
				$from = new Procedure($arr['procedure_id']);
				$arr['procedure_key'] = $from->getItemKey();
				$to = new Procedure($arr['parent_id']);
				$arr['parent_key'] = $to->getItemKey();
			}
                        //debug
                        //die(print_r($arr, true));
			$ins = $this->$model->insert($arr);
			if($ins){
				$content .= $message = '<p>Successfully created this new relationship.</p>';
				ImpressLogger::log(
					array(
						'type'    => ImpressLogger::INFO,
						'message' => 'Successfully created a new ' . $itemType . ' relationship',
						'item'    => $itemType . ' relationship',
						'item_id' => $ins,
						'action'  => ImpressLogger::ACTION_CREATE
					)
				);
				displayFlashMessage($message, true, getReturnPathTo($itemType, $origin));
			}else{
				$content .= $message = '<p>An error occured while trying to create this new relationship</p>';
				ImpressLogger::log(
					array(
						'type'    => ImpressLogger::ERROR,
						'message' => 'Failed to create a new ' . $itemType . ' relationship',
						'item'    => $itemType . ' relationship',
						'action'  => ImpressLogger::ACTION_CREATE
					)
				);
				displayFlashMessage($message, false);
			}
		}

		$this->load->view('impress', array('content' => $this->_generalTitle . admin_breadcrumb($origin) . $content));
	}

    /**
     * @callback
     */
    public function managereleases_validateDate($date)
    {
        try {
            new DateTime($date);
        } catch (Exception $e) {
            $this->form_validation->set_message(__FUNCTION__, 'Invalid date format supplied.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function admin_pipeline_exists($id)
    {
        return $this->_admin_item_exists('pipeline', $id);
    }

    /**
     * @callback
     */
    public function admin_procedure_exists($id)
    {
        return $this->_admin_item_exists('procedure', $id);
    }

    /**
     * @callback
     */
    public function admin_parameter_exists($id)
    {
        return $this->_admin_item_exists('parameter', $id);
    }

    /**
     * Called by several @callback's
     */
    private function _admin_item_exists($item, $id)
    {
        $item = ucfirst($item);
        $p = (in_array($item, array('Pipeline', 'Procedure', 'Parameter'))) ? new $item($id) : null;
        if ( ! is_object($p)) {
            throw new Exception('Invalid type passed to _admin_item_exists');
            return false;
        }
        if ( ! $p->exists()) {
            $this->form_validation->set_message('admin_' . strtolower($item) . '_exists', "The $item supplied does not exist.");
            return false;
        }
        return true;
    }
    
    /**
     * @callback
     */
    public function admin_validateRelationType($type)
    {
        if (ERelationType::validate($type) === false) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(', ', ERelationType::__toArray()) . '.');
            return false;
        }
        return true;
    }
    
    /**
     * @callback
     */
    public function admin_validateOptionId($id)
    {
        $this->load->model('paramoptionmodel');
        $p = $this->paramoptionmodel->getById($id);
        if (empty($p)) {
            $this->form_validation->set_message(__FUNCTION__, 'An invalid Option Id was supplied');
            return false;
        }
        return true;
    }
    
    /**
     * @callback
     */
    public function admin_validateOntologyGroupId($id)
    {
        $this->load->model('ontologygroupmodel');
        $p = $this->ontologygroupmodel->getById($id);
        if (empty($p)) {
            $this->form_validation->set_message(__FUNCTION__, 'An invalid Ontology Group Id was supplied');
            return false;
        }
        return true;
    }
}
