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
 * Parameter Option model
 */
class ParamOptionModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable, ISequenceable
{
    const TABLE = 'param_option';
    const PRIMARY_KEY = 'param_option_id';
    const OLDEDITS_TABLE = 'param_option_oldedits';
    const OLDEDITS_PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->get()
                        ->result_array();
    }

    public function getById($id)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByParameter($pid)
    {		
        $this->load->model('parameterhasoptionsmodel');
        return $this->db->select('opt.*, php.weight')
                        ->from(self::TABLE . ' AS opt')
                        ->join(ParameterHasOptionsModel::TABLE . ' php', 'php.' . self::PRIMARY_KEY . ' = opt.' . self::PRIMARY_KEY, 'inner')
                        ->where('php.' . ParameterModel::PRIMARY_KEY, $pid)
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }

    public function isDeleted($optionId)
    {
        $option = $this->_getById($optionId);
        return (bool)@$option['deleted'];
    }
	
    /**
    * @param int $id
    * @param bool $harddelete deprecated and ignored parameter
    * @return bool
    */
    public function delete($id, $harddelete = false, array $origin = array())
    {
        //if more than one parameter using the option then delete the link rather than the option itself
        $this->load->model('parameterhasoptionsmodel');
        $links = $this->parameterhasoptionsmodel->getByOption($id);
        if (count($links) >= 2) {
            $option = $this->_getById($id);
            $del = (bool) $this->parameterhasoptionsmodel->delete($origin['parameter_id'], $id);
            if ($del) {
                $this->_log($id, array_merge($option, $origin), ChangeLogger::ACTION_DELETE);
            }
            return $del;
        }
	
        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardDelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, true, $origin);
    }

    public function undelete($id, array $origin)
    {
        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if($this->isDeleted($id))
            return (bool) $this->_setDeletedFlag($id, false, $origin);
	
        if($this->config->item('delete_mode') == 'hard')
            return false; //(bool) $this->_hardUndelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, false, $origin);
    }

    private function _setDeletedFlag($id, $deleted = true, array $origin = array())
    {
        $deleted = ($deleted) ? 1 : 0;
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, array('deleted' => $deleted));
        $ar = $this->db->affected_rows();
        $this->_updateParameterOptionFlag($id);
        if ($ar) {
            $record = array_merge($this->_getById($id), $origin);
            $this->_log($id, $record, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }
        return $ar;
    }

    private function _hardDelete($id, $origin)
    {
        $record = $this->_getById($id);
        //check the record exists  - if it doesn't it may have already been deleted
        if (empty($record)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Option ' . $id . ' because it does not exist! Probably already deleted', 'paramoption', ImpressLogger::ACTION_DELETE);
            return 0;
        }
	
        $this->load->model('parameterhasoptionsmodel');
        $options = $this->parameterhasoptionsmodel->getByOption($id);
        if (empty($options)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Option ' . $id . ' because it does not exist! Probably already deleted', 'paramoption', ImpressLogger::ACTION_DELETE);
            return 0;
        }
		
        //if the option has children then you can't delete it - you're expected to delete the children before the parent
        foreach ($options as $option) {
            $children = $this->getChildOptionsForParent($option[self::PRIMARY_KEY]);
            if( ! empty($children) && $this->config->item('child_deletion') === false)
                return 0;
        }
		
        //store a backup of the option in the deleted table
        $this->load->model('paramoptiondeletedmodel');
        $iid = $this->paramoptiondeletedmodel->insert($record);
		
        //delete
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $ar = $this->db->affected_rows();
            if ($ar) {
                $currentRecord = array_merge($record, $origin);
                $this->_log($id, $currentRecord, ChangeLogger::ACTION_DELETE);
            }
            //delete any relationships for this option
            $this->load->model('paramoptionrelationsmodel');
            $this->paramoptionrelationsmodel->deleteByOptionOrParent($id);
            //set parameter is_option flag to 0 if it has no more options associated with it
            foreach ($options as $option) {
                $this->_updateParameterOptionFlagForParameter($option['parameter_id']);
            }
        }

        return $iid;
    }

    public function hardDeleteOptionsForParameter($paramId, array $origin)
    {
        $this->load->model('parameterhasoptionsmodel');
        $options = $this->parameterhasoptionsmodel->getByParameter($paramId, false);
        foreach ($options as $option)
            $this->_hardDelete($option[self::PRIMARY_KEY], $origin);
        $this->_updateParameterOptionFlagForParameter($paramId);
    }

    public function getChildOptionsForParent($parentId)
    {
        return $this->db->from(self::TABLE)
                        ->where('parent_id', $parentId)
                        ->get()
                        ->result_array();
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
	
    public function getFields()
    {
        return array(
            self::PRIMARY_KEY, 'parent_id', 'name', 'is_default',
            'is_active', 'description', 'deleted', 'user_id',
            'time_modified'
        );
    }

    /**
    * @param array $arr hash of columns
    * @param string $action
    * @return int last insert id
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        //stop duplicates being created
        if ( ! $this->_isUnique($arr)) {
            return false;
        }
        
        //insert the new option
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        
        //tie up to parameter_has_options table
        if ($iid) {
            $this->load->model('parameterhasoptionsmodel');
            $weight = (isset($arr['weight'])) ? $arr['weight'] : null;
            $phoid = $this->parameterhasoptionsmodel->insert($arr['parameter_id'], $iid, $weight);
            if ($phoid === false) {
                return false;
            }
        }
        
        //log it
        if ($iid) { // && (in_array($action, array(ChangeLogger::ACTION_CREATE, ChangeLogger::ACTION_IMPORT, ChangeLogger::ACTION_UNDELETE))))
            $this->_log($iid, $arr, $action);
        }
        
        //update the options flag of the parameter
        $this->_updateParameterOptionFlag($iid);
        
        return $iid;
    }
    
    /**
     * To prevent duplicate options being created, the option label (name) is
     * checked (case-insensitive)
     * 
     * @param array $arr
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $this->load->model('parameterhasoptionsmodel');
        $options = $this->parameterhasoptionsmodel->getByParameter($arr[ParameterModel::PRIMARY_KEY]);
        foreach ($options as $option) {
            if (strtolower($option['name']) == strtolower($arr['name'])) {
                return false;
            }
        }
        return true;
    }

    /**
    * @param int $optionId
    * @param array hash of columns with versioning info
    * @return mixed last updated id, or in the case of a new version being triggered a hash array
    */
    public function update($optionId, $arr)
    {
        $this->load->model('notinbetamodel');
        $parameter = $this->parametermodel->getById($arr[ParameterModel::PRIMARY_KEY]);
        if(empty($parameter))
            return false;
        
        //@todo look at this again another time to see if this is all necessary
        if($this->isCreationOfNewVersionRequired($optionId, $arr) &&
           $this->parametermodel->isLatestVersion($arr) &&
           ! $this->parametermodel->isInternal($parameter[ParameterModel::PRIMARY_KEY]) &&
           $this->notinbetamodel->keyIsInBeta($parameter['parameter_key'])
        ) {
            return $this->_versionTriggeringUpdate($optionId, $arr);
        } else {
            return $this->_normalUpdate($optionId, $arr);
        }
    }

    /**
    * @param int $optionId The id of the old option that needs to be versioned
    * @param array $arr A hash of the values passed in from the editing form
    * @return array|bool False if something went wrong or an $origin style hash array
    * of the location of the newly created option
    */
    private function _versionTriggeringUpdate($optionId, $arr)
    {        
        //get weight of option so it sticks it back in same position
        $this->load->model('parameterhasoptionsmodel');
        $link = $this->parameterhasoptionsmodel->getByParameterAndOption($arr[ParameterModel::PRIMARY_KEY], $optionId, false);

        //create new version
        $newOrigin = $this->createNewParentVersionAndDeleteOldItem($arr);
        if(empty($newOrigin))
            return false;
        
        $arr['parameter_id'] = $newOrigin['parameter_id'];
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();
        $arr['weight'] = (int)@$link['weight'];
        $arr['old_option_id'] = $optionId;
        $iid = $this->insert($arr, ChangeLogger::ACTION_VERSION);

        //remove the old option from the new parameter
        // $this->load->model('parameterhasoptionsmodel');
        // $this->parameterhasoptionsmodel->delete($newParameterId, $optionId);
        
        if ($iid) {
            // $this->load->model('originalpathwaysmodel');
            // $origin = current($this->originalpathwaysmodel->getPathwaysByParameter($newOrigin['parameter_id']));
            $newOrigin[self::PRIMARY_KEY] = $iid;
            return $newOrigin;
        }
        
        return false;
    }

    private function _normalUpdate($id, $arr)
    {
        //save a copy of the old record
        $beforeChange = $this->_getById($id);
        $this->db->insert(self::OLDEDITS_TABLE, $beforeChange);
        //apply update
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
        //log it
        if ($ar) {
            $beforeChange[ChangeLogger::FIELD_PIPELINE]  = $arr[ChangeLogger::FIELD_PIPELINE];
            $beforeChange[ChangeLogger::FIELD_PROCEDURE] = $arr[ChangeLogger::FIELD_PROCEDURE];
            $beforeChange[ChangeLogger::FIELD_PARAMETER] = $arr[ChangeLogger::FIELD_PARAMETER];
            $this->_log($id, $beforeChange, ChangeLogger::ACTION_UPDATE);
        }
        return $ar;
    }

    /**
     * @param array $arr
     * @return array $origin style array or false on failure
     */
    public function createNewParentVersionAndDeleteOldItem($arr)
    {
        $this->load->helper('array_keys_exist');
        $requiredFields = array(
                self::PRIMARY_KEY, 'pipeline_id', 'procedure_id',
                'parameter_id', 'nvpipeline', 'nvprocedure',
                'nvrelation', 'nvforkprocedure', 'nvrelationdescription',
                'nvuseoldpipelinekey'
        );
        if( ! array_keys_exist($arr, $requiredFields))
            return false;
        $oldParameterId = $arr[ParameterModel::PRIMARY_KEY];
        $parameter = $this->parametermodel->getById($oldParameterId);
        if(empty($parameter))
            return false;
        //make a new version of the parameter/procedure and delete old items
        $parameter['delete_option_id'] = $arr[self::PRIMARY_KEY];
        $parameter['nvdeleteolditem'] = true;
        $parameter['delete_parameter_id'] = $oldParameterId;
        foreach ($requiredFields as $field)
                $parameter[$field] = $arr[$field];
        $newParameterId = $this->parametermodel->createNewVersion($parameter);
        if($newParameterId === false)
            return false;
			
        //delete the option from the new parameter
        // $this->load->model('parameterhasoptionsmodel');
        // $ret = (bool) $this->parameterhasoptionsmodel->delete($newParameterId, $arr[self::PRIMARY_KEY]);

        //update the status of the options flag on the parameter
        // $this->_updateParameterOptionFlagForParameter($newParameterId);

        $this->load->model('originalpathwaysmodel');
        $origin = current($this->originalpathwaysmodel->getPathwaysByParameter($newParameterId));
		
        return ($newParameterId) ? $origin : false;
    }
    
    public function createNewVersion($arr)
    {
        $originalItem = $this->_getById($arr[self::PRIMARY_KEY]);
        $originalItem['pipeline_id']  = $arr['pipeline_id'];
        $originalItem['procedure_id'] = $arr['procedure_id'];
        $originalItem['parameter_id'] = $arr['parameter_id'];
        $newOrigin = $this->createNewParentVersionAndDeleteOldItem($arr);
        if ($newOrigin === false || empty($newOrigin)) {
            return false;
        }
        $arr = array_merge($arr, $newOrigin);
        $iid = $this->insert($arr);
        if ($iid) {
            $this->load->model('paramoptionrelationsmodel');
            $this->paramoptionrelationsmodel->insert(array(
                self::PRIMARY_KEY => $iid,
                'parent_id'       => $iid, //$originalItem[self::PRIMARY_KEY],
                'relationship'    => (isset($arr['nvoption_relation'])) ? $arr['nvoption_relation'] : ERelationType::EQUIVALENT,
                'description'     => (isset($arr['nvoption_relationdescription'])) ? $arr['nvoption_relationdescription'] : null,
                'connection'      => ERelationConnection::RELATION,
                'from_parameter'  => $originalItem['parameter_id'],
                'to_parameter'    => $arr['parameter_id'],
                'parameter_id'    => $arr['parameter_id'],
                'procedure_id'    => $arr['procedure_id'],
                'pipeline_id'     => $arr['pipeline_id']
            ));
        }
        return $iid;
    }

    private function _updateParameterOptionFlagForParameter($parameterId)
    {
        return $this->parametermodel->updateOptionFlagForParameter($parameterId);
    }

    private function _updateParameterOptionFlag($optionId)
    {
        $this->parametermodel->updateParameterOptionFlagForOption($optionId);
    }
	
    public function cloneByParameter(array $source, array $destination, $exceptOption = null)
    {
        $srcOptions = $this->getByParameter($source['parameter_id']);
        if(empty($srcOptions))
            return true;
        $allOptionsCopied = true;
        $exceptOption = (array)$exceptOption;
        $this->load->model('paramoptionrelationsmodel');
        foreach ($srcOptions as $newOption) {
            if($newOption['deleted'])
                continue;
            if (in_array($newOption[self::PRIMARY_KEY], $exceptOption))
                continue;
            $newOption['old_option_id'] = $newOption[self::PRIMARY_KEY];
            $newOption['pipeline_id'] = $destination['pipeline_id'];
            $newOption['procedure_id'] = $destination['procedure_id'];
            $newOption['parameter_id'] = $destination['parameter_id'];
            $newOption['user_id'] = User::getId();
            $newOption['time_modified'] = $this->config->item('timestamp');
            $newOption['description'] = (empty($newOption['description'])) ? null : $newOption['description'];
            $iid = $this->insert($newOption, ChangeLogger::ACTION_CLONE);
            if ($iid) {
                $this->paramoptionrelationsmodel->insert(array(
                    self::PRIMARY_KEY => $iid,
                    'parent_id'       => $newOption['old_option_id'],
                    'relationship'    => (isset($source['nvoption_relation'])) ? $source['nvoption_relation'] : ERelationType::EQUIVALENT,
                    'description'     => (isset($source['nvoption_relationdescription'])) ? $source['nvoption_relationdescription'] : null,
                    'connection'      => ERelationConnection::RELATION,
                    'from_parameter'  => $source['parameter_id'],
                    'to_parameter'    => $destination['parameter_id'],
                    'parameter_id'    => $destination['parameter_id'],
                    'procedure_id'    => $destination['procedure_id'],
                    'pipeline_id'     => $destination['pipeline_id']
                ));
            } else {
                log_message('info', 'error cloning option ' . print_r($newOption, true));
                $allOptionsCopied = false;
            }
        }
        return $allOptionsCopied;
    }
	
    /**
    * Move a record up or down in display order
    * @param int $optionId option id
    * @param int $parameterId the id of the parameter to which this option belongs
    * @param string $direction should be either "up" or "dn"
    * @see parameterhasoptionsmodel::move()
    */
    public function move($optionId, $parameterId, $direction)
    {
        $this->load->model('parameterhasoptionsmodel');
        return $this->parameterhasoptionsmodel->move($optionId, $parameterId, $direction);
    }
	
    public function resequence($parameterId = null)
    {
        $this->load->model('parameterhasoptionsmodel');
        $this->parameterhasoptionsmodel->resequence((int)$parameterId);
    }
	
    /**
    * @param int $optionId
    * @return bool
    */
    public function hasDeprecatedParent($optionId)
    {
        $this->load->model('parameterhasoptionsmodel');
        $rows = $this->parameterhasoptionsmodel->getByOption($optionId);
        foreach ($rows as $row) {
                if ($this->hasDeprecatedParentByParentId($row[ParameterModel::PRIMARY_KEY]))
                        return true;
        }
        return false;
    }

    public function hasDeprecatedParentByParentId($parameterId)
    {
        return $this->parametermodel->isDeprecated($parameterId);
    }

    public function hasInternalParent($optionId)
    {
        $this->load->model('parameterhasoptionsmodel');
        $rows = $this->parameterhasoptionsmodel->getByOption($optionId);
        foreach ($rows as $row) {
                if ($this->hasInternalParentByParentId($row[ParameterModel::PRIMARY_KEY]))
                        return true;
        }
        return false;
    }

    /**
    * @param int $parameterId
    * @return bool
    */
    public function hasInternalParentByParentId($parameterId)
    {
        return $this->parametermodel->isInternal($parameterId);
    }

    public function hasParentInBeta($optionId)
    {
        $this->load->model('parameterhasoptionsmodel');
        $rows = $this->parameterhasoptionsmodel->getByOption($optionId);
        foreach ($rows as $row) {
            if ($this->hasParentInBetaByParentId($row[ParameterModel::PRIMARY_KEY]))
                return true;
        }
        return false;
    }
   
    public function hasParentInBetaByParentId($parameterId)
    {
        return $this->parametermodel->isInBeta($parameterId);
    }
    
    /**
     * @param int $optionId The option being edited
     * @param array $arr A hash of the fields and the values intended to be changed
     * @return bool
     */
    public function isCreationOfNewVersionRequired($optionId, array $arr)
    {
        if ( ! $this->config->item('version_triggering')) {
            return false;
        }
        
        $option = $this->_getById($optionId);
        if (empty($option)) {
            return false;
        }
        
        //if the names don't match then a new version needs to be created...
        //unless the parameter for this option has not been pushed to beta/live
        //all other fields don't need to be checked for
        if (isset($arr['name'])) {
            if ($option['name'] != $arr['name'] &&
                $this->hasParentInBetaByParentId($arr[ParameterModel::PRIMARY_KEY])
            ) {
                return true;
            }
        }
        
        //the is_default flag might become significant but it's usage is currently
        //inconsistent so I've temporarily commented this test out
        // if (isset($arr['is_default'])) {
            // if ($option['is_default'] != $arr['is_default'] &&
            //     $this->hasParentInBetaByParentId($arr[ParameterModel::PRIMARY_KEY])
            // ) {
                // return true;
            // }
        // }
        
        return false;
    }
        
    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about object being logged
        $param = $this->parametermodel->getById(@$arr[ParameterModel::PRIMARY_KEY]);
        $currentRecord = $this->_getById($id);
        if (isset($arr['old_option_id']))
            $oldOption = $this->_getById($arr['old_option_id']);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Option (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'] . '. ';
            foreach (array('parent_id','name','is_default','is_active','description') as $field) {
                if(@$arr[$field] != @$currentRecord[$field])
                    $message .= $field . ' changed from ' . @$arr[$field] . ' to ' . @$currentRecord[$field] . '. ';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Option (' . $id . ') ' . @$arr['name']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Option (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' from Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
            $message = 'Imported a new Option (' . $id . ') ' . @$arr['name']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Option (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_VERSION) {
            $message = 'New version of option (' . @$oldOption[self::PRIMARY_KEY]
                     . ') ' . @$oldOption['name'] . ' created with updated name ('
                     . @$arr[self::PRIMARY_KEY] . ') ' . @$arr['name'];
        } else if ($action == ChangeLogger::ACTION_CLONE) {
            $message = 'Clone of option (' . @$oldOption[self::PRIMARY_KEY] . ') created '
                     . 'with new id ' . @$currentRecord[self::PRIMARY_KEY] . ' for Parameter '
                     . '(' . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID   => $id,
                ChangeLogger::FIELD_ITEM_KEY  => (empty($currentRecord['parameter_key'])) ? @$param['parameter_key'] : @$currentRecord['parameter_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Parameter Option',
                ChangeLogger::FIELD_ACTION    => $action,
                ChangeLogger::FIELD_PIPELINE  => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
                ChangeLogger::FIELD_MESSAGE   => $message,
                ChangeLogger::FIELD_INTERNAL  => (int)(bool) (@$param['internal'] || $this->hasInternalParentByParentId(@$arr[ParameterModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$currentRecord[ParameterModel::PRIMARY_KEY]))
            )
        );
    }
}
