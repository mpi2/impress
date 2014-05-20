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
 * The IU class is responsible for adding or editing items in IMPReSS. The
 * gateway method is IU::model(). This class replaces the old Admin::iu() method
 * but links across the admin portal are routed here via the admin/iu/model/*
 * route
 * 
 * @see application/config/routes.php
 */
class IU extends CI_Controller
{
    /**
     * @const INSERT_MODE To signify the mode of the form - Insert
     */
    const INSERT_MODE = 'I';
    /**
     * @const UPDATE_MODE To signify the mode of the form - update
     */
    const UPDATE_MODE = 'U';
    
    /**
     * @var string $_controller
     */
    private $_controller;
    /**
     * @var string $_adminTitle
     */
    private $_adminTitle;
    /**
     * @var string $m item type e.g. procedure, parameter, paramoption, etc.
     */
    private $m;
    /**
     * @var string $model e.g. proceduremodel, parametermodel, paramoptionmodel, etc.
     */
    private $model;
    /**
     * @var int $id The id of the item currently being dealt with
     */
    private $id;
    /**
     * @var string $mode The mode of the current action - insert or update - see consts
     */
    private $mode;
    /**
     * @var array $p The fields with data that will be passed to the view
     */
    private $p = array();
    
    /**
     * Constructor to load essential libraries and helpers
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
        
        //set the title of the page
        $this->_controller = 'admin'; //$this->router->class;
        $this->_adminTitle  = '<h1>' . anchor('admin_controller', 'Administer IMPReSS', array('id' => 'adminimpress')) . '</h1>' . PHP_EOL;
        $this->_adminTitle .= $this->load->view('admin/toggleversiontriggering', null, true);
        
        $this->init();
    }
    
    /**
     * Initialize by reading URI vars and loading the appropriate models and records
     */
    private function init()
    {
        $assoc = $this->uri->uri_to_assoc(3, array('pipeline_id', 'procedure_id', 'parameter_id'));
        
        //extract and load model
        if ( ! isset($assoc['model'])) {
            die('Error: model missing');
        }
        $this->m = $assoc['model'];
        $this->model = "{$this->m}model";
        $this->load->model($this->model);
        $this->id = (isset($assoc['row_id'])) ? (int)$assoc['row_id'] : null;
        
        //load item if id given
        $this->loadInRecord($assoc);
        $this->checkUserAccessPermissions();
        $this->assignUriVars($assoc);
    }
    
    /**
     * If the id is given then it tries to load the item ready for passing into
     * the view. The allvalues hidden field is entered for $this->p here and so
     * is the mode, controller and username if relevant
     * @param array $assoc URI vars
     * @return array key-value pairset of record
     */
    private function loadInRecord(array $assoc)
    {
        $this->p = array();
        $id = $this->id;
        if ($id) {
            $pipelineId = (isset($assoc['pipeline_id'])) ? $assoc['pipeline_id'] : null;
            $this->p = $this->fetchRecord($this->model, $id, $pipelineId);
        }
        if (empty($this->p)) {
            $this->id = null;
            $this->mode = self::INSERT_MODE;
        } else {
            $this->mode = self::UPDATE_MODE;
            $this->p['allvalues'] = $this->getFlattenedHash($this->p);
            //get the username of the last person who edited the record
            if (isset($this->p['user_id'])) {
                $user = new Person((int)$this->p['user_id']);
                $this->p['username'] = $user->getName();
            }
        }
        $this->p['mode'] = $this->mode;
        $this->p['controller'] = $this->_controller;
    }
    
    /**
     * @param string $model
     * @param int $id record id
     * @param int $parentId At the moment, the only model that needs this is the
     * procedure model, in which case the pipeline id is the parent id
     * @return array key-value pairset of record
     */
    private function fetchRecord($model, $id, $parentId = null)
    {
        if ($model == 'proceduremodel') {
            return $this->$model->getByPipelineAndProcedure($parentId, $id);
        } else {
            return $this->$model->getById($id);
        }
    }

    /**
     * The key-value pairs passed in via the URI can be used to set form fields
     * with initial values:
     * e.g. http://localhost/impress/admin/iu/model/parameter/procedure_id/1
     * will set the procedure_id input field value to 1. Note that in Update
     * mode, variables passed in via the URI do not overwrite the values of
     * fields previously pulled in from the database
     * @param array $assoc URI vars
     */
    private function assignUriVars(array $assoc)
    {
        foreach ($assoc as $field => $value) {
            if ( ! isset($this->p[$field])) {
                $this->p[$field] = $value;
            }
        }
    }

    /**
     * Generates the 'allvalues' field value in editing forms in IMPReSS which
     * is used to stop editing collision
     *
     * @param array $arr A hash array of the fields and values
     * @return string md5 hash string of flattened array
     */
    private function getFlattenedHash(array $arr)
    {
        //debug: return htmlentities(strip_tags(nl2br(implode($arr))));
        return md5(implode($arr));
    }
    
    /**
     * This method relies on the $this->mode variable to check permissions
     * 
     * To insert an item the user needs to belong to a role that has the
     * User::CREATE_ITEM permission. To update any item in the database, the
     * user needs the User::EDIT_ITEM permission. In order for a user to edit at
     * least their own items, they should also have the User::EDIT_OWN_ITEM
     * permission
     */
    private function checkUserAccessPermissions()
    {
                
        //Only logged in users may see the admin section
        if ( ! User::hasPermission(User::ACCESS_ADMIN)) {
            $message = 'You do not have permission to access the admin interface. ';
            if (User::isLoggedIn()) {
                $message .= 'Please contact the administrator to seek further assitance.';
            } else {
                $message .= 'Are you ' . anchor($this->config->item('mousephenotypeurl') . 'user/login', 'logged in') . '?';
            }
            permissionDenied($message);
        }
        
        //check if user has permissions to create a new item
        if ($this->mode == self::INSERT_MODE)
        {
            if ( ! User::hasPermission(User::CREATE_ITEM)) {
                ImpressLogger::log(ImpressLogger::SECURITY, "User lacks permission to create new items");
                permissionDenied("Permission denied. You are not allowed to create new items.");
            }
        }
        //check if user has permissions to update items
        else if ($this->mode == self::UPDATE_MODE)
        {
            if ( ! User::hasPermission(User::EDIT_OWN_ITEM))
            {
                ImpressLogger::log(ImpressLogger::SECURITY, "User lacks permission to edit own items or anyone else's");
                permissionDenied("Permission denied. You are not permitted to edit any items.");
            }
            if (isset($this->p['user_id']) &&
                $this->p['user_id'] != User::getId() &&
                 ! User::hasPermission(User::EDIT_ITEM)
            ) {
                ImpressLogger::log(ImpressLogger::SECURITY, "User lacks permission to edit other people's items");
                permissionDenied("Permission denied. You may have tried to edit somebody else's item.");
            }
        }
    }
    
    /**
     * The IMPReSS Admin Title, breadcrumb and the Create/Update item title and
     * success/error flash message responses
     * @return string heading
     */
    private function getHeading()
    {
        //set the title and subtitle
        $content = $this->_adminTitle
                 . '<!--' . $this->session->flashdata("returnLocation") . '-->'
                 . admin_breadcrumb(array(
            'pipeline_id' => $this->p['pipeline_id'],
            'procedure_id' => $this->p['procedure_id'],
            'parameter_id' => $this->p['parameter_id']
        ));
        $title = 'Item';
        switch ($this->m) {
            case 'pipeline': $title = 'Pipeline'; break;
            case 'procedure': $title = 'Procedure'; break;
            case 'parameter': $title = 'Parameter'; break;
            case 'paramincrement': $title = 'Parameter Increment'; break;
            case 'paramoption': $title = 'Parameter Option'; break;
            case 'paramontologyoption': $title = 'Parameter Ontology Option'; break;
            case 'parameqterm': $title = 'EQ Term'; break;
            case 'parammpterm': $title = 'Basic Ontology Term'; break;
            case 'sop': $title = 'Protocol'; break;
            case 'section': $title = 'Protocol Section';  break;
            case 'sectiontitle': $title = 'Protocol Section Title'; break;
            case 'proceduretype': $title = 'Procedure Type'; break;
            case 'procedureweek': $title = 'Procedure Week'; break;
            case 'glossary': $title = 'Glossary Item'; break;
            case 'unit': $title = 'Unit'; break;
            case 'ontologygroup': $title = 'Ontology Group'; break;
        }
        if ($this->mode == self::UPDATE_MODE) {
            $content .= "<h2>Update the $title</h2>";
        } else {
            $content .= "<h2>Create a new $title</h2>";
        }

        //display insert/update success/failure message
        $content .= getFlashMessage();
        
        return $content;
    }
    
    /**
     * Loads the form validation library and sets up all the validation rules.
     * Validation rules can do some basic input editing like whitespace trimming
     */
    private function setupValidationRules()
    {
        //load validation library
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        switch ($this->m) {
            case 'pipeline':
                $this->form_validation->set_rules('name', 'Pipeline Name', 'required|trim|max_length[255]');
                $this->form_validation->set_rules('centre_name', 'Centre Name', 'trim');
                if ($this->mode == self::INSERT_MODE) {
                    $this->form_validation->set_rules('pipeline_key', 'Pipeline Key Stub', 'required|callback_iu_validatePipelineKeyStubb');
                } else {
                    $this->form_validation->set_rules('pipeline_key', 'Pipeline Key', 'required|callback_iu_validatePipelineKey');
                }
                break;
            case 'procedure':
                $this->form_validation->set_rules('name', 'Procedure Name', 'required|trim');
                if ($this->mode == self::UPDATE_MODE) {
                    $this->form_validation->set_rules('procedure_key', 'Procedure Key', 'required|callback_iu_validateProcedureKey');
                }
                $this->form_validation->set_rules('type', 'Procedure Type', 'required');
                $this->form_validation->set_rules('nvrelation', 'Relationship Type', 'required|callback_iu_validateRelationType');
                $this->form_validation->set_rules('level', 'Procedure Level', 'required|callback_iu_validateProcedureLevel');
                $this->form_validation->set_rules('week', 'Week', 'required|callback_iu_validateProcedureWeekId');
                $this->form_validation->set_rules('min_females', 'Min Females', 'trim|callback_iu_validateMinAnimals');
                $this->form_validation->set_rules('min_males', 'Min Males', 'trim|callback_iu_validateMinAnimals');
                $this->form_validation->set_rules('min_animals', 'Min Animals', 'trim|callback_iu_validateMinAnimals');
                break;
            case 'parameter':
                $this->form_validation->set_rules('name', 'Parameter Name', 'required|trim|max_length[255]');
                $this->form_validation->set_rules('type', 'Parameter Type', 'callback_iu_validateParameterType');
                if ($this->mode == self::UPDATE_MODE) {
                    $this->form_validation->set_rules('parameter_key', 'Parameter Key', 'required|callback_iu_validateParameterKey');
                }
                $this->form_validation->set_rules('value_type', 'Value Type', 'callback_iu_validateValueType');
                $this->form_validation->set_rules('graph_type', 'Graph Type', 'callback_iu_validateGraphType');
                $this->form_validation->set_rules('qc_min', 'QC Min', 'numeric');
                $this->form_validation->set_rules('qc_min', 'QC Max', 'numeric');
                $this->form_validation->set_rules('nvrelation', 'Relationship Type', 'required|callback_iu_validateRelationType');
                $this->form_validation->set_rules('unit', 'Unit', 'required|callback_iu_validateUnitId');
                break;
            case 'paramincrement':
                $this->form_validation->set_rules('parameter_id', 'Parameter ID', 'required|integer|callback_iu_validateParameterId');
                $this->form_validation->set_rules('increment_type', 'Increment Type', 'required|callback_iu_validateIncrementType');
                $this->form_validation->set_rules('increment_unit', 'Increment Unit', 'callback_iu_validateIncrementUnit');
                $this->form_validation->set_rules('increment_min', 'Increment Minumum', 'trim|max_length[9]|integer');
                $this->form_validation->set_rules('increment_string', 'Increment String', 'trim|max_length[255]|callback_iu_validateNameString');
                $this->form_validation->set_rules('parameter_type', 'Parameter Type', 'callback_iu_validateParameterTypeForIncrement');
                break;
            case 'paramoption':
                $this->form_validation->set_rules('name', 'Option Name', 'required|trim|max_length[255]|callback_iu_validateNameString');
                $this->form_validation->set_rules('parameter_id', 'Parameter ID', 'required|integer|callback_iu_validateParameterId');
                $this->form_validation->set_rules('parent_id', 'Parent Option ID', 'callback_iu_validateOptionId');
                break;
            case 'parameqterm':
                $this->form_validation->set_rules('entity1_term', 'Entity 1 Term', 'required|max_length[255]|callback_iu_requiresField[entity1_id]|callback_iu_validateNameString');
                $this->form_validation->set_rules('entity1_id', 'Entity 1 ID', 'required|callback_iu_validateOntologyKey|callback_iu_requiresField[entity1_term]');
                $this->form_validation->set_rules('entity2_term', 'Entity 2 Term', 'max_length[255]|callback_iu_requiresField[entity2_id]|callback_iu_validateNameString');
                $this->form_validation->set_rules('entity2_id', 'Entity 2 ID', 'callback_iu_validateOntologyKey|callback_iu_requiresField[entity2_term]');
                $this->form_validation->set_rules('entity3_term', 'Entity 3 Term', 'max_length[255]|callback_iu_requiresField[entity3_id]|callback_iu_validateNameString');
                $this->form_validation->set_rules('entity3_id', 'Entity 3 ID', 'callback_iu_validateOntologyKey|callback_iu_requiresField[entity3_term]');
                $this->form_validation->set_rules('quality1_term', 'Quality 1 Term', 'required|max_length[255]|callback_iu_requiresField[quality1_id]|callback_iu_validateNameString');
                $this->form_validation->set_rules('quality1_id', 'Quality 1 ID', 'required|callback_iu_validatePATOKey|callback_iu_requiresField[quality1_term]');
                $this->form_validation->set_rules('quality2_term', 'Quality 2 Term', 'max_length[255]|callback_iu_requiresField[quality2_id|callback_iu_validateNameString]');
                $this->form_validation->set_rules('quality2_id', 'Quality 2 ID', 'callback_iu_validatePATOKey|callback_iu_requiresField[quality2_term]');
                $this->form_validation->set_rules('parameter_id', 'Parameter ID', 'required|integer|callback_iu_validateParameterId');
                $this->form_validation->set_rules('option_id', 'Option ID', 'callback_iu_validateOptionId');
                $this->form_validation->set_rules('increment_id', 'Increment ID', 'callback_iu_validateIncrementId');
                $this->form_validation->set_rules('selection_outcome', 'Selection Outcome', 'callback_iu_validateSelectionOutcome');
                $this->form_validation->set_rules('sex', 'Sex', 'callback_iu_validateSexType');
                break;
            case 'parammpterm':
                $this->form_validation->set_rules('mp_term', 'MP Term', 'required|trim|max_length[255]|callback_iu_validateNameString');
                $this->form_validation->set_rules('mp_id', 'MP ID', 'required|trim|callback_iu_validateMPKey');
                $this->form_validation->set_rules('parameter_id', 'Parameter ID', 'required|integer|callback_iu_validateParameterId');
                $this->form_validation->set_rules('option_id', 'Option ID', 'callback_iu_validateOptionId');
                $this->form_validation->set_rules('increment_id', 'Increment ID', 'callback_iu_validateIncrementId');
                $this->form_validation->set_rules('selection_outcome', 'Selection Outcome', 'callback_iu_validateSelectionOutcome');
                $this->form_validation->set_rules('sex', 'Sex', 'callback_iu_validateSexType');
                break;
            case 'sop':
                $this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[255]');
                $this->form_validation->set_rules('procedure_id', 'Procedure ID', 'required|integer|callback_iu_validateProcedureId');
                break;
            case 'section':
                $this->form_validation->set_rules('sop_id', 'SOP ID', 'required|integer|callback_iu_validateSOPId');
                $this->form_validation->set_rules('section_title_id', 'Section Title ID', 'required|integer|callback_iu_validateSectionTitleId');
                $this->form_validation->set_rules('level', 'Level', 'integer');
                $this->form_validation->set_rules('section_text', 'Section Text', 'required');
                break;
            case 'sectiontitle':
                $this->form_validation->set_rules('title', 'Title', 'required|trim|max_length[255]|is_unique[section_title.title]');
                break;
            case 'proceduretype':
                $this->form_validation->set_rules('type', 'Type Name', 'required|trim');
                $this->form_validation->set_rules('key', 'Key', 'required|exact_length[3]|callback_iu_validateProcedureTypeKey' . (($this->mode == self::INSERT_MODE) ? '|is_unique[procedure_type.key]' : ''));
                $this->form_validation->set_rules('num', 'Number', 'required|exact_length[3]|numeric' . (($this->mode == self::INSERT_MODE) ? '|is_unique[procedure_type.num]' : ''));
                break;
            case 'procedureweek':
                $this->form_validation->set_rules('label', 'Week Label', 'required|trim|max_length[255]');
                $this->form_validation->set_rules('num', 'Week Number', 'required|numeric|max_length[5]|greater_than[-1]');
                $this->form_validation->set_rules('stage', 'Stage', 'required|callback_iu_validateProcedureWeekStage');
                break;
            case 'glossary':
                $this->form_validation->set_rules('term', 'Term', 'required|trim|max_length[255]' . (($this->mode == self::INSERT_MODE) ? '|is_unique[glossary.term]' : ''));
                $this->form_validation->set_rules('definition', 'Definition', 'required');
                break;
            case 'unit':
                $this->form_validation->set_rules('unit', 'Unit', 'required|trim|max_length[50]|trim|is_unique[units.unit]');
                break;
            case 'paramontologyoption':
                $this->form_validation->set_rules('ontology_term', 'Ontology Term', 'required|trim|max_length[255]|callback_iu_validateNameString');
                $this->form_validation->set_rules('ontology_id', 'Ontology Id', 'required|trim|callback_iu_validateOntologyKey');
                $this->form_validation->set_rules('ontology_group_id', 'Ontology Group', 'required|integer|callback_iu_validateOntologyGroupId');
                break;
            case 'ontologygroup':
                $this->form_validation->set_rules('parameter_id', 'Parameter ID', 'required|integer|callback_iu_validateParameterId');
                $this->form_validation->set_rules('name', 'Group Name', 'required|trim|max_length[255]|is_unique[ontology_group.name]');
                break;
        }
    }
    
    /**
     * This method returns an array with the values submitted in the form. The
     * code does some basic type conversion and nulling where necessary and the
     * validation process does some more things like trimming whitespace. If you
     * want to capture some data from a new field in the form you need to define
     * it here
     * @return array Key-Value map of fields and their data
     */
    private function getSubmittedData()
    {
        $userId = User::getId();
        $centreId = 1; //@hack The old plan was to get centreId from user profile
        
        $arr = array();
        switch ($this->m) {
            case 'pipeline':
                $arr = array(
                    'pipeline_id' => (empty($_POST['pipeline_id'])) ? NULL : $this->input->post('pipeline_id'),
                    'pipeline_key' => $this->input->post('pipeline_key'),
                    'name' => $this->input->post('name'),
                    'weight' => (empty($_POST['weight'])) ? 0 : (int) $this->input->post('weight'),
                    'visible' => (isset($_POST['visible']) && $this->input->post('visible')) ? 1 : 0,
                    'active' => (isset($_POST['active']) && $this->input->post('active')) ? 1 : 0,
                    'internal' => (isset($_POST['internal']) && $this->input->post('internal')) ? 1 : 0,
                    'deprecated' => (isset($_POST['deprecated']) && $this->input->post('deprecated')) ? 1 : 0,
                    'major_version' => (empty($_POST['major_version'])) ? 1 : (int) $this->input->post('major_version'),
                    'minor_version' => (empty($_POST['minor_version'])) ? 0 : (int) $this->input->post('minor_version'),
                    'centre_name' => (empty($_POST['centre_name'])) ? NULL : $this->input->post('centre_name'),
                    'impc' => (isset($_POST['impc']) && $this->input->post('impc')) ? 1 : 0,
                    'description' => $this->input->post('description'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId
                );
                break;
            case 'procedure':
                $arr = array(
                    'pipeline_id' => (empty($_POST['pipeline_id'])) ? NULL : $this->input->post('pipeline_id'),
                    'procedure_id' => (empty($_POST['procedure_id'])) ? NULL : $this->input->post('procedure_id'),
                    'procedure_key' => $this->input->post('procedure_key'),
                    'type' => $this->input->post('type'),
                    'name' => $this->input->post('name'),
                    'level' => $this->input->post('level'),
                    'is_visible' => (isset($_POST['is_visible']) && $this->input->post('is_visible')) ? 1 : 0,
                    'is_active' => (isset($_POST['is_active']) && $this->input->post('is_active')) ? 1 : 0,
                    'is_internal' => (isset($_POST['is_internal']) && $this->input->post('is_internal')) ? 1 : 0,
                    'is_deprecated' => (isset($_POST['is_deprecated']) && $this->input->post('is_deprecated')) ? 1 : 0,
                    'is_mandatory' => (isset($_POST['is_mandatory']) && $this->input->post('is_mandatory')) ? 1 : 0,
                    'is_line_level' => (isset($_POST['is_line_level']) && $this->input->post('is_line_level')) ? 1 : 0,
                    'major_version' => (empty($_POST['major_version'])) ? 1 : (int) $this->input->post('major_version'),
                    'minor_version' => (empty($_POST['minor_version'])) ? 0 : (int) $this->input->post('minor_version'),
                    'description' => $this->input->post('description'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'week' => $this->input->post('week'),
                    'min_females' => (strlen($_POST['min_females']) == 0) ? NULL : $this->input->post('min_females'),
                    'min_males' => (strlen($_POST['min_males']) == 0) ? NULL : $this->input->post('min_males'),
                    'min_animals' => (strlen($_POST['min_animals']) == 0) ? NULL : $this->input->post('min_animals'),
                    //now the fields only needed with creating a new version
                    'nvrelation' => $this->input->post('nvrelation'),
                    'nvrelationdescription' => (empty($_POST['nvrelationdescription'])) ? NULL : $this->input->post('nvrelationdescription'),
                    'nvpipeline' => (int) $this->input->post('nvpipeline'),
                    'nvuseoldpipelinekey' => (isset($_POST['nvuseoldpipelinekey']) && $this->input->post('nvuseoldpipelinekey')) ? 1 : 0,
                    'softlinkintopipelines' => $this->input->post('softlinkintopipelines')
                );
                break;
            case 'parameter':
                $arr = array(
                    'pipeline_id' => (empty($_POST['pipeline_id'])) ? NULL : $this->input->post('pipeline_id'),
                    'procedure_id' => (empty($_POST['procedure_id'])) ? NULL : $this->input->post('procedure_id'),
                    'parameter_id' => (empty($_POST['parameter_id'])) ? NULL : $this->input->post('parameter_id'),
                    'parameter_key' => $this->input->post('parameter_key'),
                    'type' => (empty($_POST['type'])) ? NULL : $this->input->post('type'),
                    'name' => $this->input->post('name'),
                    'visible' => (isset($_POST['visible']) && $this->input->post('visible')) ? 1 : 0,
                    'active' => (isset($_POST['active']) && $this->input->post('active')) ? 1 : 0,
                    'internal' => (isset($_POST['internal']) && $this->input->post('internal')) ? 1 : 0,
                    'deprecated' => (isset($_POST['deprecated']) && $this->input->post('deprecated')) ? 1 : 0,
                    'major_version' => (empty($_POST['major_version'])) ? 1 : (int) $this->input->post('major_version'),
                    'minor_version' => (empty($_POST['minor_version'])) ? 0 : (int) $this->input->post('minor_version'),
                    'description' => (empty($_POST['description'])) ? NULL : $this->input->post('description'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'value_type' => $this->input->post('value_type'),
                    'unit' => $this->input->post('unit'),
                    'derivation' => (empty($_POST['derivation'])) ? NULL : $this->input->post('derivation'),
                    'data_analysis_notes' => (empty($_POST['data_analysis_notes'])) ? NULL : $this->input->post('data_analysis_notes'),
                    'graph_type' => (empty($_POST['graph_type'])) ? NULL : $this->input->post('graph_type'),
                    'qc_min' => (strlen($_POST['qc_min']) == 0) ? NULL : (float) $this->input->post('qc_min'),
                    'qc_max' => (strlen($_POST['qc_max']) == 0) ? NULL : (float) $this->input->post('qc_max'),
                    'qc_notes' => (empty($_POST['qc_notes'])) ? NULL : $this->input->post('qc_notes'),
                    'qc_check' => (isset($_POST['qc_check']) && $this->input->post('qc_check')) ? 1 : 0,
                    'is_derived' => (isset($_POST['is_derived']) && $this->input->post('is_derived')) ? 1 : 0,
                    'is_increment' => (isset($_POST['is_increment']) && $this->input->post('is_increment')) ? 1 : 0,
                    'is_option' => (isset($_POST['is_option']) && $this->input->post('is_option')) ? 1 : 0,
                    'is_required' => (isset($_POST['is_required']) && $this->input->post('is_required')) ? 1 : 0,
                    'is_important' => (isset($_POST['is_important']) && $this->input->post('is_important')) ? 1 : 0,
                    'is_annotation' => (isset($_POST['is_annotation']) && $this->input->post('is_annotation')) ? 1 : 0,
                    //now the fields only needed with creating a new version
                    'nvrelation' => $this->input->post('nvrelation'),
                    'nvrelationdescription' => (empty($_POST['nvrelationdescription'])) ? NULL : $this->input->post('nvrelationdescription'),
                    'nvpipeline' => (int) $this->input->post('nvpipeline'),
                    'nvprocedure' => (int) $this->input->post('nvprocedure'),
                    'nvforkprocedure' => (isset($_POST['nvforkprocedure']) && $this->input->post('nvforkprocedure')) ? 1 : 0,
                    'nvuseoldpipelinekey' => (isset($_POST['nvuseoldpipelinekey']) && $this->input->post('nvuseoldpipelinekey')) ? 1 : 0,
                );
                break;
            case 'paramincrement':
                $arr = array(
                    'param_increment_id' => (empty($_POST['param_increment_id'])) ? NULL : $this->input->post('param_increment_id'),
                    'weight' => $this->input->post('weight'),
                    'is_active' => (isset($_POST['is_active']) && $this->input->post('is_active')) ? 1 : 0,
                    'increment_string' => (strlen($_POST['increment_string']) == 0) ? NULL : $this->input->post('increment_string'),
                    'increment_type' => $this->input->post('increment_type'),
                    'increment_unit' => (empty($_POST['increment_unit'])) ? NULL : $this->input->post('increment_unit'),
                    'increment_min' => (strlen($_POST['increment_min']) == 0) ? NULL : (int) $this->input->post('increment_min'),
                    'parameter_id' => $this->input->post('parameter_id'),
                    'parameter_type' => $this->input->post('parameter_type'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'procedure_id' => $this->input->post('procedure_id'),
                    'pipeline_id' => $this->input->post('pipeline_id')
                );
                break;
            case 'paramoption':
                $arr = array(
                    'param_option_id' => (empty($_POST['param_option_id'])) ? NULL : $this->input->post('param_option_id'),
                    'parameter_id' => $this->input->post('parameter_id'),
                    'name' => $this->input->post('name'),
                    'parent_id' => (empty($_POST['parent_id'])) ? NULL : (int) $this->input->post('parent_id'),
                    'is_default' => (isset($_POST['is_default']) && $this->input->post('is_default')) ? 1 : 0,
                    'is_active' => (isset($_POST['is_active']) && $this->input->post('is_active')) ? 1 : 0,
                    'description' => $this->input->post('description'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'pipeline_id' => $this->input->post('pipeline_id'),
                    'procedure_id' => $this->input->post('procedure_id'),
                    //and the fields required for creating a new version of the parameter/procedure
                    'nvoption_relation' => $this->input->post('nvoption_relation'),
                    'nvrelation' => $this->input->post('nvrelation'),
                    'nvpipeline' => (int) $this->input->post('nvpipeline'),
                    'nvprocedure' => (int) $this->input->post('nvprocedure'),
                    'nvforkprocedure' => (isset($_POST['nvforkprocedure']) && $this->input->post('nvforkprocedure')) ? 1 : 0,
                    'nvuseoldpipelinekey' => (isset($_POST['nvuseoldpipelinekey']) && $this->input->post('nvuseoldpipelinekey')) ? 1 : 0,
                    'nvrelationdescription' => (empty($_POST['nvrelationdescription'])) ? NULL : $this->input->post('nvrelationdescription'),
                    'nvoption_relationdescription' => (empty($_POST['nvoption_relationdescription'])) ? NULL : $this->input->post('nvoption_relationdescription')
                );
                break;
            case 'parameqterm':
                $arr = array(
                    'param_eqterm_id' => (empty($_POST['param_eqterm_id'])) ? NULL : $this->input->post('param_eqterm_id'),
                    'entity1_term' => $this->input->post('entity1_term'),
                    'entity1_id' => $this->input->post('entity1_id'),
                    'entity2_term' => (empty($_POST['entity2_term'])) ? NULL : $this->input->post('entity2_term'),
                    'entity2_id' => (empty($_POST['entity2_id'])) ? NULL : $this->input->post('entity2_id'),
                    'entity3_term' => (empty($_POST['entity3_term'])) ? NULL : $this->input->post('entity3_term'),
                    'entity3_id' => (empty($_POST['entity3_id'])) ? NULL : $this->input->post('entity3_id'),
                    'quality1_term' => (empty($_POST['quality1_term'])) ? NULL : $this->input->post('quality1_term'),
                    'quality1_id' => (empty($_POST['quality1_id'])) ? NULL : $this->input->post('quality1_id'),
                    'quality2_term' => (empty($_POST['quality2_term'])) ? NULL : $this->input->post('quality2_term'),
                    'quality2_id' => (empty($_POST['quality2_id'])) ? NULL : $this->input->post('quality2_id'),
                    'weight' => $this->input->post('weight'),
                    'parameter_id' => $this->input->post('parameter_id'),
                    'increment_id' => (empty($_POST['increment_id'])) ? NULL : $this->input->post('increment_id'),
                    'option_id' => (empty($_POST['option_id'])) ? NULL : $this->input->post('option_id'),
                    'sex' => (empty($_POST['sex'])) ? NULL : $this->input->post('sex'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'procedure_id' => $this->input->post('procedure_id'),
                    'pipeline_id' => $this->input->post('pipeline_id'),
                    'selection_outcome' => $this->input->post('selection_outcome')
                );
                break;
            case 'parammpterm':
                $arr = array(
                    'param_mpterm_id' => (empty($_POST['param_mpterm_id'])) ? NULL : $this->input->post('param_mpterm_id'),
                    'mp_term' => $this->input->post('mp_term'),
                    'mp_id' => $this->input->post('mp_id'),
                    'weight' => $this->input->post('weight'),
                    'parameter_id' => $this->input->post('parameter_id'),
                    'increment_id' => (empty($_POST['increment_id'])) ? NULL : $this->input->post('increment_id'),
                    'option_id' => (empty($_POST['option_id'])) ? NULL : $this->input->post('option_id'),
                    'sex' => (empty($_POST['sex'])) ? NULL : $this->input->post('sex'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'procedure_id' => $this->input->post('procedure_id'),
                    'pipeline_id' => $this->input->post('pipeline_id'),
                    'selection_outcome' => $this->input->post('selection_outcome')
                );
                break;
            case 'sop':
                $arr = array(
                    'sop_id' => (empty($_POST['sop_id'])) ? NULL : $this->input->post('sop_id'),
                    'title' => $this->input->post('title'),
                    'centre_id' => $centreId,
                    'weight' => (int) $this->input->post('weight'),
                    'major_version' => (int) $this->input->post('major_version'),
                    'minor_version' => (int) $this->input->post('minor_version'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'procedure_id' => $this->input->post('procedure_id'),
                    'pipeline_id' => $this->input->post('pipeline_id')
                );
                break;
            case 'section':
                $arr = array(
                    'section_id' => (empty($_POST['section_id'])) ? NULL : $this->input->post('section_id'),
                    'section_title_id' => $this->input->post('section_title_id'),
                    'section_text' => $this->input->post('section_text'),
                    'weight' => (int) $this->input->post('weight'),
                    'level' => (int) $this->input->post('level'),
                    'level_text' => (empty($_POST['level_text'])) ? NULL : $this->input->post('level_text'),
                    'major_version' => (int) $this->input->post('major_version'),
                    'minor_version' => (int) $this->input->post('minor_version'),
                    'time_modified' => $this->config->item('timestamp'),
                    'user_id' => $userId,
                    'sop_id' => $this->input->post('sop_id'),
                    'procedure_id' => $this->input->post('procedure_id'),
                    'pipeline_id' => $this->input->post('pipeline_id')
                );
                break;
            case 'sectiontitle':
                $arr = array(
                    'id' => (empty($_POST['id'])) ? NULL : $this->input->post('id'),
                    'title' => $this->input->post('title'),
                    'weight' => $this->input->post('weight'),
                    'centre_id' => $centreId
                );
                break;
            case 'proceduretype':
                $arr = array(
                    'id' => (empty($_POST['id'])) ? NULL : $this->input->post('id'),
                    'type' => $this->input->post('type'),
                    'key' => $this->input->post('key'),
                    'num' => $this->input->post('num')
                );
                break;
            case 'procedureweek':
                $arr = array(
                    'id' => (empty($_POST['id'])) ? NULL : $this->input->post('id'),
                    'label' => $this->input->post('label'),
                    'num' => $this->input->post('num'),
                    'stage' => $this->input->post('stage'),
                    'weight' => (int) $this->input->post('weight')
                );
                break;
            case 'glossary':
                $arr = array(
                    'glossary_id' => (empty($_POST['glossary_id'])) ? NULL : $this->input->post('glossary_id'),
                    'term' => $this->input->post('term'),
                    'definition' => $this->input->post('definition'),
                    'user_id' => $userId,
                    'time_modified' => $this->config->item('timestamp')
                );
                break;
            case 'unit':
                $arr = array(
                    'id' => (empty($_POST['id'])) ? NULL : $this->input->post('id'),
                    'unit' => $this->input->post('unit')
                );
                break;
            case 'paramontologyoption':
                $arr = array(
                    'param_ontologyoption_id' => (empty($_POST['param_ontologyoption_id'])) ? NULL : $this->input->post('param_ontologyoption_id'),
                    'ontology_term' => $this->input->post('ontology_term'),
                    'ontology_id' => $this->input->post('ontology_id'),
                    'ontology_group_id' => $this->input->post('ontology_group_id'),
                    'is_default' => (isset($_POST['is_default']) && $this->input->post('is_default')) ? 1 : 0,
                    'is_active' => (isset($_POST['is_active']) && $this->input->post('is_active')) ? 1 : 0,
                    'is_collapsed' => (isset($_POST['is_collapsed']) && $this->input->post('is_collapsed')) ? 1 : 0,
                    'pipeline_id' => $this->input->post('pipeline_id'),
                    'procedure_id' => $this->input->post('procedure_id'),
                    'parameter_id' => $this->input->post('parameter_id')
                );
                break;
            case 'ontologygroup':
                $arr = array(
                    'ontology_group_id' => (empty($_POST['ontology_group_id'])) ? NULL : $this->input->post('ontology_group_id'),
                    'pipeline_id' => (empty($_POST['pipeline_id'])) ? NULL : $this->input->post('pipeline_id'),
                    'procedure_id' => (empty($_POST['procedure_id'])) ? NULL : $this->input->post('procedure_id'),
                    'parameter_id' => (empty($_POST['parameter_id'])) ? NULL : $this->input->post('parameter_id'),
                    'name' => $this->input->post('name')
                );
                break;
        }
        return $arr;
    }

    /**
     * Edit Collision is when a record is altered by somebody else while you are
     * working on it. Collision detection is done when a user is editing an item
     * and upon pressing the submit button. This is an implementation of
     * optimistic edit collision detection - see confluence documentation
     * @return string Collision error message or an empty string if no collision
     */
    private function checkForEditCollision()
    {
        $collisionError = '';
        if ($this->mode == self::UPDATE_MODE && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $previousHash = $this->input->post('allvalues');
            $pipelineId = (isset($this->p['pipeline_id'])) ? $this->p['pipeline_id'] : null;
            $nowInDb = $this->fetchRecord($this->model, $this->id, $pipelineId);
            if (empty($nowInDb)) {
                $collisionError = 'Fatal error. It appears as though this item'
                                . 'has been deleted from the database! Cannot '
                                . 'continue with edit.';
            } else {
                $nowHash = $this->getFlattenedHash($nowInDb);
                if ($nowHash != $previousHash) {
                    $collisionError = 'It appears that someone else has modified '
                                    . 'this record while you were editing it. '
                                    . 'Your update has not been processed.';
                }
            }
            //put the submitted data back into the form fields
            if ( ! empty($collisionError)) {
                $this->p = array_merge($this->p, $_POST);
                $collisionError = "<div class='error'>$collisionError</div>\n";
            }
        }
        return $collisionError;
    }
    
    /**
     * Ensures he original return location is maintained even if the user
     * encounters a validation error and is returned back to the form
     */
    private function maintainReturnLocation()
    {
        if ($this->session->flashdata('returnLocation')) {
            $this->session->keep_flashdata('returnLocation');
        }
    }
    
    /**
     * If the modify_deprecated setting is on then this method always returns
     * false, allowing unrestricted editing. Otherwise deprecation is checked
     * only for items being edited
     * @return boolean returns false if item is not deprecated or is but may be edited
     */
    private function isItemDeprecated()
    {
        if ($this->config->item('modify_deprecated')) {
            return false;
        }
        
        $model = $this->{$this->model};
        $isDeprecated = false;
        if ($this->mode == self::UPDATE_MODE && $model instanceof IPathwayCheckable) {
            if ($this->m == 'procedure') {
                $isDeprecated = (bool)$this->p['is_deprecated'];
            } else if ($this->m == 'parameter' || $this->m == 'pipeline') {
                $isDeprecated = (bool)$this->p['deprecated'];
            } else if ($this->m == 'paramoption' && isset($this->p['parameter_id'])) {
                $isDeprecated = $model->hasDeprecatedParentByParentId($this->p['parameter_id']);
            } else {
                $isDeprecated = $model->hasDeprecatedParent($this->id);
            }
        }
        return $isDeprecated;
    }
    
    /**
     * Checks to see if an item is being edited from it's original location - if
     * the user were to try edit an IMPC item from the JAX Pipeline it should
     * stop them
     * @return boolean
     */
    private function isItemFromOriginalPathway()
    {
        if ($this->m == 'pipeline' || $this->m == 'procedure') {
            return true;
        }
        
        $model = $this->{$this->model};
        if ($model instanceof IPathwayCheckable)
        {
            if ($this->mode == self::INSERT_MODE &&
                in_array($this->m, array('procedure', 'sop', 'section')) //, 'paramoption'
            ) {
                return true;
            }
            
            $this->load->model('originalpathwaysmodel');
            
            if (($this->mode == self::INSERT_MODE && $this->m == 'parameter') ||
                ($this->mode == self::UPDATE_MODE && ($this->m == 'sop' || $this->m == 'section')) //'procedure'
            ) {
                $matchingpathways = $this->originalpathwaysmodel->getPathwaysByOrigin($this->p);
            } else {
                $matchingpathways = $this->originalpathwaysmodel->getPathwayMatching($this->p);
            }
            return (empty($matchingpathways)) ? false : true;
        }
        return true;
    }
    
    /**
     * @return boolean
     */
    private function isItemInternal()
    {
        $model = $this->{$this->model};
        if ($this->m == 'parameter' || $this->m == 'pipeline') {
            return $model->isInternal($this->id);
        } else if ($this->m == 'procedure') {
            return $model->isInternal(array(
                'pipeline_id'  => $this->p['pipeline_id'],
                'procedure_id' => $this->p['procedure_id']
            ));
        } else if ($this->m == 'paramoption' || $this->m == 'paramincrement') {
            return $model->hasInternalParentByParentId($this->p['parameter_id']);
        } else if ($this->m == 'paramontologyoption') {
            return $this->parametermodel->isInternal($this->p[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    /**
     * @return bool
     */
    private function isLatestVersion()
    {
        $origin = array(
            'pipeline_id'  => (isset($this->p['pipeline_id']))  ? $this->p['pipeline_id']  : null,
            'procedure_id' => (isset($this->p['procedure_id'])) ? $this->p['procedure_id'] : null,
            'parameter_id' => (isset($this->p['parameter_id'])) ? $this->p['parameter_id'] : null
        );
        
        if (in_array($this->m, array('pipeline', 'procedure', 'parameter'))) {
            return $this->{$this->model}->isLatestVersion($origin);
        } else if ($this->m == 'sop' || $this->m == 'section') {
            return $this->proceduremodel->isLatestVersion($origin);
        } else {
            return $this->parametermodel->isLatestVersion($origin);
        }
    }

    /**
     * If the option being edited is being added on an internal parameter then
     * this method always returns true because internal items are super flexible
     * @return boolean
     */
    private function isCreationOfNewOptionPermitted()
    {
        if ( ! $this->isItemInternal() &&
            $this->m == 'paramoption' &&
            $this->config->item('version_triggering')
        ) {
            $this->load->model('notinbetamodel');
            $parameter = $this->parametermodel->getById($this->p[ParameterModel::PRIMARY_KEY]);
            if ($this->notinbetamodel->keyIsInBeta($parameter['parameter_key'])) {
                $this->load->model('parameterhasoptionsmodel');
                $options = $this->parameterhasoptionsmodel->getByParameter($parameter[ParameterModel::PRIMARY_KEY], false);
                return (count($options) >= 1);
            }
        }
        return true;
    }
    
    /**
     * If an increment is being added to a parameter it should not be permitted
     * unless the parameter is not in beta, or the parameter is internal.
     * Internal items are flexible so we allow adding of new increments or other
     * @return boolean
     */
    private function isCreationOfNewIncrementPermitted()
    {
        if ( ! $this->isItemInternal() &&
            $this->m == 'paramincrement' &&
            $this->config->item('version_triggering')
        ) {
            $this->load->model('notinbetamodel');
            $parameter = $this->parametermodel->getById($this->p[ParameterModel::PRIMARY_KEY]);
            return ! $this->notinbetamodel->keyIsInBeta($parameter['parameter_key']);
        }
        return true;
    }
    
    /**
     * @return string
     */
    private function getRevisionHistory()
    {
        if ($this->mode == self::UPDATE_MODE &&
            in_array($this->m, array('pipeline', 'procedure', 'parameter', 'sop', 'section'))
        ) {
            return $this->load->view('admin/displayrevisions', array(
                'revisions' => $this->{$this->model}->getRevisionsById($this->id),
                'm' => $this->m,
                'id' => $this->id,
                'pipelineId' => @$this->p['pipeline_id'],
                'procedureId' => @$this->p['procedure_id'],
                'controller' => $this->_controller
            ), true);
        }
    }
    
    /**
     * Old Pipelines, Procedures and Parameters with deprecated-style keys
     * cannot have a new version created from them because their keys are
     * incompatible with the new format, so an error message is displayed
     * @param array $arr
     * @param string $returnLocation
     * @param string $message Message to display if key deprecated
     */
    private function checkKeyNotDeprecated(array $arr, $returnLocation, $message)
    {
        if (in_array($this->m, array('parameter', 'procedure', 'pipeline')) &&
            KeyUtil::isDeprecatedKey($arr[$this->m . '_key'])
        ) {
            ImpressLogger::log(array(
                'type' => ImpressLogger::WARNING,
                'message' => strip_tags($message) . ' Data: ' . print_r($arr, true),
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_VERSION
            ));
            displayFlashMessage($message, false, $returnLocation);
        }
    }
    
    /**
     * Create new major versions of paramoptions, parameters, procedures and
     * pipelines if the Create New Major Version (nvsubmitbuttonclicked) button
     * was clicked
     * @param array $arr
     * @param string $returnLocation
     */
    private function createNewMajorVersion(array $arr, $returnLocation)
    {
        //check the user has sufficient permissions to create a new major version
        if ( ! User::hasPermission(User::CREATE_VERSION)) {
            ImpressLogger::log(ImpressLogger::SECURITY, 'User lacks permission to create a new version');
            $message = '<p>Permission Denied. You are not allowed to create new major versions.</p>';
            displayFlashMessage($message, false, $returnLocation);
        }
        
        //Check the key of the item is not deprecated
        $this->checkKeyNotDeprecated($arr, $returnLocation,
                '<p>Error. Items with deprecated keys cannot have a new version created for them.</p>');
        
        //create a new version
        $newVersionId = $this->{$this->model}->createNewVersion($arr);
        if ( ! $newVersionId) {
            $message = '<p>An error occured while trying to create a new Major Version</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::ERROR,
                'message' => strip_tags($message) . ' Data: ' . print_r($arr, true),
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_VERSION
            ));
            displayFlashMessage($message, false, $returnLocation);
        } else {
            $message = '<p>New version successfully created.</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::INFO,
                'message' => "A new version was created for {$this->m}, id: {$this->id}",
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_VERSION
            ));
            //set return location
            if ($this->m == 'pipeline') {
                $returnLocation = getReturnPathTo('home');
            } else if ($this->m == 'parameter' && isset($arr['nvforkprocedure']) && $arr['nvforkprocedure'] == 1) {
                $returnLocation = getReturnPathTo('procedure', $arr);
            } else if ($this->m == 'parameter') {
                $returnLocation = getReturnPathTo('parameter', $arr);
            } else if ($this->m == 'paramoption' && isset($arr['nvforkprocedure']) && $arr['nvforkprocedure'] == 1) {
                $returnLocation = getReturnPathTo('procedure', $arr);
            } else if ($this->m == 'paramoption') {
                $returnLocation = getReturnPathTo('parameter', $arr);
            } else {
                $returnLocation = getReturnPathTo('procedure', $arr);
            }
            displayFlashMessage($message, true, $returnLocation);
        }
    }
    
    /**
     * Update as normal, checking that the edits do not trigger the creation of
     * a new version
     * @param array $arr
     * @param string $returnLocation
     * @param string $content
     */
    private function update(array $arr, $returnLocation, &$content)
    {
        $model = $this->model;
        
        //The $isCreationOfNewVersionRequired can be changed to TRUE only by Procedures or Parameters or Parameter Options
        $creationOfNewVersionRequired = false;
        if ($this->m == 'parameter') {
            $creationOfNewVersionRequired = ($this->$model->isCreationOfNewVersionRequired($this->id, $arr) &&
                                              ! $this->$model->isInternal($this->id));
        } else if ($this->m == 'procedure') {
            $creationOfNewVersionRequired = (
                $this->$model->isCreationOfNewVersionRequired($this->id, $arr) &&
                 ! $this->$model->isInternal(array(
                     ProcedureModel::PRIMARY_KEY => $this->p[ProcedureModel::PRIMARY_KEY],
                     PipelineModel::PRIMARY_KEY  => $this->p[PipelineModel::PRIMARY_KEY]))
            );
        } else if ($this->m == 'paramoption') {
            $creationOfNewVersionRequired = (
                $this->$model->isCreationOfNewVersionRequired($this->id, $arr) &&
                 ! $this->$model->hasInternalParentByParentId($arr[ParameterModel::PRIMARY_KEY])
            );
            $param = $this->parametermodel->getById($arr[ParameterModel::PRIMARY_KEY]);
            $arr['paramoption_key'] = $param['parameter_key'];
        }

        //Check the key of the item is not deprecated
        if ($creationOfNewVersionRequired) {
            $message = '<p>The changes you were trying to make would have '
                     . 'triggered the creation of a new version of this item '
                     . 'but this item has a deprecated key and cannot have a '
                     . 'new version created for it.</p><p>To make key changes '
                     . 'to this deprecated item you should ask the administrator '
                     . 'to change the version_triggering setting to off and the '
                     . 'modify_deprecated setting to on.</p>';
            $this->checkKeyNotDeprecated($arr, $returnLocation, $message);
        }
        
        //check only the latest version is being edited - if a Procedure or Parameter
        //is not the latest version then only simple changes can be made to it
        if ($this->m != 'paramoption' &&
            $creationOfNewVersionRequired &&
            ! $this->$model->isLatestVersion($arr)
        ) {
            
            $message = '<p>The changes you were trying to make would have '
                     . 'triggered the creation of a new version of this item but '
                     . 'the item you are editing is not the latest version, or '
                     . 'it\'s parent is not the latest version, and only simple '
                     . 'edits are allowed on older items. Ask the administrator '
                     . 'to switch off version_triggering if you really need to '
                     . 'edit it.</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::WARNING,
                'message' => strip_tags($message) . ' Data: ' . print_r($arr, true),
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_VERSION
            ));
            displayFlashMessage($message, false, $returnLocation);
        }

        //load a form that allows the specification of the nature of this new
        //version or if new version is not needed then update as normal
        if ($creationOfNewVersionRequired) {
            $content .= $this->load->view(
                "admin/{$model}newversionform",
                array_merge($arr, array(
                    'controller' => $this->_controller,
                    'allvalues'  => $this->p['allvalues']
                )),
                true
            );
        } else {
            $this->updateSimple($arr, $returnLocation);
        }
    }
    
    /**
     * Simple update
     * @see IU::update()
     * @param array $arr
     * @param string $returnLocation
     */
    private function updateSimple(array $arr, $returnLocation)
    {
        $iid = $this->{$this->model}->update($this->id, $arr);
        if ($iid)
        {
            $message = '<p>Data successfully updated.</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::INFO,
                'message' => 'Updated record ' . $this->id . ' for model ' . $this->m,
                'item' => $this->m,
                'item_id' => $this->id,
                'action' => ImpressLogger::ACTION_UPDATE
            ));
            if ($this->m == 'paramoption' &&
                $this->config->item('version_triggering') &&
                is_array($iid)
            ) {
                //when a new version is triggered by the editing of an option
                //the new location of that option is returned from the model's
                //update() method as an $origin style hash array in $iid
                $returnLocation = getReturnPathTo($this->m, $iid);
            } else {
                $returnLocation = getReturnPathTo($this->m, $arr);
            }
            displayFlashMessage($message, true, $returnLocation);
        }
        else
        {
            ImpressLogger::log(ImpressLogger::ERROR, 'An error occured while trying to update. Data: ' . print_r($arr, true));
            $message = '<p>An Error occured while trying to update the values!</p>';
            displayFlashMessage($message, false, $returnLocation);
        }
    }

    /**
     * Insert a new record
     * @param array $arr
     * @param string $returnLocation
     */
    private function insert(array $arr, $returnLocation)
    {
        $iid = $this->{$this->model}->insert($arr);
        if ($iid)
        {
            $message = '<p>Data successfully inserted.</p>';
            ImpressLogger::log(array(
                'type' => ImpressLogger::INFO,
                'message' => 'Created a new record for model ' . $this->m,
                'item' => $this->m,
                'item_id' => $iid,
                'action' => ImpressLogger::ACTION_CREATE
            ));
            displayFlashMessage($message, true, $returnLocation);
        }
        else
        {
            ImpressLogger::log(ImpressLogger::ERROR, 'An error occured while trying to insert a new record. Data: ' . print_r($arr, true));
            $message = '<p>An Error occured while trying to insert the values!</p>';
            displayFlashMessage($message, false, $returnLocation);
        }
    }

    /**
     * This is the main method of this class and runs all of IU functionality
     *
     * IU = Insert/Update
     * 
     * One method to do these two similar things... all you have to do is create
     * a form view file, then you set the validation and field array properties
     * in the getSubmittedData() and setupValidationRule() methods of this class.
     * 
     * When a user visits the iu controller model method with the name of the
     * model name alone as the parameter it goes into insert mode:
     * http://localhost/impress/admin/iu/model/model_name
     * 
     * But when a numeric row id is supplied in addition to the model name paramter,
     * it goes into edit mode:
     * http://localhost/impress/admin/iu/model/model_name/row_id/%d
     * 
     * And when you want to enter insert mode but preset one or more of the fields
     * to a particular value, this can be done through the URI:
     * http://localhost/impress/admin/iu/model/model_name/field_name/some_value
     * 
     * @access public
     */
    public function model()
    {
        $content = $this->getHeading();
        
        $returnLocation = getFormReturnLocation();

        $this->setupValidationRules();

        //check the record has not been edited by someone else between the time
        //the form was loaded and when it was submitted
        $collisionError = $this->checkForEditCollision();

        //validate submitted form
        //if form has not been submitted or submitted data is invalid
        //then show the form (and display errors if there are any)
        if ($this->form_validation->run() === false || $collisionError)
        {
            $content .= validation_errors();
            $content .= $collisionError;

            //if a validation error is returned, ensure the redirect location
            //is not changed to the insertion form
            $this->maintainReturnLocation();

            //check the item is not deprecated and/or can be modified
            if ($this->isItemDeprecated()) {
                $content .= '<p>Sorry but deprecated items cannot be modified. '
                          . 'If you really need to edit this item ask the '
                          . 'administrator to change the modify_deprecated setting</p>';
            }

            //check the item being belongs to the pathway it was originally created in
            else if ( ! $this->isItemFromOriginalPathway()) {
                $content .= '<p>Sorry but you are trying to modify or create an item '
                          . 'that does not originate from this Pipeline or Procedure.</p>'
                          . $this->load->view('admin/clonereplacelinks', array(
                                'm' => $this->m,
                                'id' => $this->id,
                                'pipelineId' => $this->p['pipeline_id'],
                                'procedureId' => $this->p['procedure_id'],
                                'parameterId' => @$this->p['parameter_id'],
                                'controller' => $this->_controller
                          ), true);
            }

            //Display stop screen if a new Option is being added to a Parameter that has none
            else if ( ! $this->isCreationOfNewOptionPermitted()) {
                $content .= '<p>Sorry, but adding the first new Option to a '
                          . 'Parameter which does not have any Options already '
                          . 'is not allowed. To add a new Option, you need to '
                          . anchor($this->_controller . '/iu/model/parameter/row_id/' . $this->p['parameter_id'] . '/procedure_id/' . $this->p['procedure_id'] . '/pipeline_id/' . $this->p['pipeline_id'], 'create a new version of this Parameter')
                          . ' first... Or just turn off version triggering.</p>';
            }

            //Display stop screen if an increment is being added to a Parameter
            else if ( ! $this->isCreationOfNewIncrementPermitted()) {
                $content .= '<p>Sorry, but adding new Increments to a Parameter requires you to '
                          . anchor($this->_controller . '/iu/model/parameter/row_id/' . $this->p['parameter_id'] . '/procedure_id/' . $this->p['procedure_id'] . '/pipeline_id/' . $this->p['pipeline_id'], 'create a new version of this Parameter')
                          . ' first.</p>';
            }

            //display form and revision history
            else {
                //Check the item being edited is the latest version or belongs to the latest version of any 3P
                if ( ! $this->isLatestVersion()) {
                    if (in_array($this->m, array('pipeline', 'procedure', 'parameter'))) {
                        $this->load->model('notinbetamodel');
                        $content .= "\n<p><b>Note:</b> This is not the latest version of this Item (or it's parent). ";
                        if ($this->notinbetamodel->keyIsInBeta($this->p["{$this->m}_key"]) && $this->config->item('version_triggering')) {
                            $content .= 'Major changes are not permitted.';
                        }
                        $content .= "</p>\n";
                    } else {
                        $content .= "\n<p><b>Note:</b> This item does not belong to the latest version of its parent.</p>\n";
                    }
                }
                
                $content .= $this->load->view("admin/{$this->m}form", $this->p, true);
                $content .= $this->getRevisionHistory();
            }

        }
        else
        {
            //else data is valid so we create an associated array ($arr) from
            //the submitted values ready for insertion into the db

            //debug
            //die(print_r($_POST, TRUE));

            $arr = $this->getSubmittedData();

            //debug
            //die(print_r($arr, true)); log_message('info', print_r($arr, true));

            if ($this->mode == self::UPDATE_MODE)
            {
                //create a new major version if the Create New Major Version button has been pressed
                if (isset($_POST['nvsubmitbuttonclicked']) &&
                    $this->input->post('nvsubmitbuttonclicked') == 1 &&
                    in_array($this->m, array('paramoption', 'parameter', 'procedure', 'pipeline'))
                ) {
                    $this->createNewMajorVersion($arr, $returnLocation);
                } else {
                    $this->update($arr, $returnLocation, $content);
                }
            }
            else
            {
                $this->insert($arr, $returnLocation, $content);
            }
        }

        $this->load->view('impress', array('content' => $content));
    }

    /**
     * @callback
     * Changed so it allows MA and EMAP terms as well as MP
     */
    public function iu_validateMPKey($mpid)
    {
        if (strlen($mpid) > 0) {
            $this->load->helper('is_valid_ontology_key');
            if ( ! (is_valid_ontology_key($mpid, 'MP') || is_valid_ontology_key($mpid, 'MA') || is_valid_ontology_key($mpid, 'EMAP'))) {
                $this->form_validation->set_message(__FUNCTION__, 'Please enter a valid MP/MA/EMAP Term ID.');
                return false;
            }
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validatePATOKey($patoId)
    {
        if (strlen($patoId) > 0) {
            $this->load->helper('is_valid_ontology_key');
            if ( ! is_valid_ontology_key($patoId, 'PATO')) {
                $this->form_validation->set_message(__FUNCTION__, 'Please enter a valid PATO ID.');
                return false;
            }
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateOntologyKey($oId)
    {
        if (strlen($oId) > 0) {
            $this->load->helper('is_valid_ontology_key');
            if ( ! is_valid_ontology_key($oId)) {
                $this->form_validation->set_message(__FUNCTION__, 'Please enter a valid Ontology ID.');
                return false;
            }
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateValueType($val)
    {
        if (EParamValueType::validate($val) === FALSE) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(", ", EParamValueType::__toArray()) . '.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateParameterType($val)
    {
        if (EParamType::validate($val) === false) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(", ", EParamType::__toArray()) . '.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateParameterTypeForIncrement($val)
    {
        if ( ! ($val == EParamType::SERIES || $val == EParamType::SERIES_MEDIA)) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be either ' . EParamType::SERIES . ' or ' . EParamType::SERIES_MEDIA . '.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateGraphType($val)
    {
        if (EParamGraphType::validate($val) === false) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(", ", EParamGraphType::__toArray()) . '.');
            return false;
        }
        return true;
    }

    /**
     * Alphanumspaceunderscoredash validation rule with extra leeway
     * @callback
     */
    public function iu_validateNameString($s = '')
    {
        if ( ! preg_match("/^([a-zA-Z0-9_\-.,+%&\/():'\"\^# ])*$/", $s)) {
            $this->form_validation->set_message(__FUNCTION__, 'Invalid character(s) found in %s.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validatePipelineId($id)
    {
        return $this->_iu_validate('pipeline', $id, __FUNCTION__, 'An invalid Pipeline Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validatePipelineKey($key)
    {
        if (KeyUtil::isValidPipelineKey($key) === false) {
            $this->form_validation->set_message(__FUNCTION__, 'Pipeline Key must resemble the format IMPC_001.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validatePipelineKeyStubb($key)
    {
        if ( ! KeyUtil::isValidPipelineStubb($key)) {
            $this->form_validation->set_message(__FUNCTION__, 'Pipeline Key Stub must be all Uppercase Alphabetic and between 3 and 8 characters long.');
            return false;
        }
        if ( ! KeyUtil::isUniquePipelineStubb($key)) {
            $this->form_validation->set_message(__FUNCTION__, 'The Pipeline Key Stub must be unique. Please try a different one.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateProcedureId($id)
    {
        return $this->_iu_validate('procedure', $id, __FUNCTION__, 'An invalid Procedure Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateProcedureKey($key)
    {
        if (KeyUtil::isValidProcedureKey($key) === false)
        {
            $this->form_validation->set_message(__FUNCTION__, 'Procedure Key must resemble the format IMPC_XRY_001.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateParameterId($id)
    {
        return $this->_iu_validate('parameter', $id, __FUNCTION__, 'An invalid Parameter Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateParameterKey($key)
    {
        if (KeyUtil::isValidParameterKey($key) === false && KeyUtil::isDeprecatedKey($key) === false) {
            $this->form_validation->set_message(__FUNCTION__, 'Parameter Key must resemble the format IMPC_XRY_001_001.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateOptionId($id)
    {
        if ($id == null)
            return true;
        return $this->_iu_validate('paramoption', $id, __FUNCTION__, 'An invalid Option Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateOntologyOptionId($id)
    {
        if ($id == null)
            return true;
        return $this->_iu_validate('paramontologyoption', $id, __FUNCTION__, 'An invalid Ontology Option Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateIncrementId($id)
    {
        if ($id == null)
            return true;
        return $this->_iu_validate('paramincrement', $id, __FUNCTION__, 'An invalid Increment Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateSelectionOutcome($so)
    {
        if (ESelectionOutcome::validate($so) === false) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(', ', ESelectionOutcome::__toArray()) . '.');
            return false;
        }
        return true;
    }
    
    /**
     * @callback
     */
    public function iu_validateProcedureLevel($level)
    {
        if (EProcedureLevel::validate($level) === false) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(', ', EProcedureLevel::__toArray()) . '.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateRelationType($type)
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
    public function iu_validateSexType($val)
    {
        if (ESexType::validate($val) === false) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of ' . join(', ', ESexType::__toArray()) . ' or left empty.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateIncrementType($val)
    {
        if (EIncrementType::validate($val) === false) {
            $this->form_validation->set_message(__FUNCTION__, 'Please choose a valid Increment Type.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateProcedureWeekStage($val)
    {
        if (EProcedureWeekStage::validate($val) === false) {
            $this->form_validation->set_message(__FUNCTION__, 'Please choose a valid Stage.');
            return false;
        }
        return true;
    }
    
    /**
     * @callback
     */
    public function iu_validateMinAnimals($val)
    {
        if ($val != null && ! preg_match('/^\d{1,3}$/', $val)) {
            $this->form_validation->set_message(__FUNCTION__, 'Please enter a positive integer number or leave the field blank');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateIncrementUnit($val)
    {
        if (EIncrementUnit::validate($val) === false) { //$val != null && 
            $this->form_validation->set_message(__FUNCTION__, '%s must be any of (empty)' . join(", ", EIncrementUnit::__toArray()) . '.');
            return false;
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateProcedureTypeKey($key)
    {
        if ( ! KeyUtil::isValidTLA($key)) {
            $this->form_validation->set_message(__FUNCTION__, '%s must be Uppercase and three characters long.');
            return false;
        }
        return true;
    }

    /**
     * This checks another field is filled in as well as the one calling this validation check
     * @param mixed $first The value of the field that is calling this method
     * @param string $second The name of the field that is required
     * @callback
     */
    public function iu_requiresField($first, $second)
    {
        if (strlen($first) != 0) {
            if ( ! isset($_POST[$second]) || strlen($_POST[$second]) == 0) {
                $this->load->helper('titlize');
                $this->form_validation->set_message(__FUNCTION__, 'You must fill in the ' . titlize($second) . ' field if you have filled in %s');
                return false;
            }
        }
        return true;
    }

    /**
     * @callback
     */
    public function iu_validateSOPId($id)
    {
        return $this->_iu_validate('sop', $id, __FUNCTION__, 'An invalid SOP Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateSectionTitleId($id)
    {
        return $this->_iu_validate('sectiontitle', $id, __FUNCTION__, 'An invalid Section Title Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateOntologyGroupId($id)
    {
        return $this->_iu_validate('ontologygroup', $id, __FUNCTION__, 'A non-existent Ontology Group Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateUnitId($id)
    {
        return $this->_iu_validate('unit', $id, __FUNCTION__, 'An invalid Unit Id was supplied');
    }

    /**
     * @callback
     */
    public function iu_validateProcedureWeekId($id)
    {
        return $this->_iu_validate('procedureweek', $id, __FUNCTION__, 'An invalid Week Id was supplied');
    }
    
    /**
     * Called by several @callback's
     */
    private function _iu_validate($model, $id, $method, $message = '')
    {
        $model = $model . 'model';
        if (false === $this->load->model($model)) {
            ImpressLogger::log(array('type' => ImpressLogger::ERROR, 'message' => 'Invalid model supplied for validation: ' . $model, 'alsoerrorlogit' => true));
            return false;
        }
        $p = $this->$model->getById($id);
        if (empty($p)) {
            $this->form_validation->set_message($method, $message . '!');
            return false;
        }
        return true;
    }
}
