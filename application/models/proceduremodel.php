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
 * Procedure model
 */
class ProcedureModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable, ISequenceable
{
    const TABLE = 'procedure';
    const PRIMARY_KEY = 'procedure_id';
    const OLDEDITS_TABLE = 'procedure_oldedits';
    const OLDEDITS_PRIMARY_KEY = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pipelinehasproceduresmodel');
    }

    /**
     * @return array hash of only procedure table fields
     */
    public function fetchAll()
    {
        return $this->db->get(self::TABLE)->result_array();
    }

    /**
     * @param int $id
     * @return array hash of only procedure table fields
     */
    public function getById($id)
    {
        return $this->db->get_where(self::TABLE, array(self::PRIMARY_KEY => $id))->row_array();
    }

    /**
     * @param string $key
     * @return array hash of only procedure table fields
     */
    public function getByKey($key)
    {
        return $this->db->get_where(self::TABLE, array('procedure_key' => $key))->row_array();
    }

    /**
     * @param int $pipelineId
     * @return array hash
     */
    public function getByPipeline($pipelineId)
    {
        return $this->pipelinehasproceduresmodel->getByPipeline($pipelineId, true);
    }
    
    /**
     * Pass the pipeline id and procedure id or an origin-style array for the first argument
     * @param int|array $pipId
     * @param int $procId
     * @return array hash
     */
    public function getByPipelineAndProcedure($pipId, $procId = null)
    {
        if (is_array($pipId)) {
            return $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($pipId[PipelineModel::PRIMARY_KEY], $pipId[self::PRIMARY_KEY]);
        } else {
            return $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($pipId, $procId);
        }
    }

    /**
    * @param int $typeId The id of the type as found in the procedure_type table
    * @return int
    */
    public function getNumProceduresWithType($typeId)
    {
        return (int) $this->db->from(self::TABLE)
                               ->where('type', $typeId)
                               ->count_all_results();
    }

    /**
    * @param int $weekId The id of the week as found in the procedure_week table
    * @return int
    */
    public function getNumProceduresWithWeek($weekId)
    {
        return $this->pipelinehasproceduresmodel->getNumProceduresWithWeek($weekId);
    }

    /**
    * @param array $origin
    * @return bool
    */
    public function delete(array $origin)
    {
        //deprecated procedures are not allowed to be deleted unless the modify_deprecated settings flag is on
        if($this->config->item('modify_deprecated') === FALSE && $this->isDeprecated($origin))
            return false;
			
        //deleting a procedure from a pipeline should mean deleting the link between the pipeline and the procedure
        //but if there is only one link to a procedure then it should mean the procedure itself needs deleting
        $links = $this->pipelinehasproceduresmodel->getByProcedure($origin[self::PRIMARY_KEY], false);
        if(count($links) >= 2){
            $proc = $this->getById($origin[self::PRIMARY_KEY]);
            $del = (bool) $this->pipelinehasproceduresmodel->delete($origin[PipelineModel::PRIMARY_KEY], $origin[self::PRIMARY_KEY]);
            if ($del) {
                $this->_log($origin[self::PRIMARY_KEY], array_merge($proc, $origin), ChangeLogger::ACTION_DELETE);
            }
            return $del;
        }

        //delete the procedure (hard or soft delete it)
        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardDelete($origin);
        return (bool) $this->_setDeletedFlag($origin, true);
    }

    /**
    * @param array $origin
    * @param bool $deleted TRUE sets the deleted flag to 1, FALSE sets it to 0 and effectively undeletes it
    * @todo Check this for sop!
    */
    private function _setDeletedFlag(array $origin, $deleted = true)
    {
        //mark the procedure as un/deleted
        $deleted = ($deleted) ? 1 : 0;
        $ar = $this->pipelinehasproceduresmodel->setDeletedFlag($origin[PipelineModel::PRIMARY_KEY], $origin[self::PRIMARY_KEY], (bool)$deleted);

        if ($ar) {
            //and do the same for its SOP
            //@todo look at this later
            $this->load->model('sopmodel');
            $this->sopmodel->setDeletedFlagByProcedure($id, $deleted, $origin);
            //log it
            $proc = $this->getByPipelineAndProcedure($origin[PipelineModel::PRIMARY_KEY], $origin[self::PRIMARY_KEY]);
            $this->_log($id, $proc, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }

        return $ar;
    }

    /**
     * @param array|int $id Origin-style array or id
     * @param true $bubble
     * @return bool
     */
    public function isDeprecated($id, $bubble = true)
    {
        if (is_numeric($id))
            $id = $this->_getOrigin($id);
        
        return ( ! is_array($id) || empty($id)) ? false : $this->pipelinehasproceduresmodel->isDeprecated($id, null, $bubble);
    }
	
    /**
     * @param array|int $id Origin-style array or id
     * @param bool $bubble
     * @return bool
     */
    public function isInternal($id, $bubble = true)
    {
        if (is_numeric($id))
            $id = $this->_getOrigin($id);
        
        return ( ! is_array($id) || empty($id)) ? false : $this->pipelinehasproceduresmodel->isInternal($id, null, $bubble);
    }
    
    /**
     * @param int $procedureId
     * @return array
     */
    private function _getOrigin($procedureId)
    {
        $this->load->model('originalpathwaysmodel');
        $pathways = $this->originalpathwaysmodel->getPathwaysByProcedure($procedureId);
        return (array)current($pathways);
    }

    /**
     * @param array|int $id
     * @return bool
     */
    public function isDeleted($id)
    {
        if (is_numeric($id))
            $id = $this->_getOrigin($id);
        
        return $this->pipelinehasproceduremodel->isDeleted($origin);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isInBeta($id)
    {
        $item = $this->getById($id);
        if ( ! empty($item)) {
            $this->load->model('notinbetamodel');
            return $this->notinbetamodel->keyIsInBeta($item['procedure_key']);
        }
        return false;
    }

    /**
    * @param array $origin
    * @return int rows affected
    * @see ProcedureModel::delete()
    */
    private function _hardDelete(array $origin)
    {
        //check it exists
        $id = $origin[self::PRIMARY_KEY];
        $proc = $this->getById($id);
        if(empty($proc)){
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Procedure ' . $id . ' because it does not exist! Probably already deleted', 'procedure', ImpressLogger::ACTION_DELETE);
            return 0; //item probably already deleted so 0 affected rows
        }
		
        //only hard-delete if no parameters associated with this procedure or continue if settings switch is on
        $this->load->model('procedurehasparametersmodel');
        $links = $this->procedurehasparametersmodel->getByProcedure($id, false);
		
        //delete the parameters belonging to this procedure if the settings say so
        if($this->config->item('child_deletion')){
            foreach($links as $link) {
                $origin[ParameterModel::PRIMARY_KEY] = $link[ParameterModel::PRIMARY_KEY];
                //@todo look at updating this later
                $this->parametermodel->delete($link[ParameterModel::PRIMARY_KEY], null, $origin);
            }
        }
        
        //delete the Protocol associated with this Procedure
        //@todo look at updating this later
        $this->load->model('sopmodel');
        $sop = $this->sopmodel->getByProcedure($id);
        if( ! empty($sop)){
            $this->sopmodel->delete($sop[SOPModel::PRIMARY_KEY], null, $origin);
        }
		
        //save a backup of the procedure in the deleted table
        $this->load->model('proceduredeletedmodel');
        $iid = $this->proceduredeletedmodel->insert($proc);
		
        //delete the procedure
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $ar = $this->db->affected_rows();
            //delete original pathway (if it is original
            if ($ar) {
                $this->load->model('originalpathwaysmodel');
                unset($origin[ParameterModel::PRIMARY_KEY]);
                $this->originalpathwaysmodel->delete($origin);
            }
            //delete key from in_beta table if present to tie up loose ends
            $this->load->model('notinbetamodel');
            $this->notinbetamodel->deleteByKey($proc['procedure_key']);
            //also delete any relationships if present to tie up loose ends
            $this->load->model('procedurerelationsmodel');
            $this->procedurerelationsmodel->deleteByProcedureOrParent($id);
            //log it
            if ($ar) {
                $proc = array_merge($proc, $origin);
                $this->_log($id, $proc, ChangeLogger::ACTION_DELETE);
            }
            $iid = $ar;
        }

        return $iid;
    }

    /**
    * @param int $id
    * @param array $origin
    * @return bool
    */
    public function undelete($id, array $origin)
    {
        $origin[self::PRIMARY_KEY] = $id;
        
        if($this->config->item('delete_mode') == 'hard')
            return false;
        
        //deprecated procedures are not allowed to be undeleted unless the modify_deprecated flag is on
        if($this->config->item('modify_deprecated') === false && $this->isDeprecated($origin))
            return false;

        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if ($this->isDeleted($origin))
            return (bool) $this->_setDeletedFlag($origin, false);
        
        return (bool) $this->_setDeletedFlag($origin, false);
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
    
    private function _filterOldeditsFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getOldeditsFields(), self::OLDEDITS_PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
	
    public function getFields()
    {
        return array(
            self::PRIMARY_KEY, 'procedure_key', 'name', 'type',
            'user_id', 'major_version', 'minor_version',
            'description', 'time_modified', 'level'
        );
    }
    
    private function _getOldeditsFields()
    {
        return array_merge($this->getFields(), array(
            'week', 'min_females', 'min_males',
            'min_animals', 'is_visible', 'is_active',
            'is_deprecated', 'is_mandatory', 'is_internal',
            self::OLDEDITS_PRIMARY_KEY
        ));
    }

    /**
    * Move a record up or down in display order
    * @param int $procedureId procedure id
    * @param int $pipelineId the id of the pipeline in which this procedure resides
    * @param string $direction should be either "up" or "dn"
    * @see pipelinehasproceduresmodel::move()
    */
    public function move($procedureId, $pipelineId, $direction)
    {
        return $this->pipelinehasproceduresmodel->move($procedureId, $pipelineId, $direction);
    }
	
    /**
    * Resequence the Procedures in the given Pipeline
    * @param int $pipelineId
    */
    public function resequence($pipelineId = null)
    {
        $this->pipelinehasproceduresmodel->resequence((int)$pipelineId);
    }

    /**
    * insert() doubles as both a new record inserter as well as a new version creator
    * @param array $arr hash of columns
    * @param string $action For logging, ACTION_CREATE will go into log
    * @return int|bool last insert id into procedure table or false on failure
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        $pipelineId = null;
        if ( ! isset($arr[PipelineModel::PRIMARY_KEY])) {
            ImpressLogger::log(ImpressLogger::ERROR, 'Insert method called with array lacking pipeline_id: ' . print_r($arr, true), 'procedure', ImpressLogger::ACTION_CREATE);
            return false;
        } else {
            $pipelineId = $arr[PipelineModel::PRIMARY_KEY];
        }

        //prevent duplicates
        if ($action == ChangeLogger::ACTION_CREATE && ! $this->_isUnique($arr)) {
            return false;
        }

        //generate a new procedure key and version or a new key version if it already exists
        if (empty($arr['procedure_key'])) {
            $arr['procedure_key'] = KeyUtil::generateNewProcedureKey($pipelineId, $arr['type']);
        }
        $procedure = $this->getByKey($arr['procedure_key']);
        if ( ! empty($procedure)) {
            $arr['procedure_key'] = $this->_getNextVersionKey($arr['procedure_key']);
        }
        $arr['major_version'] = KeyUtil::getVersionFromProcedureKey($arr['procedure_key']);
        $arr['minor_version'] = 0;

        //insert the new Procedure
        $newProcedureId = null;
        if ($pipelineId) {
            $this->db->insert(self::TABLE, $this->_filterFields($arr));
            $newProcedureId = $this->db->insert_id();
            ImpressLogger::log(ImpressLogger::INFO, 'Created new Procedure: ' . $newProcedureId, 'procedure', ImpressLogger::ACTION_CREATE);
        } else {
            ImpressLogger::log(ImpressLogger::ERROR, 'Insert method called with invalid pipeline_id: ' . var_dump($pipelineId, true), 'procedure', ImpressLogger::ACTION_CREATE);
            return false;
        }

        if ($newProcedureId) {
            //joining procedure with pipeline
            $newProcedureFields['weight'] = (isset($arr['weight'])) ? $arr['weight'] : 0;
            $newProcedureFields['week'] = (isset($arr['week'])) ? $arr['week'] : 0;
            $newProcedureFields['min_females'] = (isset($arr['min_females'])) ? $arr['min_females'] : null;
            $newProcedureFields['min_males'] = (isset($arr['min_males'])) ? $arr['min_males'] : null;
            $newProcedureFields['min_animals'] = (isset($arr['min_animals'])) ? $arr['min_animals'] : null;
            $newProcedureFields['is_visible'] = (isset($arr['is_visible'])) ? $arr['is_visible'] : 1;
            $newProcedureFields['is_active'] = (isset($arr['is_active'])) ? $arr['is_active'] : 1;
            $newProcedureFields['is_mandatory'] = (isset($arr['is_mandatory'])) ? $arr['is_mandatory'] : 0;
            $newProcedureFields['is_internal'] = (isset($arr['is_internal'])) ? $arr['is_internal'] : 0;
            $newProcedureFields['is_deprecated'] = (isset($arr['is_deprecated'])) ? $arr['is_deprecated'] : 0;
            $newProcedureFields['is_deleted'] = 0;
            $newProcedureFields[self::PRIMARY_KEY] = $newProcedureId;
            $newProcedureFields[PipelineModel::PRIMARY_KEY] = $pipelineId;
            $iid = $this->pipelinehasproceduresmodel->insert($newProcedureFields);
            if ($iid === false) {
                ImpressLogger::log(ImpressLogger::ERROR, 'Failed to add Procedure ' . $newProcedureId . ' to Pipeline ' . $pipelineId, 'procedure', ImpressLogger::ACTION_CREATE);
                return false;
            } else {
                //and make sure the new Procedure is inserted into the original_pathways table
                $this->load->model('originalpathwaysmodel');
                $uniquePathway = $this->originalpathwaysmodel->insert(array(
                    'pipeline_id' => $pipelineId,
                    'procedure_id' => $newProcedureId
                ));
                if ( ! $uniquePathway) {
                    ImpressLogger::log(array(
                        'type' => ImpressLogger::WARNING,
                        'message' => 'Procedure inserted does not have unique pathway - how did that happen?',
                        'item' => 'procedure',
                        'action' => ImpressLogger::ACTION_CREATE
                    ));
                }
                //make sure procedure key is inserted into notinbetamodel
                $this->load->model('notinbetamodel');
                $this->notinbetamodel->insert($arr['procedure_key']);
                //log
                ImpressLogger::log(
                        ImpressLogger::INFO, 'Added Procedure ' . $newProcedureId . ' to Pipeline ' . $pipelineId, 'procedure', ImpressLogger::ACTION_CREATE
                );
                if ($action == ChangeLogger::ACTION_CREATE || $action == ChangeLogger::ACTION_UNDELETE) {
                    $arr[self::PRIMARY_KEY] = $newProcedureId;
                    $this->_log($newProcedureId, $arr, $action);
                }
            }
        } else {
            return false;
        }

        return $newProcedureId;
    }
    
    /**
     * Identifies if name of procedure is unique (case-insensitive)
     * @param array $arr
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $procs = $this->pipelinehasproceduresmodel->getByPipeline($arr[PipelineModel::PRIMARY_KEY]);
        foreach ($procs as $proc) {
            if (strtolower($proc['name']) == strtolower($arr['name'])) {
                return false;
            }
        }
        return true;
    }

    /**
    * @param array $arr
    */
    public function createNewVersion(array $arr)
    {
        $this->load->model('procedurehasparametersmodel');
        
        //get the fields required to identify the old version of the procedure
        $oldPipelineId   = $arr[PipelineModel::PRIMARY_KEY];
        $oldProcedureId  = $arr[ProcedureModel::PRIMARY_KEY];
        $oldProcedure    = $this->getById($oldProcedureId);
        $oldProcedureKey = $oldProcedure['procedure_key'];
        $link = $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($oldPipelineId, $oldProcedureId, false);
        if (empty($link)) {
            return false;
        }

        //initialize new version vars
        $newProcedureId  = null; //will be set on insertion
        $newProcedure    = array(); //will populate this with the values from the submitted fields
        $newProcedureKey = null; //will be generated below
        $newPipelineId   = $arr['nvpipeline'];
        $newRelation     = $arr['nvrelation'];
        $newRelationDesc = $arr['nvrelationdescription'];

        //populate the new procedure with the values from the submitted fields
        //@note Can this be done in a better way?!
        $copyFields = array(
            'type', 'name', 'week',
            'min_females', 'min_males', 'min_animals',
            'is_visible', 'is_active', 'is_internal',
            'is_mandatory', 'is_internal', 'description',
            'weight'
        );
        foreach ($copyFields as $k) {
            if (isset($arr[$k])) {
                $newProcedure[$k] = $arr[$k];
            } else if (isset($link[$k])) {
                $newProcedure[$k] = $link[$k];
            }
        }

        //now populate the rest of the array keys and insert the new procedure into the database
        $useOldPipelineKey = ($newPipelineId != $oldPipelineId) ? (bool)$arr['nvuseoldpipelinekey'] : true;
        if ($useOldPipelineKey) {
            $newProcedure['procedure_key'] = $newProcedureKey = KeyUtil::generateNewProcedureKey($oldPipelineId, $oldProcedure['type']);
        } else {
            $newProcedure['procedure_key'] = $newProcedureKey = KeyUtil::generateNewProcedureKey($newPipelineId, $oldProcedure['type']);
        }
        $newProcedure['major_version'] = KeyUtil::getVersionFromProcedureKey($newProcedureKey);
        $newProcedure['minor_version'] = 0;
        $newProcedure['pipeline_id']   = $newPipelineId;
        $newProcedure['time_modified'] = $this->config->item('timestamp');
        $newProcedure['user_id']       = User::getId();
        $newProcedureId = $this->insert($newProcedure, ChangeLogger::ACTION_VERSION);
        $newProcedure['procedure_id']  = $newProcedureId;
        if ( ! $newProcedureId) {
            ImpressLogger::log(ImpressLogger::ERROR, 'Failed to create a new version of Procedure ' . $oldProcedureKey, 'procedure', $oldProcedureId, ImpressLogger::ACTION_CREATE);
            return false;
        }

        //declare relationship between old and new procedure
        $this->load->model('procedurerelationsmodel');
        $rel = $this->procedurerelationsmodel->insert(
            array(
                'pipeline_id'   => $newPipelineId,
                'procedure_id'  => $newProcedureId,
                'procedure_key' => $newProcedureKey,
                'relationship'  => $newRelation,
                'description'   => $newRelationDesc,
                'parent_id'     => $oldProcedureId,
                'parent_key'    => $oldProcedureKey,
                'connection'    => ERelationConnection::RELATION
            )
        );

        //joining old parameters with new procedure
        $linksCreated = $this->procedurehasparametersmodel->copyProcedureParametersToNewProcedure(array(
            'old_procedure_id' => $oldProcedureId,
            'old_pipeline_id'  => $oldPipelineId,
            'new_procedure_id' => $newProcedureId,
            'new_pipeline_id'  => $newPipelineId,
            'except_parameter' => (isset($arr['delete_parameter_id'])) ? $arr['delete_parameter_id'] : null
        ));
        if ($linksCreated > 0) {
            ImpressLogger::log(
                ImpressLogger::INFO,
                    'Linked up all the parameters from the old Procedure ' . $oldProcedureId . ' to the new one ' . $newProcedureId,
                    'procedure',
                    $newProcedureId,
                    ImpressLogger::ACTION_CREATE
            );
        } else {
            ImpressLogger::log(
                ImpressLogger::WARNING,
                    'It appears the old procedure' . $oldProcedureId . ' had no parameters to link up to the new Procedure ' . $newProcedureId,
                    'procedure',
                    $newProcedureId,
                    ImpressLogger::ACTION_CREATE
            );
        }

        //creating a new sop version to join with new procedure
        $this->load->model('sopmodel');
        $iid = $this->sopmodel->createNewVersion($oldProcedureId, $newProcedureId, ChangeLogger::ACTION_VERSION);
        if($iid === false){
            ImpressLogger::log(
                ImpressLogger::ERROR,
                'Failed to copy the SOP from the old Procedure ' . $oldProcedureId . ' to the new one ' . $newProcedureId,
                'protocol',
                $newProcedureId,
                ImpressLogger::ACTION_VERSION
            );
            return false;
        }else{
            ImpressLogger::log(
                ImpressLogger::INFO,
                'Copied the SOP from the old Procedure ' . $oldProcedureId . ' to the new one ' . $newProcedureId,
                'protocol',
                $newProcedureId,
                ImpressLogger::ACTION_VERSION
            );
            //log it
            $newProcedure['srcProcedureId'] = $oldProcedureId;
            $newProcedure['srcProcedureKey'] = $oldProcedureKey;
            $this->_log($newProcedureId, $newProcedure, ChangeLogger::ACTION_VERSION);
        }
        
        //now Soft-link up the new procedure version with other pipelines if requested
        if ( ! empty($arr['softlinkintopipelines'])) {
            foreach ($arr['softlinkintopipelines'] as $pipId) {
                $link = $newProcedure;
                $link['pipeline_id'] = $pipId;
                $link['procedure_id'] = $newProcedureId;
                $this->pipelinehasproceduresmodel->insert($link);
            }
        }

        return $newProcedureId;
    }
	
    /**
     * @param array $source Requires the keys pipeline_id and procedure_id
     * @param array $destination Requires the key pipeline_id and nvrelation
     * and optionally takes cloneProtocol and cloneParameters as boolean flags
     * and nvrelationdescription to describe the relationship between the clone
     * and the original
     * @return bool
     */
    public function cloneProcedure(array $source, array $destination)
    {
        //initial checks
        if ( ! (isset($source[PipelineModel::PRIMARY_KEY]) && isset($source[self::PRIMARY_KEY])))
            return false;
        $srcPipelineId = $source[PipelineModel::PRIMARY_KEY];
        $srcProcedureId = $source[self::PRIMARY_KEY];
        if ( ! (isset($destination[PipelineModel::PRIMARY_KEY]) && isset($destination['nvrelation'])))
            return false;
        $cloneProtocol = (bool) @$destination['cloneProtocol'];
        $cloneParameters = (bool) @$destination['cloneParameters'];

        //Clone fields and amend:
        //Getting these fields via pipelinehasproceduresmodel will fetch the
        //settings fields such as procedure week as well as the data fields
        //$destProcedure = $srcProcedure = $this->getById($srcProcedureId);
        $destProcedure =
        $srcProcedure = $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($srcPipelineId, $srcProcedureId, true);
        if (empty($srcProcedure))
            return false;
        //check proc has a modern type or has had one assigned in clone form
        if (empty($srcProcedure['type']) && empty($destination['type'])) {
            return false;
        } else if (empty($srcProcedure['type']) && ! empty($destination['type'])) {
            $destProcedure['type'] = (int) $destination['type'];
        }
        unset($destProcedure[self::PRIMARY_KEY]);
        unset($destProcedure['procedure_key']);
        $destProcedure['old_procedure_key'] = null;
        $destProcedure['user_id'] = User::getId();
        $destProcedure['time_modified'] = $this->config->item('timestamp');
        $destProcedure['major_version'] = 1;
        $destProcedure['minor_version'] = 0;
        $destProcedure['pipeline_id'] = $destination['pipeline_id'];        

        //insert clone into new Pipeline + clone Protocol and Parameters too
        $destination[self::PRIMARY_KEY] = $iid = $this->insert($destProcedure, ChangeLogger::ACTION_CLONE);
        if ($iid && $cloneProtocol) {
            $this->load->model('sopmodel');
            $success = $this->sopmodel->cloneByProcedure($source, $destination);
            if ( ! $success) {
                return false;
            }
        }
        if ($iid && $cloneParameters) {
            $success = $this->parametermodel->cloneByProcedure($source, $destination);
            if ( ! $success) {
                return false;
            }
        }

        //create a relationship between the original and the clone
        if ($iid) {
            $oldProcedure = $srcProcedure;
            $newProcedure = $this->getById($iid);
            $this->load->model('procedurerelationsmodel');
            $rel = $this->procedurerelationsmodel->insert(
                array(
                    'pipeline_id' => $destination['pipeline_id'],
                    'procedure_id' => $newProcedure[self::PRIMARY_KEY],
                    'procedure_key' => $newProcedure['procedure_key'],
                    'relationship' => $destination['nvrelation'],
                    'description' => @$destination['nvrelationdescription'],
                    'parent_id' => $oldProcedure[self::PRIMARY_KEY],
                    'parent_key' => $oldProcedure['procedure_key'],
                    'connection' => ERelationConnection::RELATION
                )
            );
            //log it
            $srcProcedureInfo = array(
                'srcProcedureId' => $srcProcedure[self::PRIMARY_KEY],
                'srcProcedureKey' => $srcProcedure['procedure_key'],
                'srcProcedureName' => $srcProcedure['name'],
                'pipeline_id' => $destProcedure['pipeline_id']
            );
            $this->_log($iid, array_merge($newProcedure, $srcProcedureInfo), ChangeLogger::ACTION_CLONE);
        }

        return $iid;
    }

    /**
    * Steps to updating (minor update)
    * - save a copy of the record to the backup table
    * - increment the version number
    * - save changes
    * @param int row id to update
    * @param array hash of columns
    * @param string $action
    * @return int rows affected
    */
    public function update($id, $arr, $action = ChangeLogger::ACTION_UPDATE)
    {
        //get current record
        $oldprocedure = $this->getByPipelineAndProcedure($arr[PipelineModel::PRIMARY_KEY], $id);
        if(empty($oldprocedure))
            return 0;
        $this->db->insert(self::OLDEDITS_TABLE, $this->_filterOldeditsFields($oldprocedure));

        $arr['major_version'] = KeyUtil::getVersionFromProcedureKey($arr['procedure_key']);
        $arr['minor_version'] = $oldprocedure['minor_version'] + 1;
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();

        $ar = $this->_update($id, $arr);
        if ($ar) {
            $this->pipelinehasproceduresmodel->updateByPipelineAndProcedure($arr[PipelineModel::PRIMARY_KEY], $arr[self::PRIMARY_KEY], $arr);
        }
        if ($ar && $action == ChangeLogger::ACTION_UPDATE) {
            $oldprocedure[PipelineModel::PRIMARY_KEY] = $arr[PipelineModel::PRIMARY_KEY];
            $this->_log($id, $oldprocedure, $action);
        }
        return $ar;
    }

    private function _update($id, $arr)
    {
        //update the procedure table
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
        //update the linker table which hold the flags
        if ($ar) {
            $br = $this->pipelinehasproceduresmodel->updateByPipelineAndProcedure($arr[PipelineModel::PRIMARY_KEY], $id, $arr);
        }
        return $ar;
    }

    /**
    * @param int $id Id of the row in the table that we want to look up revisions for
    * @return array All the revisions for the supplied id including the current active
    * version but this row in the array lacks an 'id' field, which is intentional as it
    * allows the differentiation between the current version and the old revisions
    */
    public function getRevisionsById($id)
    {
        $new = array($this->getById($id));
        $old = $this->db->from(self::OLDEDITS_TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->order_by('minor_version', 'DESC')
                        ->get()
                        ->result_array();
        return array_merge($new, $old);
    }

    /**
    * This method sets an old revision to become the current version
    * @param int $id the id of the row to update
    * @param int $revId the id of the row in the oldedits table that we will
    * be changing the current version's values to
    * @param array $origin
    * @return int affected rows
    */
    public function revert($id, $revId, array $origin)
    {
        $current = $this->getById($id);
        $revision = $this->db->from(self::OLDEDITS_TABLE)
                             ->where(self::OLDEDITS_PRIMARY_KEY, $revId)
                             ->limit(1)
                             ->get()
                             ->row_array();
        unset($revision[self::OLDEDITS_PRIMARY_KEY]);
        unset($revision[self::PRIMARY_KEY]);
        $revision['minor_version'] = $current['minor_version'];
        $revision['user_id'] = User::getId();
        $revision['time_modified'] = $this->config->item('timestamp');
        $ar = $this->update($id, $revision, ChangeLogger::ACTION_REVERT);
        if ($ar) {
            $revision = array_merge($revision, $origin);
            $this->_log($id, $revision, ChangeLogger::ACTION_REVERT);
        }
        return $ar;
    }

    /**
    * Returns the last procedure from all the versions of a pipelines by looking
    * at it's key and getting the largest version value
    * @param int $procTypeId The id of the procedure type of that procedure
    * @param int $pipId The pipeline id is required to get the key parts to look for the latest Procedure (highest Procedure key Id)
    * @return array|bool The Procedure array of the latest Procedure or FALSE if something wasn't right
    */
    public function getLastProcedure($procTypeId, $pipId)
    {
        $pipId = (int)$pipId;
        $pip = $this->pipelinemodel->getById($pipId);
        if(empty($pip))
            return FALSE;

        $parts = KeyUtil::getPipelineKeyParts($pip['pipeline_key']);
        if($parts === FALSE)
            return FALSE;

        $prefix = $parts[KeyUtil::PREFIX] . '_';

        $procedure = $this->db->query(
            "SELECT
                `proc`.*
             FROM
                `" . self::TABLE . "` AS proc
             INNER JOIN
                pipeline_has_procedures pip
             ON
                pip.pipeline_id = $pipId AND
                pip.procedure_id = proc.procedure_id
             WHERE
                `proc`.`type` = $procTypeId AND
                `proc`.`procedure_key` LIKE '$prefix%'
             ORDER BY
                CAST(SUBSTRING(procedure_key, -3) AS SIGNED INTEGER) DESC
             LIMIT 1"
        )->row_array();
        
        return (empty($procedure)) ? false : $procedure;
    }
    
    /**
     * Returns the last procedure from all the versions of a pipelines by looking
     * at it's key and getting the largest version value
     * @param int $procId
     * @param int $pipId
     * @return array 
     */
    public function getLastProcedureVersion($procId, $pipId)
    {
        $proc = $this->getById($procId);
        return (empty($proc)) ? false : $this->getLastProcedure($proc['type'], $pipId);
    }

    /**
     * Checks the Procedure to see if it is the latest and it also checks the Pipeline
     * This method will be called by child items of the Procedure via the
     * ParameterModel::isLatestVersion() method
     * @param array|int $id
     * @return bool
     * @see ParameterModel::isLatestVersion()
     */
    public function isLatestVersion($origin)
    {
        if (is_numeric($origin))
            $origin = $this->_getOrigin($origin);
        
        $latestProc = $this->getLastProcedureVersion($origin[self::PRIMARY_KEY], $origin[PipelineModel::PRIMARY_KEY]);
        if ($origin[self::PRIMARY_KEY] != $latestProc[self::PRIMARY_KEY])
            return false;
        
        if ( ! $this->pipelinemodel->isLatestVersion($origin))
            return false;
        
        return true;
    }
    
    /**
    * @param int $procId The id of the procedure we are checking
    * @param array $arr The new values we are planning to set the procedure with
    * @return bool
    */
    public function isCreationOfNewVersionRequired($procId, $arr)
    {
        if( ! $this->config->item('version_triggering'))
            return false;
        
        //check if this item is the latest version of this procedure and also the
        //latest pipeline and if it isn't then a new version CANNOT be triggered
//        $arr[self::PRIMARY_KEY] = $procId;
//        if ( ! $this->isLatestVersion($arr))
//            return false;
        
        //check if this item as not been pushed to beta or live
        if( ! $this->isInBeta($procId))
            return false;
        
        //the current record
        $p = $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($arr[PipelineModel::PRIMARY_KEY], $procId, true);
        if (empty($p))
            return false;
        
        //Test 1 - has the week in which this procedure is carried out in changed
//        if(array_key_exists('week', $arr)){
//            if(@$p['week'] != $arr['week'])
//                return true;
//        }

        //Test 2 - we need to see if the procedure has been changed to Mandatory
        if(array_key_exists('is_mandatory', $arr)){
            if (false === (bool)$p['is_mandatory'] && true === (bool)$arr['is_mandatory'])
                return true;
        }
        
        //Test 3 - has level been changed
        if (array_key_exists('level', $arr)) {
            if ($p['level'] != $arr['level'])
                return true;
        }

        return false;
    }
	
    /**
     * @param array $arr
     * @return array|bool $origin style array containing new Pipeline Id or false on failure
     */
    public function createNewParentVersionAndDeleteOldItem($arr)
    {
        $oldPip = $this->pipelinemodel->getById($arr[PipelineModel::PRIMARY_KEY]);
        $arr['delete_procedure_id'] = $arr[self::PRIMARY_KEY];
        $newPipId = $this->pipelinemodel->createNewVersion(array_merge($oldPip, $arr));
        return ($newPipId) ? array(PipelineModel::PRIMARY_KEY => $newPipId) : false;
    }
    
    private function _getNextVersionKey($key)
    {
        $unique = true;
        $newKey = $key;
        do {
            $newKey = KeyUtil::incrementProcedureKeyVersion($newKey);
            $x = $this->getByKey($newKey);
            $unique = empty($x);
        }
        while($unique === false);
        return $newKey;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about origin of the procedure being logged
        $pipeline = $this->pipelinemodel->getById(@$arr[PipelineModel::PRIMARY_KEY]);
        $currentRecord = $this->pipelinehasproceduresmodel->getByPipelineAndProcedure(@$pipeline[PipelineModel::PRIMARY_KEY], $id);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Procedure (' . $id . ') ' . @$arr['name']
                     . ' in Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY]
                     . ') ' . @$pipeline['name'] . ' to version ' . @$currentRecord['major_version']
                     . '.' . @$currentRecord['minor_version'] . '. ';
            $fields = array(
                'name', 'level', 'description',
                'is_visible', 'is_active', 'is_deprecated',
                'is_mandatory', 'is_internal', 'week',
                'min_females', 'min_males', 'min_animals'
            );
            //$anyFieldUpdated = false;
            foreach ($fields as $field) {
                if (@$arr[$field] != @$currentRecord[$field]) {
                    if ($field == 'week') {
                        $this->load->model('procedureweekmodel');
                        $a = $this->procedureweekmodel->getById(@$arr[$field]);
                        $b = $this->procedureweekmodel->getById(@$currentRecord[$field]);
                        $message .= $field . ' changed from ' . @$a['stage'] . @$a['num'] . ' to ' . @$b['stage'] . @$b['num'] . '. ';
                    } else {
                        $message .= $field . ' changed from ' . @$arr[$field] . ' to ' . @$currentRecord[$field] . '. ';
                    }
                    //$anyFieldUpdated = true;
                }
            }
            //don't log if none of the $fields have been altered
            //if ( ! $anyFieldUpdated) {
            //    return true;
            //}
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Procedure (' . $id . ') ' . $arr['name'] . ' '
                     . ' in Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY]
                     . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Procedure (' . $id . ') ' . $arr['name'] . ' '
                     . 'from Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY]
                     . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Procedure (' . $id . ') ' . $arr['name'] . ' '
                     . 'in Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY]
                     . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_REVERT) {
            $message = 'Reverted current Procedure (' . $id . ') '
                     . @$currentRecord['name'] . ' to revision '
                     . @$currentRecord['major_version'] . '.' . @$currentRecord['minor_version'];
        } else if ($action == ChangeLogger::ACTION_VERSION) {
            $message = 'Created a new version of Procedure (' . $arr['srcProcedureId'] . ') '
                     . @$arr['srcProcedureKey'] . ' -> ' . @$currentRecord['procedure_key']
                     . ' into Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_CLONE) {
            $message = 'Cloned item (' . $arr['srcProcedureId'] . ') ' . $arr['srcProcedureName']
                     . ' [' . $arr['srcProcedureKey'] . '] into Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name']
                     . ' creating item ' . @$arr['procedure_key'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['procedure_key'])) ? @$arr['procedure_key'] : @$currentRecord['procedure_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Procedure',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => null,
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$currentRecord['is_internal'] || @$arr['is_internal'] || @$pipeline['internal'] || $this->isInternal(array(self::PRIMARY_KEY => @$currentRecord[self::PRIMARY_KEY], PipelineModel::PRIMARY_KEY => @$pipeline[PipelineModel::PRIMARY_KEY])))
            )
        );
    }
}
