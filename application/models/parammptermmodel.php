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
 * Parameter MP Term model
 */
class ParamMPTermModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable
{
    const TABLE = 'param_mpterm';
    const PRIMARY_KEY = 'param_mpterm_id';

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
	
    public function isDeleted($mpid)
    {
        $mp = $this->_getById($mpid);
        return (bool)@$mp['deleted'];
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
                self::PRIMARY_KEY, 'parameter_id', 'mp_term', 'mp_id',
                'weight', 'deleted', 'option_id', 'increment_id',
                'sex', 'selection_outcome', 'user_id', 'time_modified'
        );
    }

    public function getMPTermsByParameter($pid)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('parameter_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function getByParameter($pid)
    {
        return $this->getMPTermsByParameter($pid);
    }

    private function _getByParameter($pid)
    {
        return $this->db->from(self::TABLE)
                        ->where('parameter_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function search($term)
    {
        if(empty($term))
            return array();

        return $this->db->select('mp_term AS label, mp_id AS value')
                        ->from(self::TABLE)
                        ->where('deleted', 0)
                        ->like('mp_term', $term)
                        ->or_like('mp_id', $term)
                        ->group_by('mp_id')
                        ->order_by('mp_id')
                        ->limit(100)
                        ->get()
                        ->result_array();
    }

    public function searchresult($mpid)
    {
        return $this->db->select('pipeline.pipeline_id, pipeline.name AS pipeline_name, `procedure`.procedure_id, `procedure`.name AS procedure_name, protocol.sop_id, '
                               . 'parameter.parameter_id, parameter.name AS parameter_name, ' . self::TABLE . '.' . self::PRIMARY_KEY . ', ' . self::TABLE . '.mp_id, ' . self::TABLE . '.mp_term')
                        ->from('pipeline, `procedure`, parameter, ' . self::TABLE . ', pipeline_has_procedures, procedure_has_parameters')
                        ->join('sop protocol', 'procedure.procedure_id = protocol.procedure_id AND protocol.deleted = 0', 'left outer')
                        ->where(self::TABLE . '.mp_id', $mpid)
                        ->where('parameter.parameter_id', self::TABLE . '.parameter_id', FALSE)
                        ->where('procedure_has_parameters.parameter_id', 'parameter.parameter_id', FALSE)
                        ->where('`procedure`.procedure_id', 'procedure_has_parameters.procedure_id', FALSE)
                        ->where('pipeline_has_procedures.procedure_id', '`procedure`.procedure_id', FALSE)
                        ->where('pipeline.pipeline_id', 'pipeline_has_procedures.pipeline_id', FALSE)
                        ->where('pipeline.deleted', 0)
                        ->where('pipeline.internal !=', ($this->config->item('server') != 'internal') ? 1 : -1) //was ->where('pipeline.internal', 0)
                        ->where('pipeline_has_procedures.is_deleted', 0)
                        ->where('pipeline_has_procedures.is_internal !=', ($this->config->item('server') != 'internal') ? 1 : -1)
                        ->where('parameter.deleted', 0)
                        ->where('parameter.internal !=', ($this->config->item('server') != 'internal') ? 1 : -1)
                        ->where(self::TABLE . '.deleted', 0)
                        ->group_by('pipeline.pipeline_id, `procedure`.procedure_id, parameter.parameter_id')
                        ->order_by('pipeline.pipeline_id, `procedure`.procedure_id, parameter.parameter_id')
                        ->get()
                        ->result_array();
    }

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
            return false; //(bool) $this->_hardUndelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, FALSE, $origin);
    }

    private function _setDeletedFlag($id, $deleted = TRUE, array $origin = array())
    {
        $deleted = ($deleted) ? 1 : 0;
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, array('deleted' => $deleted));
        $ar = $this->db->affected_rows();
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
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete MP Term ' . $id . ' because it does not exist! Probably already deleted', 'parammpterm', ImpressLogger::ACTION_DELETE);
            return 0;
        }

        //prepare record for insertion into deleted table
        $this->load->model('parammptermdeletedmodel');
        $iid = $this->parammptermdeletedmodel->insert($record);

        //delete the record from the current table
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $ar = $this->db->affected_rows();
            if ($ar) {
                $record = array_merge($record, $origin);
                $this->_log($id, $record, ChangeLogger::ACTION_DELETE);
            }
            $iid = $ar;
        }
        return $iid;
    }

    public function hardDeleteByParameter($parameterId, $origin)
    {
        $rows = $this->_getByParameter($parameterId);
        foreach($rows AS $row)
            $this->_hardDelete($row[self::PRIMARY_KEY], $origin);
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
        
        $arr['user_id'] = User::getId();
        $arr['time_modified'] = $this->config->item('timestamp');
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        if($iid && (in_array($action, array(ChangeLogger::ACTION_CREATE, ChangeLogger::ACTION_IMPORT, ChangeLogger::ACTION_UNDELETE))))
            $this->_log($iid, $arr, $action);
        return $iid;
    }
    
    /**
     * Prevents duplicate MP Terms being entered for the same conditions
     * 
     * @param array $arr
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $mpTerms = $this->_getByParameter($arr[ParameterModel::PRIMARY_KEY]);
        foreach ($mpTerms as $mp) {
            if ($mp['sex'] == @$arr['sex'] &&
                $mp['mp_id'] == $arr['mp_id'] &&
                $mp['option_id'] == @$arr['option_id'] &&
                $mp['increment_id'] == @$arr['increment_id'] &&
                $mp['selection_outcome'] == $arr['selection_outcome']
            ) {
                return false;
            }
        }
        return true;
    }

    /**
    * Copies MP terms from old parameter record to new one
    * @param int $oldParameterId
    * @param int $newParameterId
    * @param array $newOrigin An array with the keys pipeline_id, procedure_id,
    * parameter_id to indicate where the new MP item will be created for log
    * @param string $action
    * @return array|bool The new MPTerm ids or FALSE on failure
    */
    public function copyMPTermsToNewParameter($oldParameterId, $newParameterId, array $newOrigin, $action = ChangeLogger::ACTION_VERSION)
    {
        $newMPTermIds = array();
        foreach ($this->getMPTermsByParameter($oldParameterId) AS $mp) {
            unset($mp[self::PRIMARY_KEY]);
            $mp['parameter_id'] = $newParameterId;
            $mp = array_merge($mp, $newOrigin);
            $id = $this->insert($mp, $action);
            if($id === FALSE)
                return FALSE;
            else
                $newMPTermIds[] = $id;
        }
        return $newMPTermIds;
    }
	
    public function cloneByParameter(array $source, array $destination)
    {
        $ids = $this->copyMPTermsToNewParameter($source['parameter_id'], $destination['parameter_id'], $destination, ChangeLogger::ACTION_CLONE);
        return ($ids === false) ? false : true;
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        $beforeChange = $this->_getById($id);
        $arr['user_id'] = User::getId();
        $arr['time_modified'] = $this->config->item('timestamp');
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
        if ($ar) {
            $beforeChange[ChangeLogger::FIELD_PIPELINE] = $arr[ChangeLogger::FIELD_PIPELINE];
            $beforeChange[ChangeLogger::FIELD_PROCEDURE] = $arr[ChangeLogger::FIELD_PROCEDURE];
            $beforeChange[ChangeLogger::FIELD_PARAMETER] = $arr[ChangeLogger::FIELD_PARAMETER];
            $this->_log($id, $beforeChange, ChangeLogger::ACTION_UPDATE);
        }
        return $ar;
    }

    /**
    * @param int $mpid
    * @return bool
    */
    public function hasDeprecatedParent($mpid)
    {
        $mp = $this->_getById($mpid);
        if ( ! empty($mp)) {
            return $this->hasDeprecatedParentByParentId($mp[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasDeprecatedParentByParentId($pid)
    {
        return $this->parametermodel->isDeprecated($pid);
    }

    /**
    * @param int $mpid
    * @return bool
    */
    public function hasInternalParent($mpid)
    {
        $mp = $this->_getById($mpid);
        if ( ! empty($mp)) {
            return $this->hasInternalParentByParentId($mp[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
	
    public function hasInternalParentByParentId($pid)
    {
            return $this->parametermodel->isInternal($pid);
    }
    
    public function hasParentInBeta($mpid)
    {
        $mp = $this->_getById($mpid);
        if ( ! empty($mp)) {
            return $this->hasParentInBetaByParentId($mp[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasParentInBetaByParentId($pid)
    {
        return $this->parametermodel->isInBeta($pid);
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
            $message = 'Updated MP Term (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['mp_id'] . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY]
                     . ') ' . @$param['name'] . '. ';
            foreach (array('mp_id', 'mp_term', 'selection_outcome', 'option_id', 'increment_id', 'sex') AS $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . '. ';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new MP Ontology (' . $id . ') ' . @$arr['mp_id']
                     . ' ' . @$arr['selection_outcome'] . ' association for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted MP Ontology (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['mp_id'] . ' associated with Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted MP Ontology (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['mp_id'] . ' associated with Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
			$message = 'Imported a new MP Ontology (' . $id . ') ' . @$arr['mp_id']
                     . ' ' . @$arr['selection_outcome'] . ' association for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['parameter_key'])) ? @$param['parameter_key'] : @$currentRecord['parameter_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'MP Ontology',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$param['internal'] || $this->hasInternalParentByParentId(@$arr[ParameterModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$currentRecord[ParameterModel::PRIMARY_KEY]))
            )
        );
    }
}
