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
 * Parameter Increment model
 */
class ParamIncrementModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable, ISequenceable
{
    const TABLE = 'param_increment';
    const PRIMARY_KEY = 'param_increment_id';
    const OLDEDITS_TABLE = 'param_increment_oldedits';
    const OLDEDITS_PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('weight')
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
            self::PRIMARY_KEY, 'parameter_id', 'weight', 'is_active',
            'increment_string', 'increment_type', 'increment_unit', 'increment_min',
            'deleted', 'user_id', 'time_modified'
        );
    }

    public function getByParameter($pid)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('parameter_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    private function _getByParameter($pid)
    {
        return $this->db->from(self::TABLE)
                        ->where('parameter_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function isDeleted($incId)
    {
        $inc = $this->_getById($incId);
        return (bool)@$inc['deleted'];
    }

    /**
    * @param int $id
    * @param bool $harddelete this parameter is deprecated and ignored
    * @param array $origin
    * @return bool
    */
    public function delete($id, $harddelete = false, array $origin = array())
    {
        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardDelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, TRUE, $origin);
    }

    public function undelete($id, array $origin)
    {
        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if($this->isDeleted($id))
            return (bool) $this->_setDeletedFlag($id, FALSE, $origin);

        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardUndelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, FALSE, $origin);
    }

    private function _setDeletedFlag($id, $deleted = TRUE, array $origin = array())
    {
        $deleted = ($deleted) ? 1 : 0;
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, array('deleted' => $deleted));
        $ar = $this->db->affected_rows();
		//update the parameter type if there are no more active increments and log it
        if ($ar) {
            $inc = $this->_getById($id);
            $inc = array_merge($inc, $origin);
            $this->_updateParameterTypeOnDelete($inc);
            $this->_updateParameterIncrementFlag($inc['parameter_id']); //@deprecated
            $this->_log($id, $inc, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }
        return $ar;
    }

    private function _hardDelete($id, $origin)
    {
        $record = $this->_getById($id);
        if (empty($record)) {
            ImpressLogger::log(
                ImpressLogger::WARNING,
                'Failed to delete Increment ' . $id . ' because it does not exist! Probably already deleted',
                'paramincrement',
                ImpressLogger::ACTION_DELETE
            );
            return 0;
        }

        //save a backup of the record
        $this->load->model('paramincrementdeletedmodel');
        $iid = $this->paramincrementdeletedmodel->insert($record);

        //delete and log it
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $iid = $ar = $this->db->affected_rows();
            $this->_updateParameterTypeOnDelete(array_merge($origin, $record));
            $this->_updateParameterIncrementFlag($record['parameter_id']); //@deprecated
            if ($ar) {
                $record = array_merge($record, $origin);
                $this->_log($id, $record, ChangeLogger::ACTION_DELETE);
            }
        }

        return $iid;
    }

    public function hardDeleteByParameter($parameterId, $origin)
    {
        $incs = $this->_getByParameter($parameterId);
        foreach($incs as $inc)
            $this->_hardDelete($inc[self::PRIMARY_KEY], $origin);
    }

    /**
    * @param array hash of columns
    * @param string $action
    * @return int last insert id
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        //prevent duplicates
        if ( ! $this->_isUnique($arr)) {
            return false;
        }
        
        //insert increment
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        
        //check to see if the parameter being added to has been set as an
        //incremental parameter and if not then set it accordingly
        if ($iid) {
            $this->_updateParameterType($arr);
            $this->_updateParameterIncrementFlag($arr['parameter_id']);	//@deprecated
        }
        
        //log it
        if ($iid && (in_array($action, array(ChangeLogger::ACTION_CREATE, ChangeLogger::ACTION_IMPORT, ChangeLogger::ACTION_UNDELETE)))) {
            $this->_log($iid, $arr, $action);
        }
        
        return $iid;
    }
    
    /**
     * Ensures no duplicate increments are created (case-insensitive)
     * 
     * @param array $arr submitted fields
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $increments = $this->getByParameter($arr[ParameterModel::PRIMARY_KEY]);
        if ( ! empty($arr['increment_string'])) {
            foreach ($increments as $increment) {
                if (strtolower($increment['increment_string']) == strtolower($arr['increment_string'])) {
                    return false;
                }
            }
        } else {
            foreach ($increments as $increment) {
                if ($increment['increment_type'] == $arr['increment_type'] &&
                    $increment['increment_unit'] == $arr['increment_unit']
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
    * Creates new copies of Increments from the old Parameter and associates them with the new One
    * @param int $oldParamId
    * @param int $newParamId
    * @param array $newOrigin
    * @param string $action
    * @return array|bool The ids of the newly inserted increments or FALSE on failure
    */
    public function copyIncrementsToNewParameter($oldParamId, $newParamId, array $newOrigin, $action = ChangeLogger::ACTION_VERSION)
    {
        $newIncIds = array();
        foreach ($this->getByParameter($oldParamId) as $inc) {
            unset($inc[self::PRIMARY_KEY]);
            $inc['parameter_id'] = $newParamId;
            $inc['increment_min'] = (strlen($inc['increment_min']) == 0) ? null : $inc['increment_min'];
            $inc = array_merge($inc, $newOrigin);
            $id = $this->insert($inc, $action);
            if($id === false)
                return false;
            else{
                $newIncIds[] = $id;
                $this->_updateParameterIncrementFlag($newParamId); //@deprecated
            }
        }
        return $newIncIds;
    }

    public function cloneByParameter(array $source, array $destination)
    {
        $ids = $this->copyIncrementsToNewParameter($source['parameter_id'], $destination['parameter_id'], $destination, ChangeLogger::ACTION_CLONE);
        return ($ids === false) ? false : true;
    }


    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        //save a copy of the old record
        $oldrecord = $this->_getById($id);
        $this->db->insert(self::OLDEDITS_TABLE, $oldrecord);
        //apply update
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
		//update the type of the parameter this increment belongs to and log it
        if ($ar) {
            $this->_updateParameterType($arr);
            $oldrecord[ChangeLogger::FIELD_PIPELINE] = $arr[ChangeLogger::FIELD_PIPELINE];
            $oldrecord[ChangeLogger::FIELD_PROCEDURE] = $arr[ChangeLogger::FIELD_PROCEDURE];
            $oldrecord[ChangeLogger::FIELD_PARAMETER] = $arr[ChangeLogger::FIELD_PARAMETER];
            $this->_log($id, $oldrecord, ChangeLogger::ACTION_UPDATE);
        }
        return $ar;
    }
	
    private function _updateParameterType($arr)
    {
        $parameter = $this->parametermodel->getById($arr[ParameterModel::PRIMARY_KEY]);
        if ( ! empty($arr['parameter_type']) && $parameter['type'] != $arr['parameter_type']) {
            return $this->parametermodel->update($arr[ParameterModel::PRIMARY_KEY], array(
                'type' => $arr['parameter_type'],
                'procedure_id' => $arr['procedure_id'],
                'pipeline_id' => $arr['pipeline_id']
            ));
        }
        return true;
    }
	
    private function _updateParameterTypeOnDelete($arr)
    {
        $incs = $this->_getByParameter($arr[ParameterModel::PRIMARY_KEY]);
        $anyIncs = false;
        foreach ($incs as $inc) {
            if($inc['deleted'] == 0)
                $anyIncs = true;
        }
        if ( ! $anyIncs) {
            return $this->parametermodel->update($arr[ParameterModel::PRIMARY_KEY], array(
                'type' => EParamType::SIMPLE,
                'procedure_id' => $arr['procedure_id'],
                'pipeline_id' => $arr['pipeline_id']
            ));
        }
        return true;
    }

    private function _updateParameterIncrementFlag($paramId)
    {
        $this->parametermodel->updateParameterIncrementFlag($paramId);
    }

    /**
    * @param int $incrementId
    * @return bool
    */
    public function hasDeprecatedParent($incrementId)
    {
        $inc = $this->_getById($incrementId);
        if ( ! empty($inc)) {
            $this->load->model('parametermodel');
            return $this->hasDeprecatedParentByParentId($inc[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasDeprecatedParentByParentId($pid)
    {
        return $this->parametermodel->isDeprecated($pid);
    }
	
    /**
    * @param int $incrementId
    * @return bool
    */
    public function hasInternalParent($incrementId)
    {
        $inc = $this->_getById($incrementId);
        if ( ! empty($inc)) {
            return $this->hasInternalParentByParentId($inc[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }

    public function hasInternalParentByParentId($pid)
    {
        return $this->parametermodel->isInternal($pid);
    }
    
    public function hasParentInBeta($incId)
    {
        $inc = $this->_getById($incId);
        if ( ! empty($inc)) {
            return $this->hasParentInBetaByParentId($inc[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasParentInBetaByParentId($pid)
    {
        return $this->parametermodel->isInBeta($pid);
    }

    /**
    * Move a record up or down in display order
    * @param int $incrementId
    * @param int $parameterId parent
    * @param string $direction should be either "up" or "dn"
    * @return bool success
    */
    public function move($incrementId, $parameterId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $incs = $this->_getByParameter($parameterId);

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($incs); $i++) {
                if ($incs[$i][self::PRIMARY_KEY] == $incrementId) {
                    $current = $incs[$i];
                    if(isset($incs[$i + 1]))
                        $next = $incs[$i + 1];
                    else
                        return false;
                    break;
                }
            }
            if ($current['weight'] != $next['weight']) {
                $temp = $next['weight'];
                $next['weight'] = $current['weight'];
                $current['weight'] = $temp;
            } else {
                $current['weight'] = $next['weight'] + 1;
            }
            $other = $next;
        }
        else if($direction == 'up')
        {
            $prev = null;
            for ($i = 0; $i < count($incs); $i++) {
                if ($incs[$i][self::PRIMARY_KEY] == $incrementId) {
                    $current = $incs[$i];
                    if(isset($incs[$i - 1]))
                        $prev = $incs[$i - 1];
                    else
                        return false;
                    break;
                }
            }
            if ($current['weight'] != $prev['weight']) {
                $temp = $prev['weight'];
                $prev['weight'] = $current['weight'];
                $current['weight'] = $temp;
            } else {
                $prev['weight'] = $current['weight'] + 1;
            }
            $other = $prev;
        }

        if ($current) {
            $this->db->where(self::PRIMARY_KEY, $current[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $current['weight']));
            $this->db->where(self::PRIMARY_KEY, $other[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $other['weight']));
            return (bool) $this->db->affected_rows();
        }
        return false;
    }

    public function resequence($parameterId = null)
    {
        $incs = $this->_getByParameter($parameterId);
        $counter = 0;
        foreach ($incs as $inc) {
            $this->db->where(self::PRIMARY_KEY, $inc[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $counter));
            $counter++;
        }
    }

    private function _deleteMatchingIncrementFromParameter($matchingIncrementId, $newParameterId)
    {
        $mi = $this->_getById($matchingIncrementId);
        if (empty($mi))
            return false;
        $newIncrements = $this->_getByParameter($newParameterId);
        foreach ($newIncrements as $ni) {
            if (
                $mi['increment_string'] == $ni['increment_string'] &&
                $mi['increment_type']   == $ni['increment_type']   &&
                $mi['increment_unit']   == $ni['increment_unit']   &&
                $mi['increment_min']    == $ni['increment_min']
            ) {
                $this->db->where(self::PRIMARY_KEY, $ni[self::PRIMARY_KEY])
                         ->delete(self::TABLE);
                return $this->db->affected_rows();
            }
        }
        return true;
    }

    private function _createNewParameterVersion($arr)
    {
        $this->load->helper('array_keys_exist');
        if( ! array_keys_exist($arr, array('parameter_id','nvpipeline','nvprocedure','nvrelation','nvforkprocedure','nvrelationdescription')))
            return false;
        $parameter = $this->parametermodel->getById($arr['parameter_id']);
        if(empty($parameter))
            return false;
        $newParameterId = $this->parametermodel->createNewVersion(array_merge($parameter, $arr));
        return ($newParameterId) ? $newParameterId : false;
    }

    /**
    * This method is here because of the new behavior that when an increment is
    * deleted it should trigger the creation of a new parameter version
    */
    public function createNewParentVersionAndDeleteOldItem($arr)
    {
        //make a new version of the parameter/procedure
        $newParameterId = $this->_createNewParameterVersion($arr);
        if($newParameterId === false)
            return false;

        //delete the chosen increment from the new parameter
        $delSuccessful = (bool)$this->_deleteMatchingIncrementFromParameter($arr[self::PRIMARY_KEY], $newParameterId);

        //update the status of the options flag on the parameter
        $this->load->model('originalpathwaysmodel');
        $origin = $this->originalpathwaysmodel->getPathwaysByParameter($newParameterId);
        $arr = array_merge($arr, current($origin));
        $this->_updateParameterTypeOnDelete($arr);
        $this->_updateParameterIncrementFlag($newParameterId); //@deprecated

        $arr['nvparameter'] = $arr['nvprocedure'] = $arr['nvpipeline'] = null; //stops incorrect redirection
        return ($delSuccessful) ? $arr : false;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about object being logged
        $param = $this->parametermodel->getById(@$arr[ParameterModel::PRIMARY_KEY]);
        $currentRecord = $this->_getById($id);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Increment (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['increment_string'] . ' for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'] . '. ';
            $fields = array(
                'parameter_id', 'is_active',
                'increment_string', 'increment_type',
                'increment_unit', 'increment_min'
            );
            foreach ($fields AS $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . '. ';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Increment (' . $id . ') ' . @$arr['increment_string']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Increment (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['increment_string'] . ' from Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Increment (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['increment_string'] . ' for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
            $message = 'Imported a new Increment (' . $id . ') ' . @$arr['increment_string']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(array(
            ChangeLogger::FIELD_ITEM_ID => $id,
            ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['parameter_key'])) ? @$param['parameter_key'] : @$currentRecord['parameter_key'],
            ChangeLogger::FIELD_ITEM_TYPE => 'Parameter Increment',
            ChangeLogger::FIELD_ACTION => $action,
            ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
            ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
            ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
            ChangeLogger::FIELD_MESSAGE => $message,
            ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$param['internal'] || $this->hasInternalParentByParentId(@$currentRecord[ParameterModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$arr[ParameterModel::PRIMARY_KEY]))
        ));
    }
}
