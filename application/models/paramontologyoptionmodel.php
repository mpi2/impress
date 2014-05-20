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
 * Parameter Ontology Option Modell
 */
class ParamOntologyOptionModel extends CI_Model implements IUserIdCheckable, ISequenceable
{
    const TABLE = 'param_ontologyoption';
    const PRIMARY_KEY = 'param_ontologyoption_id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('ontology_group_id')
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

    public function getByOntologyGroup($og)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('ontology_group_id', $og)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    private function _getByOntologyGroup($og)
    {
        return $this->db->from(self::TABLE)
                        ->where('ontology_group_id', $og)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function isDeleted($id)
    {
        $item = $this->_getById($id);
        return (bool)@$item['deleted'];
    }

    public function hasDeprecatedParent($optionId)
    {
        $option = $this->_getById($optionId);
        if ( ! empty($option)) {
            $this->load->model('ontologygroupmodel');
            return $this->hasDeprecatedParentByParentId($option[OntologyGroupModel::PRIMARY_KEY]);
        }
        return false;
    }

    public function hasDeprecatedParentByParentId($groupId)
    {
        $this->load->model('ontologygroupmodel');
        return $this->ontologygroupmodel->hasDeprecatedParent($groupId);
    }
    
    public function hasInternalParent($optionId)
    {
        $option = $this->_getById($optionId);
        if ( ! empty($option)) {
            $this->load->model('ontologygroupmodel');
            return $this->hasInternalParentByParentId($option[OntologyGroupModel::PRIMARY_KEY]);
        }
        return false;        
    }
    
    public function hasInternalParentByParentId($groupId)
    {
        $this->load->model('ontologygroupmodel');
        return $this->ontologygroupmodel->hasInternalParent($groupId);
    }
    
    public function hasParentInBeta($optionId)
    {
        $option = $this->_getById($optionId);
        if ( ! empty($option)) {
            $this->load->model('ontologygroupmodel');
            return $this->hasParentInBetaByParentId($option[OntologyGroupModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasParentInBetaByParentId($groupId)
    {
        $this->load->model('ontologygroupmodel');
        return $this->ontologygroupmodel->hasParentInBeta($groupId);
    }

    public function delete($id, $harddelete = false, array $origin = array())
    {
        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardDelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, true, $origin);
    }

    public function deleteByOntologyGroup($og, $origin)
    {
        $rows = $this->_getByOntologyGroup($og);
        $numAffectedRows = 0;
        foreach($rows as $row)
            $numAffectedRows += (int)(bool) $this->delete($row[self::PRIMARY_KEY], $origin);
        return $numAffectedRows;
    }

    private function _hardDelete($id, $origin)
    {
        $item = $this->_getById($id);
        if(empty($item))
            return 0;

        //if the active item deletion setting is 'on' and one or more parameters
        //are using the group where this option resides, then forbid deletion
        if ($this->config->item('active_item_deletion') === false) {
            if($this->parametermodel->getNumParametersWithOntologyGroup($item['ontology_group_id']) > 0)
                return 0;
        }

        //save backup
        $this->load->model('paramontologyoptiondeletedmodel');
        $iid = $this->paramontologyoptiondeletedmodel->insert($item, (array)$origin);

        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $iid = $this->db->affected_rows();
            if ($iid) {
                $this->_log($id, array_merge($item, (array)$origin), ChangeLogger::ACTION_DELETE);
            }
        }

        return $iid;
    }

    /**
    * @param int $id
    * @param mixed $deleted TRUE sets the deleted flag to 1, FALSE sets it to 0 and effectively undeletes it
    */
    private function _setDeletedFlag($id, $deleted = true, array $origin = array())
    {
        $deleted = ($deleted) ? 1 : 0;
        $arr = array('deleted' => $deleted, 'time_modified' => $this->config->item('timestamp'), 'user_id' => User::getId());
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $arr);
        $ar = $this->db->affected_rows();
        if ($ar) {
            $currentRecord = array_merge($this->_getById($id), (array)$origin);
            $this->_log($id, $currentRecord, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }
        return $ar;
    }

    /**
    * @param int $id
    * @param array $origin In this case the $origin will be ignored because it
    * is not directly linked to any 3Ps
    * @return bool
    */
    public function undelete($id, array $origin = array())
    {
        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if($this->isDeleted($id))
            return (bool) $this->_setDeletedFlag($id, false, $origin);

        if($this->config->item('delete_mode') == 'hard')
            return false;
        return (bool) $this->_setDeletedFlag($id, false, $origin);
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
            self::PRIMARY_KEY, 'ontology_term', 'ontology_id', 'ontology_group_id',
            'weight', 'is_active', 'is_default', 'is_collapsed',
            'user_id', 'time_modified', 'deleted'
        );
    }

    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        //prevent duplicates
        if ( ! $this->_isUnique($arr)) {
            return false;
        }
        
        $arr['user_id'] = User::getId();
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['weight'] = (isset($arr['weight'])) ? abs((int)$arr['weight']) : 1 + $this->_getMaxWeightByOntologyGroup(@$arr['ontology_group_id']);
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        if ($iid) {
            $action = ($action == ChangeLogger::ACTION_CLONE) ? ChangeLogger::ACTION_CREATE : $action;
            $this->_log($iid, $arr, $action);
        }
        return $iid;
    }
    
    /**
     * Prevent duplicates
     * 
     * @param array $arr
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $onts = $this->_getByOntologyGroup($arr[OntologyGroupModel::PRIMARY_KEY]);
        foreach ($onts as $ont) {
            if ($ont['ontology_id'] == $arr['ontology_id']) {
                return false;
            }
        }
        return true;
    }

    public function update($id, $arr)
    {
        $beforeChange = $this->_getById($id);
        $arr['user_id'] = User::getId();
        $arr['time_modified'] = $this->config->item('timestamp');
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

    /**
    * Move a record up or down in display order
    * @param int $optionId ontology option id
    * @param int $groupId the id of the group to which this ontology option belongs
    * @param string $direction should be either "up" or "dn"
    * @see parameterhasoptionsmodel::move()
    */
    public function move($optionId, $groupId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $options = $this->_getByOntologyGroup($groupId);

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($options); $i++) {
                if ($options[$i][self::PRIMARY_KEY] == $optionId) {
                    $current = $options[$i];
                    if(isset($options[$i + 1]))
                        $next = $options[$i + 1];
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
            for ($i = 0; $i < count($options); $i++) {
                if ($options[$i][self::PRIMARY_KEY] == $optionId) {
                    $current = $options[$i];
                    if(isset($options[$i - 1]))
                        $prev = $options[$i - 1];
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

    public function resequence($groupId = null)
    {
        $options = $this->_getByOntologyGroup($groupId);
        $counter = 0;
        foreach ($options as $option) {
            $this->db->where(self::PRIMARY_KEY, $option[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $counter));
            $counter++;
        }
    }

    /**
    * @param int $oldGroupId
    * @param int $newGroupId
    * @param int $exceptOptionId Copy all the options from the old group to
    * the new one except this option
    * @return bool success
    */
    private function _copyOptionsFromOldGroupToNewGroup($oldGroupId, $newGroupId, $exceptOptionId = null)
    {
        $this->load->model('ontologygroupmodel');
        $newGroup = $this->ontologygroupmodel->getById($newGroupId);
        if(empty($newGroup))
            return false;

        $oldOptions = $this->_getByOntologyGroup($oldGroupId);
        foreach ($oldOptions as $newOption) {
            if ($newOption[self::PRIMARY_KEY] == $exceptOptionId)
                continue;
            $newOption['ontology_group_id'] = $newGroupId;
            if ( ! $this->insert($newOption, ChangeLogger::ACTION_CREATE))
                return false;
        }

        return true;
    }

    public function createNewParentVersionAndDeleteOldItem($arr)
    {
        //Create a new Parameter Version
        $newParameterId = $this->parametermodel->createNewVersion($arr);
        if ( ! $newParameterId)
            return false;

        //create a new ontology group with a new name
        $this->load->model('ontologygroupmodel');
        $newOntGroupArr = array_merge($arr, array('name' => $arr['ontology_group_name'], 'parameter_id' => $newParameterId));
        $newOntologyGroupId = $this->ontologygroupmodel->insert($newOntGroupArr, ChangeLogger::ACTION_VERSION);
        if ($newOntologyGroupId === false)
            return false;

        //Copy ontology options of old group to new group except the option we did not want
        $copied = (bool)$this->_copyOptionsFromOldGroupToNewGroup(
            $arr['ontology_group_id'],
            $newOntologyGroupId,
            $arr[self::PRIMARY_KEY]
        );
        if ( ! $copied)
            return false;

        //delete old option group from new parameter and assign new group to new parameter
        $this->load->model('parameterhasontologygroupsmodel');
        $linkDeleted = (bool)$this->parameterhasontologygroupsmodel->delete($newParameterId, $arr['ontology_group_id']);
        if ( ! $linkDeleted)
            return false;

        return true;
    }
	
    private function _getMaxWeightByOntologyGroup($groupId)
    {
        $rec = $this->db->select_max('weight')
                        ->from(self::TABLE)
                        ->where('ontology_group_id', $groupId)
                        ->get()
                        ->row_array();
        return (isset($rec['weight'])) ? (int)$rec['weight'] : 0;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about object being logged
        $this->load->model('ontologygroupmodel');
        $param = $this->parametermodel->getById(@$arr[ParameterModel::PRIMARY_KEY]);
        $procedure = $this->proceduremodel->getById(@$arr[ProcedureModel::PRIMARY_KEY]);
        $pipeline = $this->pipelinemodel->getById(@$arr[PipelineModel::PRIMARY_KEY]);
        $currentRecord = $this->_getById($id);
        $group = $this->ontologygroupmodel->getById(@$arr[OntologyGroupModel::PRIMARY_KEY]);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Ontology Option (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['ontology_term'] . ' [' . @$arr['ontology_id'] . '] '
                     . 'in Group (' . @$group['name'] . '). ';
            $fields = array('ontology_term', 'ontology_id', 'ontology_group_id', 'is_active', 'is_default', 'is_collapsed');
            foreach ($fields AS $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . ', ';
            }
            $message .= 'for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                      . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                      . ') ' . @$procedure['name'] . ' of Pipeline ('
                      . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Ontology Option (' . $id . ') ' . @$arr['ontology_term']
                     . ' [' . @$arr['ontology_id'] . '] in Group (' . $group['name'] . ') '
                     . 'for Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' of Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Ontology Option (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['ontology_term'] . ' [' . $arr['ontology_id'] . '] from Group ('
                     . @$group['name'] . ') ' . 'of Parameter (' . @$param[ParameterModel::PRIMARY_KEY] . ') '
                     . @$param['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' of Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
            $message = 'Imported a new Ontology Option (' . $id . ') ' . @$arr['ontology_term']
                     . ' [' . @$arr['ontology_id'] . '] into Group (' . @$group['name'] . ') for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name']
                     . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') ' . @$procedure['name']
                     . ' of Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Ontology Option (' . @$arr[self::PRIMARY_KEY] . ') ' . @$arr['ontology_term']
                     . ' [' . @$arr['ontology_id'] . '] into Group (' . @$group['name'] . ') for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name']
                     . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') ' . @$procedure['name']
                     . ' of Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_VERSION) {
            $message = 'Created a new version of Ontology Option (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['ontology_term'] . ' [' . @$arr['ontology_id'] . '] '
                     . 'into Group (' . @$group['name'] . ') for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name']
                     . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') ' . @$procedure['name']
                     . ' of Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['parameter_key'])) ? @$param['parameter_key'] : @$currentRecord['parameter_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Ontology Option',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$param['internal'] || @$procedure['internal'] || @$pipeline['internal']) //|| $this->hasInternalParentByParentId(@$group[OntologyGroupModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$currentRecord[OntologyGroupModel::PRIMARY_KEY])) ////not full check because OntologyOptions are global and not hideable
            )
        );
    }
}
