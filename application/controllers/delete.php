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
 * The Delete controller replaces:
 *
 * <ul>
 *  <li>Admin::delete()</li>
 *  <li>Admin::_deleteWithVersionTriggeringChecks()</li>
 *  <li>Admin::deleteOntologyOption()</li>
 *  <li>Admin::deleteOntologyOptionGroup()</li>
 *  <li>Admin::deleteOption()</li>
 *  <li>Admin::deleteIncrement()</li>
 *  <li>Admin::deleteParameter()</li>
 *  <li>Admin::deleteProcedure()</li>
 * </ul>
 *
 * Deletion routes are unified to go through the Delete::model() method. Any
 * version triggering actions are passed from Delete::model() to
 * Delete::_deleteWithVersionTriggeringChecks(). If an item is deemed to really
 * need a new version created, it shows a form, otherwise it returns back into
 * the flow of the Delete::model() to delete the item simply.
 */
class Delete extends CI_Controller
{
    /**
     * @var string
     */
    protected $_controller = 'admin';
    /**
     * @var string
     */
    protected $_adminTitle = '';
    /**
     * @var string e.g. parameter, paramoption
     */
    protected $m;
    /**
     * @var string e.g. parametermodel, paramoptionmodel
     */
    protected $model;
    /**
     * @var int The id of the item being un/deleted
     */
    protected $id;
    /**
     * @var array $origin
     */
    protected $origin = array();
    /**
     * @var array $record key value pairs of the item that is being deleted
     */
    protected $record = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        //update config from database
        $this->load->model('overridesettingsmodel');
        $this->overridesettingsmodel->updateRunningConfig();

        //for flash memory when filling out forms
        $this->load->library('session');

        //load language file for tooltips in admin interface
        $this->lang->load('tooltips');
        $this->load->helper('language');
        $this->load->helper('tooltip');
        $this->load->helper('admin_flash');
        $this->load->helper('admin_breadcrumb');
        $this->load->helper('permission_denied');
        $this->load->helper('get_return_path_to');

        //to stop the http_referer missing warning message appearing when starting from scratch
        if ( ! isset($_SERVER['HTTP_REFERER'])) {
            $_SERVER['HTTP_REFERER'] = site_url('admin');
        }

        $this->checkUserAccessPermissions();

        //set the title of the page
        $this->_controller = 'admin';
        $this->_adminTitle  = '<h1>' . anchor('admin_controller', 'Administer IMPReSS', array('id' => 'adminimpress')) . '</h1>' . PHP_EOL;
        $this->_adminTitle .= $this->load->view('admin/toggleversiontriggering', null, true);

        $this->init();
    }

    /**
     * Extracts the URI Vars and then loads the model and the record
     */
    private function init()
    {
        $this->load->library('uri');
        $assoc = $this->uri->uri_to_assoc(2, array(
            'pipeline_id', 'procedure_id', 'parameter_id', 'ontology_group_id'));

        if ( ! (isset($assoc['model']) && isset($assoc['item_id']))) {
            die('Error: required uri fields missing');
        }

        $this->m = $assoc['model'];
        $this->model = "{$this->m}model";
        $this->load->model($this->model);
        $this->id = (int)$assoc['item_id'];
        unset($assoc['model']);
        unset($assoc['item_id']);
        $this->origin = $assoc;
        $this->loadRecord();
    }

    /**
     * Only logged in users may see the admin section
     */
    private function checkUserAccessPermissions()
    {
        if ( ! User::hasPermission(User::ACCESS_ADMIN)) {
            $message = 'You do not have permission to access the admin interface. ';
            if (User::isLoggedIn()) {
                $message .= 'Please contact the administrator to seek further assitance.';
            } else {
                $message .= 'Are you ' . anchor($this->config->item('mousephenotypeurl') . 'user/login', 'logged in') . '?';
            }
            permissionDenied($message);
        }
    }

    /**
     * If the item being deleted is of a certain tyoe it should be going through
     * the version-triggering deletion route - this method checks the route
     * @return bool
     */
    private function shouldBeGoingThroughVersionTriggeringDeletionRoute()
    {
        return in_array($this->m, array(
            'procedure', 'parameter', 'paramoption',
            'paramincrement', 'paramontologyoption', 'ontologygroup'
        ));
    }

    /**
     * @param Pipeline $pipeline
     * @param Procedure $procedure
     * @param Parameter $parameter
     * @param OntologyGroup $ontologyGroup
     */
    private function checkParentsExist(Pipeline $pipeline,
            Procedure $procedure, Parameter $parameter, OntologyGroup $ontologyGroup)
    {
        if ($this->m == 'procedure') {
            $parentsExist = $pipeline->exists();
        } else if ($this->m == 'parameter') {
            $parentsExist = ($pipeline->exists() && $procedure->exists());
        } else if ($this->m == 'paramontologyoption') {
            $parentsExist = ($pipeline->exists() && $procedure->exists() && $parameter->exists() && $ontologyGroup->exists());
        } else {
            $parentsExist = ($pipeline->exists() && $procedure->exists() && $parameter->exists());
        }
        if ( ! $parentsExist) {
            die('Parents missing. Bad URL?');
        }
    }

    /**
     * Loads the fields of the record to be deleted
     */
    private function loadRecord()
    {
        if ($this->m == 'procedure') {
            $this->record = $this->{$this->model}->getByPipelineAndProcedure($this->origin['pipeline_id'], $this->id);
        } else {
            $this->record = $this->{$this->model}->getById($this->id);
        }
        if (empty($this->record)) {
            die('Item not found');
        }
    }

    /**
     * Validation rules for deletion form when going through version-triggering route
     */
    private function setupValidationRules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        if ($this->m == 'procedure') {
            $this->form_validation->set_rules('pipeline_id', 'Pipeline Id', 'required|callback_validate_pipelineExists');
            $this->form_validation->set_rules('procedure_id', 'Procedure Id', 'required|callback_validate_procedureExists');
        } else if ($this->m == 'parameter') {
            $this->form_validation->set_rules('pipeline_id', 'Pipeline Id', 'required|callback_validate_pipelineExists');
            $this->form_validation->set_rules('procedure_id', 'Procedure Id', 'required|callback_validate_procedureExists');
            $this->form_validation->set_rules('parameter_id', 'Parameter Id', 'required|callback_validate_parameterExists');
            $this->form_validation->set_rules('nvpipeline', 'Pipeline', 'required|callback_validate_pipelineExists|callback_validate_checkPipelineNotDeprecated');
        } else if ($this->m == 'paramontologyoption') {
            $this->form_validation->set_rules('ontology_group_id', 'Ontology Group Id', 'required|callback_validate_ontologyGroupExists');
            $this->form_validation->set_rules('ontology_group_name', 'Ontology Group Name', 'required|trim|max_length[255]|is_unique[ontology_group.name]');
        } else if ($this->m == 'paramoption') {
            $this->form_validation->set_rules('param_option_id', 'Option Id', 'required|callback_validate_optionExists');
        }
        if ( ! ($this->m == 'procedure' || $this->m == 'parameter')) {
            $this->form_validation->set_rules('pipeline_id', 'Pipeline Id', 'required|callback_validate_pipelineExists');
            $this->form_validation->set_rules('procedure_id', 'Procedure Id', 'required|callback_validate_procedureExists');
            $this->form_validation->set_rules('parameter_id', 'Parameter Id', 'required|callback_validate_parameterExists|callback_validateParameterNotDeprecated');
            $this->form_validation->set_rules('nvpipeline', 'Pipeline', 'required|callback_validate_pipelineExists|callback_validate_checkPipelineNotDeprecated');
            $this->form_validation->set_rules('nvprocedure', 'Procedure', 'required|callback_validate_procedureExists|callback_validate_checkProcedureNotDeprecated');
        }
        $this->form_validation->set_rules('nvrelation', 'Relationship', 'required|callback_validate_relationType');
    }

    /**
     * @return bool
     */
    private function isLatestVersion()
    {
        $origin = array(
            'pipeline_id'  => (isset($this->origin['pipeline_id']))  ? $this->origin['pipeline_id']  : null,
            'procedure_id' => (isset($this->origin['procedure_id'])) ? $this->origin['procedure_id'] : null,
            'parameter_id' => (isset($this->origin['parameter_id'])) ? $this->origin['parameter_id'] : null
        );
        if ($this->m == 'procedure') {
            $origin['procedure_id'] = $this->id;
        } else if ($this->m == 'parameter') {
            $origin['parameter_id'] = $this->id;
        }

        if (in_array($this->m, array('pipeline', 'procedure', 'parameter'))) {
            return $this->{$this->model}->isLatestVersion($origin);
        } else if ($this->m == 'sop' || $this->m == 'section') {
            return $this->proceduremodel->isLatestVersion($origin);
        } else {
            return $this->parametermodel->isLatestVersion($origin);
        }
    }

    /**
     * Some items should not just simply be deleted. If version triggering is on,
     * deleting an item such as a:
     *
     *   - procedure
     *   - parameter
     *   - paramoption
     *   - ontologygroup
     *   - paramincrement
     *   - paramontologyoption
     *
     * Should bring the user to a page warning the user and allowing them to
     * decide if they want to create a new version.
     *
     * If version triggering is off or the item is not in beta or live, then it
     * will go ahead and delete it normally without any intermediate page.
     */
    private function _deleteWithVersionTriggeringChecks()
    {
        //load required vars
        $pipeline = new Pipeline($this->origin['pipeline_id']);
        $procedure = new Procedure($this->origin['procedure_id'], $pipeline->getId());
        $parameter = new Parameter($this->origin['parameter_id'], $procedure->getId());
        $ontologyGroup = new OntologyGroup($this->origin['ontology_group_id']);

        //check parents exist for item
        $this->checkParentsExist($pipeline, $procedure, $parameter, $ontologyGroup);

        //if version triggering is switched off then just go back and delete the
        //item normally. Simples!
        if ( ! $this->config->item('version_triggering')) {
            return;
        }

        //if the item being deleted is marked as internal or it belongs to a
        //parameter that is internal then just delete the item normally
        if ($this->m == 'parameter') {
            if ($this->{$this->model}->isInternal($this->id)) {
                return;
            }
        } else if ($this->m == 'procedure') {
            if ($this->{$this->model}->isInternal(array(
                'procedure_id' => $this->id, 'pipeline_id' => $pipeline->getId()))
            ) {
                return;
            }
        } else if ($this->m == 'paramoption') {
            if ($this->{$this->model}->hasInternalParentByParentId($parameter->getId())) {
                return;
            }
        } else if (in_array($this->m, array('paramontologyoption', 'ontologygroup', 'paramincrement'))) {
            if ($this->parametermodel->isInternal($parameter->getId())) {
                return;
            }
        }

        //do a check if item is or belongs to 3P item that has not been released
        //to beta - if it hasn't then delete it normally
        $this->load->model('notinbetamodel');
        if (($this->m == 'procedure' &&
              ! $this->notinbetamodel->keyIsInBeta($this->record['procedure_key'])) ||
            ($this->m == 'parameter' &&
              ! $this->notinbetamodel->keyIsInBeta($this->record['parameter_key'])) ||
            (in_array($this->m, array('ontologygroup', 'paramoption', 'paramincrement')) &&
              ! $this->notinbetamodel->keyIsInBeta($parameter->getItemKey()))
        ) {
            return;
        }

        //check procedure or parameter is not mandatory/required
        //Because it is common that data has been submitted for optional parameters/procedures,
        //we're going to treat them like required ones and display versioning form
//        if (($this->m == 'procedure' && $this->record['is_mandatory'] == 0) ||
//            ($this->m == 'parameter' && $this->record['is_required'] == 0)
//        ) {
//            return;
//        }

        //check delete permissions and for deprecation
        $this->_checkUserDeletePermissions();
        $this->_checkItemBeingDeletedNotDeprecated();

        //set validation rules
        $this->setupValidationRules();

        //load form
        if ($this->form_validation->run() === false) {
            //displaying form and errors if there are any
            $content = $this->load->view(
                "admin/delete{$this->m}",
                array(
                    'controller' => $this->_controller,
                    'pipeline_id' => $pipeline->getId(),
                    'procedure_id' => $procedure->getId(),
                    'parameter_id' => $parameter->getId(),
                    'errors' => validation_errors(),
                    'isLatestVersion' => $this->isLatestVersion(),
                    'item_id' => $this->id,
                    'flash' => getFlashMessage(),
                    'ontology_group_id' => $ontologyGroup->getId()
                ),
                true
            );
        } else {
            //good to go: do the delete/create new version thing
            $model = $this->{$this->model};
            $arr = array(
                $model::PRIMARY_KEY => $this->id,
                'pipeline_id' => $this->input->post('pipeline_id'),
                'procedure_id' => $this->input->post('procedure_id'),
                'parameter_id' => $this->input->post('parameter_id'),
                'nvrelation' => $this->input->post('nvrelation'),
                'nvpipeline' => $this->input->post('nvpipeline'),
                'nvprocedure' => $this->input->post('nvprocedure'),
                'nvparameter' => $this->input->post('nvparameter'),
                'nvforkprocedure' => (isset($_POST['nvforkprocedure'])) ? true : false,
                'ontology_group_id' => $this->input->post('ontology_group_id'),
                'ontology_group_name' => $this->input->post('ontology_group_name'),
                'nvuseoldpipelinekey' => (isset($_POST['nvuseoldpipelinekey'])) ? true : false,
                'softlinkintopipelines' => $this->input->post('softlinkintopipelines'),
                'nvrelationdescription' => (empty($_POST['nvrelationdescription'])) ? null : $this->input->post('nvrelationdescription'),
                'param_option_id' => $this->input->post('param_option_id')
            );
            $del = $model->createNewParentVersionAndDeleteOldItem($arr);
            if ($del) {
                $message = '<p>The Item was successfully deleted in a newly created version of this Parameter/Procedure.</p>';
                ImpressLogger::log(array(
                    'type' => ImpressLogger::INFO,
                    'message' => "Successfully deleted {$this->m} {$this->id}",
                    'item' => $this->m,
                    'item_id' => $this->id,
                    'action' => ImpressLogger::ACTION_DELETE
                ));
                displayFlashMessage($message, true, getReturnPathTo($this->m, array_merge($arr, (array)$del)));
            } else {
                $this->errorMessage(getReturnLocation());
            }
        }

        die($this->load->view('impress', array('content' => $this->_adminTitle . admin_breadcrumb($this->origin) . $content), true));
    }

    /**
     * Set flash error message and return
     * @param string $returnLocation
     */
    private function errorMessage($returnLocation = null)
    {
        if (in_array($this->m, array('proceduretype', 'sectiontitle', 'procedureweek'))) {
            $message = '<p>Delete Failed. You cannot delete an item that is currently in use.</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::WARNING,
                'message' => 'User tried to delete an item that is still being used, id: ' . $this->id,
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_DELETE
            ));
            displayFlashMessage($message, false, $returnLocation);
        } else {
            $message = '<p>Deletion Failed. Please contact the administrator.</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::ERROR,
                'message' => 'Failed deleting ' . $this->m . ', id: ' . $this->id,
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_DELETE
            ));
            displayFlashMessage($message, false, $returnLocation);
        }
    }

    /**
     * Check the user's permissions to delete items and display an error page if
     * they lack them
     */
    private function _checkUserDeletePermissions()
    {
        //check delete permissions
        if (User::hasPermission(User::DELETE_ITEM)) {
            //continue as normal
        } else if (User::hasPermission(User::DELETE_OWN_ITEM)) {
            //check the user is deleting their own record if the model has
            //the ability to check the record belongs to the current user
            $model = $this->{$this->model};
            if ($model instanceof IUserIdCheckable &&
                $this->record['user_id'] != User::getId()
            ) {
                permissionDenied("Permission denied. You are not permitted to delete other people's items.");
            }
        } else {
            //User not allowed to delete anything
            permissionDenied('Permission denied. You are not allowed to delete any items.');
        }
    }

    /**
     * If an item is deprecated it cannot be modified or removed so it will
     * display an error page if it is deprecated
     */
    private function _checkItemBeingDeletedNotDeprecated()
    {
        if ($this->m == 'procedure') {
            $parentId = $this->origin['pipeline_id'];
        } else if (in_array($this->m, array('ontologygroup', 'paramontologyoption', 'paramincrement'))) {
            $parentId = $this->origin['parameter_id'];
        } else {
            $parentId = null;
        }
        $model = $this->{$this->model};
        if ($this->config->item('modify_deprecated') === false &&
            $model instanceof IPathwayCheckable
        ) {
            $isDeprecated = false;
            if ($this->m == 'pipeline' || $this->m == 'parameter') {
                $isDeprecated = $model->isDeprecated($this->id);
            } else if ($this->m == 'procedure') {
                $isDeprecated = $model->isDeprecated(array('procedure_id' => $this->id, 'pipeline_id' => $parentId));
            } else if (in_array($this->m, array('ontologygroup', 'paramontologyoption', 'paramincrement'))) {
                $isDeprecated = $model->hasDeprecatedParentByParentId($parentId); //parameterId
            } else {
                $isDeprecated = $model->hasDeprecatedParent($this->id);
            }
            if ($isDeprecated) {
                permissionDenied('You cannot modify or remove items that are deprecated or belong to a deprecated item');
            }
        }
    }

    /**
     * This method is the delete functionality's gateway method and all delete
     * actions are routed through here
     *
     * @todo Work out better strategy for deletion of section titles and
     * paramontologyoptions. Section titles should only delete if not in use and
     * paramontologyoptions need to check group used once only and is deletable.
     * At the moment I'm forcing user to switch off version_triggering to delete
     * a title or a paramontologyoption
     */
    public function model()
    {
        //branch to the version triggering deletion route for certain item types
        if ($this->shouldBeGoingThroughVersionTriggeringDeletionRoute()) {
            $this->_deleteWithVersionTriggeringChecks();
        }

        //check permissions
        $this->_checkUserDeletePermissions();
        $this->_checkItemBeingDeletedNotDeprecated();

        //section titles cannot be deleted with version triggering on
        if ($this->m == 'sectiontitle' && $this->config->item('version_triggering')) {
            $message = 'You should not delete a title that is in use otherwise it '
                     . 'deletes all sections with that title. If you are sure no '
                     . 'one is using the title, switch off version triggering in '
                     . 'order to continue with the deletion';
            displayFlashMessage($message, false);
        }

        //procedure weeks (stages) cannot be deleted with version triggering on
        if ($this->m == 'procedureweek' && $this->config->item('version_triggering')) {
            $message = 'You should not delete a procedure week (stage) that is '
                     . 'in use as it will set the procedure week field of the '
                     . 'procedures that use it to null. If you are sure no one '
                     . 'is using the title, switch off version triggering in '
                     . 'order to continue with the deletion';
              displayFlashMessage($message, false);
        }

        //deleting a pipeline requires version_triggering to be off or if it's not been pushed to beta
        if ($this->m == 'pipeline' && $this->config->item('version_triggering')) {
            $this->load->model('notinbetamodel');
            if ($this->notinbetamodel->keyIsInBeta($this->record['pipeline_key'])) {
                $message = 'You need to switch off version triggering to delete a pipeline';
                displayFlashMessage($message, false, getReturnPathTo('home'));
            }
        }

        //@todo check original pathways not needed in _d...
        //if these items are being deleted outside of their original pathways
        //then an error message should display
        if ($this->m == 'parammpterm' || $this->m == 'parameqterm') {
            $this->load->model('originalpathwaysmodel');
            $matchingpathway = $this->originalpathwaysmodel->getPathwayMatching($this->origin);
            if (empty($matchingpathway)) {
                $content = '<p>This item originates from another Procedure and cannot be deleted from here.</p>';
                return $this->load->view('impress', array('content' => $this->_generalTitle . $content));
            }
        }

        //delete it
        if ($this->m == 'procedure') {
            $this->origin[ProcedureModel::PRIMARY_KEY] = $this->id;
            $rowsAffected = $this->{$this->model}->delete($this->origin);
        } else {
            $rowsAffected = $this->{$this->model}->delete($this->id, null, $this->origin);
        }
        if ($rowsAffected) {
            $message = '<p>Item Deleted Successfully!</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::INFO,
                'message' => 'Successfully Deleted ' . $this->m . ', id: ' . $this->id,
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_DELETE
            ));
            displayFlashMessage($message, true, getReturnPathTo($this->m, $this->origin));
        } else {
            $this->errorMessage();
        }
    }

    /**
         * Undelete items
     * @todo Rewrite this!
         */
    private function undelete($m, $id, $origin = null)
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
     * @callback
     */
    public function validate_relationType($type)
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
    public function validate_optionExists($id)
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
    public function validate_OntologyGroupExists($id)
    {
        $this->load->model('ontologygroupmodel');
        $p = $this->ontologygroupmodel->getById($id);
        if (empty($p)) {
            $this->form_validation->set_message(__FUNCTION__, 'An invalid Ontology Group Id was supplied');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function validate_checkPipelineNotDeprecated($pipelineId)
    {
        return $this->_validate_itemNotDeprecated('pipeline', $pipelineId);
    }

    /**
     * @callback
     */
    public function validate_checkProcedureNotDeprecated($procedureId)
    {
        return $this->_validate_itemNotDeprecated('procedure', $procedureId);
    }

    /**
     * @callback
     */
    public function validate_checkParameterNotDeprecated($parameterId)
    {
        return $this->_validate_itemNotDeprecated('parameter', $parameterId);
    }

    /**
     * Called by several @callback's
     * @param string $item
     * @param int $id
     * @return boolean
     */
    private function _validate_itemNotDeprecated($item, $id)
    {
        $msg['pipeline']  = 'You cannot place a new Procedure in a deprecated Pipeline.';
        $msg['procedure'] = 'You cannot remove a Parameter in a deprecated Procedure.';
        $msg['parameter'] = 'You cannot remove items from a deprecated Parameter';
        $class = ucfirst($item);
        $p = new $class($id);
        if ($this->config->item('modify_deprecated') === false && $p->isDeprecated()) {
            $this->form_validation->set_message("validate_check{$class}NotDeprecated", $msg[$item]);
            return false;
        }
        return;
    }


    /**
     * @callback
     */
    public function validate_pipelineExists($id)
    {
        return $this->_validate_itemExists('pipeline', $id);
    }

    /**
     * @callback
     */
    public function validate_procedureExists($id)
    {
        return $this->_validate_itemExists('procedure', $id);
    }

    /**
     * @callback
     */
    public function validate_parameterExists($id)
    {
        return $this->_validate_itemExists('parameter', $id);
    }

    /**
     * Called by several @callback's
     * @param string $item
     * @param int $id
     * @return bool
     */
    private function _validate_itemExists($item, $id)
    {
        $class = ucfirst($item);
        $p = new $class($id);
        if ( ! $p->exists()) {
            $this->form_validation->set_message("admin_{$item}Exists", "The $item supplied does not exist.");
            return false;
        }
        return true;
    }
}
