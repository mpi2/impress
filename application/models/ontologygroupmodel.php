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
 * Ontology Group Model
 */
class OntologyGroupModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable
{
    const TABLE = 'ontology_group';
    const PRIMARY_KEY = 'ontology_group_id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('name')
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

    public function getByName($name)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('name', $name)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _getByName($name)
    {
        return $this->db->from(self::TABLE)
                        ->where('name', $name)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByParameter($pid)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        $ogs = $this->parameterhasontologygroupsmodel->getByParameter($pid);
        $groupIds = array();
        foreach($ogs AS $og)
            $groupIds[] = $og[self::PRIMARY_KEY];
        if(empty($groupIds))
            return array();
        return $this->db->from(self::TABLE)
                        ->where_in(self::PRIMARY_KEY, $groupIds)
                        ->get()
                        ->result_array();
    }

    public function getRandomRow()
    {
        return $this->db->from(self::TABLE)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function isDeleted($groupId)
    {
        $group = $this->_getById($groupId);
        return (bool)@$group['deleted'];
    }

    public function delete($id, $hardelete = false, array $origin)
    {
        //if a group is being used by two or more parameters then just unlink it from the parameter
        $this->load->model('parameterhasontologygroupsmodel');
        $paramsUsingGroup = $this->parameterhasontologygroupsmodel->getByOntologyGroup($id);
        if (count($paramsUsingGroup) >= 2) {
            $group = $this->_getById($id);
            $del = (bool) $this->parameterhasontologygroupsmodel->delete($origin['parameter_id'], $id);
            if ($del) {
                    $this->_log($id, array_merge($group, $origin), ChangeLogger::ACTION_DELETE);
            }
            return $del;
        }
	
        if ($this->config->item('delete_mode') == 'hard') {
            return (bool) $this->_hardDelete($id, $origin);
        }
        return (bool) $this->_setDeletedFlag($id, true, $origin);
    }

    public function _hardDelete($id, $origin)
    {
        $record = $this->_getById($id);
        if(empty($record))
            return false;
		
		//first check if the group contains ontologies and delete them if config settings permit
        $this->load->model('paramontologyoptionmodel');
        $ontologies = $this->paramontologyoptionmodel->getByOntologyGroup($id);
        if ($this->config->item('child_deletion') === false && count($ontologies) >= 1)
            return false;
        foreach ($ontologies as $ontology) {
            if ( ! $this->paramontologyoptionmodel->delete($ontology[ParamOntologyOptionModel::PRIMARY_KEY], true, $origin))
                return false;
        }

        //delete the ontology group
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        $ar = $this->db->affected_rows();

        //log it
        if ($ar) {
            $record = array_merge($record, $origin);
            $this->_log($id, $record, ChangeLogger::ACTION_DELETE);
        }

        return $ar;
    }

    /**
    * Check the item hasn't already been soft deleted and if it has then soft-undelete it
    */
    public function undelete($id, array $origin)
    {
        return ($this->isDeleted($id)) ? (bool)$this->_setDeletedFlag($id, false, $origin) : false;
    }

    private function _setDeletedFlag($id, $deleted = true, array $origin)
    {
        $deleted = ($deleted) ? 1 : 0;
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, array('deleted' => $deleted));
        $ar = $this->db->affected_rows();
        if ($ar) {
            $record = array_merge($this->_getById($id), (array)$origin);
            $this->_log($id, $record, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }
        return $ar;
    }

    public function deleteOntologyGroupsForParameter($parameterId)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        $groups = $this->parameterhasontologygroupsmodel->getByParameter($parameterId);
        foreach ($groups as $group) {
            if ( ! $this->delete($group[self::PRIMARY_KEY]))
                return false;
        }
        return true;
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }

    private function _getFields()
    {
        return array(
            self::PRIMARY_KEY, 'name', 'is_active',
            'user_id', 'time_modified', 'deleted'
        );
    }

    /**
    * @param array hash of columns
    * @param string $action
    * @return int last insert id
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        if ($this->isValidName($arr) && isset($arr['parameter_id'])) {
            $arr['user_id'] = User::getId();
            $arr['time_modified'] = $this->config->item('timstamp');
            $this->db->insert(self::TABLE, $this->_filterFields($arr));
            $iid = $this->db->insert_id();

            //link up to parameter_has_ontologygroups table
            if ($iid) {
                $this->load->model('parameterhasontologygroupsmodel');
                $nid = $this->parameterhasontologygroupsmodel->insert($arr['parameter_id'], $iid);
                //log it
                if ($nid) {
                    $action = ($action == ChangeLogger::ACTION_CLONE) ? ChangeLogger::ACTION_CREATE : $action;
                    if ($nid && (in_array($action, array(ChangeLogger::ACTION_CREATE, ChangeLogger::ACTION_IMPORT, ChangeLogger::ACTION_UNDELETE))))
                        $this->_log($iid, $arr, $action);
                } else {
                    return false;
                }
            }

            return $iid;
        }
        return false;
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        $beforeChange = $this->_getById($id);
        if ($this->isValidName($arr)) {
            $arr['time_modified'] = $this->config->item('timestamp');
            $arr['user_id'] = User::getId();
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->update(self::TABLE, $this->_filterFields($arr));
            $ar = $this->db->affected_rows();
            //log it
            if ($ar) {
                $beforeChange[ChangeLogger::FIELD_PIPELINE] = $arr[ChangeLogger::FIELD_PIPELINE];
                $beforeChange[ChangeLogger::FIELD_PROCEDURE] = $arr[ChangeLogger::FIELD_PROCEDURE];
                $beforeChange[ChangeLogger::FIELD_PARAMETER] = $arr[ChangeLogger::FIELD_PARAMETER];
                $this->_log($id, $beforeChange, ChangeLogger::ACTION_UPDATE);
            }
            return $ar;
        }
        return false;
    }

    private function isValidName(array $arr)
    {
        //check if it is set
        if ( ! isset($arr['name']))
            return false;
        //check if it is unique
        $rows = $this->_getByName($arr['name']);
        return 0 == count($rows);
    }

    /**
    * Move a record up or down in display order
    * @param int $groupId Ontology Group Id
    * @param int $parameterId the id of the parameter to which this ontology group belongs
    * @param string $direction should be either "up" or "dn"
    * @see parameterhasontologygroupsmodel::move()
    */
    public function move($groupId, $parameterId, $direction)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        return $this->parameterhasontologygroupsmodel->move($groupId, $parameterId, $direction);
    }

    public function resequence($parameterId = null)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        $this->parameterhasontologygroupsmodel->resequence((int)$parameterId);
    }

    public function hasDeprecatedParent($groupId)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        $rows = $this->parameterhasontologygroupsmodel->getByOntologyGroup($groupId);
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
        
    public function hasInternalParent($groupId)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        $rows = $this->parameterhasontologygroupsmodel->getByOntologyGroup($groupId);
        foreach ($rows as $row) {
            if ($this->hasInternalParentByParentId($row[ParameterModel::PRIMARY_KEY]))
                return true;
        }
        return false;
    }
	
    public function hasInternalParentByParentId($parameterId)
    {
        return $this->parametermodel->isInternal($parameterId);
    }
    
    public function hasParentInBeta($groupId)
    {
        $this->load->model('parameterhasontologygroupsmodel');
        $rows = $this->parameterhasontologygroupsmodel->getOntologyGroup($groupId);
        foreach ($rows as $row) {
            if ($this->hasParentInBetaByParentId($row[ParameterModel::PRIMARY_KEY]))
                return true;
        }
        return false;
    }
    
    public function hasParentInBetaByParentId($pid)
    {
        return $this->parametermodel->isInBeta($pid);
    }

    public function cloneByParameter(array $source, array $destination)
    {
        // $srcGroups = $this->getByParameter($source['parameter_id']);
        // if(empty($srcGroups))
            // return true;
        // $groupsCopied = true;
        // foreach ($srcGroups AS $newGroup) {
            // if($newGroup['deleted'])
                // continue;
            // unset($newGroup[self::PRIMARY_KEY]);
            // $newGroup['pipeline_id'] = $destination['pipeline_id'];
            // $newGroup['procedure_id'] = $destination['procedure_id'];
            // $newGroup['parameter_id'] = $destination['parameter_id'];
            // $newGroup['user_id'] = User::getId();
            // $newGroup['time_modified'] = $this->config->item('timestamp');
            // if ( ! $this->insert($newGroup, ChangeLogger::ACTION_CLONE))
                // $groupsCopied = false;
        // }
        // return $groupsCopied;
		
        //The above implentation would create brand new copies, and it would also fail as name is not unique
        //This re-implementation would soft-link the groups associated with the source Parameter to
        //the new destination parameter
        $this->load->model('parameterhasontologygroupsmodel');
        return false !== $this->parameterhasontologygroupsmodel->copyGroupsToNewParameter($source['parameter_id'], $destination['parameter_id']);
    }
    
    /**
     * When an ontology group is deleted it should trigger the creation of a new
     * parameter version
     * @param array $arr fields
     * @return array New location of item as Origin-style array
     */
    public function createNewParentVersionAndDeleteOldItem(array $arr)
    {
        $this->load->helper('array_keys_exist');
        if ( ! array_keys_exist($arr, array(
            'parameter_id', 'nvpipeline', 'nvprocedure',
            'nvrelation', 'nvforkprocedure', 'nvrelationdescription'))
        ) {
            return false;
        }
        
        //create new parameter
        $parameter = $this->parametermodel->getById($arr[ParameterModel::PRIMARY_KEY]);
        if (empty($parameter)) {
            return false;
        }
        $newParameterId = $this->parametermodel->createNewVersion(array_merge($parameter, $arr));
        if ($newParameterId === false) {
            return false;
        }

        //delete the selected ontology group from the new parameter
        $newOrigin = array(
            'pipeline_id'  => $arr['nvpipeline'],
            'procedure_id' => $arr['nvprocedure'],
            'parameter_id' => $newParameterId
        );
        $delSuccessful = (bool)$this->delete($arr[self::PRIMARY_KEY], true, $newOrigin);

	$arr['nvparameter'] = $arr['nvprocedure'] = $arr['nvpipeline'] = null; //stops incorrect redirection
        return ($delSuccessful) ? $arr : false;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about object being logged
        $param = $this->parametermodel->getById(@$arr[ParameterModel::PRIMARY_KEY]);
        $procedure = $this->proceduremodel->getById(@$arr[ProcedureModel::PRIMARY_KEY]);
        $pipeline = $this->pipelinemodel->getById(@$arr[PipelineModel::PRIMARY_KEY]);
        $currentRecord = $this->_getById($id);

        //prepare message
        $message = '';
        if ($action == ChangeLogger::ACTION_UPDATE) {
            if ($arr['name'] != $currentRecord['name']) {
            $message = 'Updated Ontology Group (' . @$arr[self::PRIMARY_KEY] . ') - '
                     . 'Group name changed from ' . $arr['name'] . ' to ' . $currentRecord['name']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] 
                     . ') ' . @$procedure['name'] . ' of Pipeline (' 
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
            } else {
                return true;
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Ontology Group (' . $id . ') ' . @$arr['name']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] 
                     . ') ' . @$procedure['name'] . ' of Pipeline (' 
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Ontology Group (' . @$id . ') '
                     . @$arr['name'] . ' from Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] 
                     . ') ' . @$procedure['name'] . ' of Pipeline (' 
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
            $message = 'Imported a new Ontology Group (' . $id . ') ' . @$arr['name']
                     . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] 
                     . ') ' . @$procedure['name'] . ' of Pipeline (' 
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Ontology Group (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] 
                     . ') ' . @$procedure['name'] . ' of Pipeline (' 
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(array(
            ChangeLogger::FIELD_ITEM_ID => $id,
            ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['parameter_key'])) ? @$param['parameter_key'] : @$currentRecord['parameter_key'],
            ChangeLogger::FIELD_ITEM_TYPE => 'Ontology Group',
            ChangeLogger::FIELD_ACTION => $action,
            ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
            ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
            ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
            ChangeLogger::FIELD_MESSAGE => $message,
            ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$param['internal'] || @$procedure['internal'] || @$pipeline['internal']) //|| $this->hasInternalParentByParentId(@$currentRecord[ParameterModel::PRIMARY_KEY]) //not full check because OntologyGroups are global and not hideable
        ));
    }
}
