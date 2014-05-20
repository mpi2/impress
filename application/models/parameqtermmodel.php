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
* EQ Term Model
*/

class ParamEQTermModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable
{

    const TABLE = 'param_eqterm';
    const PRIMARY_KEY = 'param_eqterm_id';

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
	
    public function isDeleted($eqid)
    {
        $eq = $this->_getById($eqid);
        return (bool)@$eq['deleted'];
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
			self::PRIMARY_KEY, 'parameter_id', 'entity1_term', 'entity1_id',
			'entity2_term', 'entity2_id', 'entity3_term', 'entity3_id',
			'quality1_term', 'quality1_id',	'quality2_term', 'quality2_id',
			'weight', 'deleted', 'parameter_id', 'option_id',
			'increment_id', 'sex', 'selection_outcome', 'user_id',
			'time_modified'
		);
	}

    public function getEQTermsByParameter($pid)
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
        return $this->getEQTermsByParameter($pid);
    }

    private function _getByParameter($pid)
    {
        return $this->db->from(self::TABLE)
                        ->where('parameter_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function searchEntity($term)
    {
        if(empty($term))
            return array();

        return array_merge(
            $this->db->select('entity1_term AS label, entity1_id AS value')
                     ->from(self::TABLE)
                     ->where('deleted', 0)
                     ->like('entity1_term', $term)
                     ->or_like('entity1_id', $term)
                     ->group_by('entity1_id')
                     ->order_by('entity1_id')
                     ->limit(100)
                     ->get()
                     ->result_array(),
            $this->db->select('entity2_term AS label, entity2_id AS value')
                     ->from(self::TABLE)
                     ->where('deleted', 0)
                     ->like('entity2_term', $term)
                     ->or_like('entity2_id', $term)
                     ->group_by('entity2_id')
                     ->order_by('entity2_id')
                     ->limit(100)
                     ->get()
                     ->result_array(),
            $this->db->select('entity3_term AS label, entity3_id AS value')
                     ->from(self::TABLE)
                     ->where('deleted', 0)
                     ->like('entity3_term', $term)
                     ->or_like('entity3_id', $term)
                     ->group_by('entity3_id')
                     ->order_by('entity3_id')
                     ->limit(100)
                     ->get()
                     ->result_array()
        );
    }

    public function searchQuality($term)
    {
        if(empty($term))
            return array();

        return array_merge(
            $this->db->select('quality1_term AS label, quality1_id AS value')
                     ->from(self::TABLE)
                     ->where('deleted', 0)
                     ->like('quality1_term', $term)
                     ->or_like('quality1_id', $term)
                     ->group_by('quality1_id')
                     ->order_by('quality1_id')
                     ->limit(100)
                     ->get()
                     ->result_array(),
            $this->db->select('quality2_term AS label, quality2_id AS value')
                     ->from(self::TABLE)
                     ->where('deleted', 0)
                     ->like('quality2_term', $term)
                     ->or_like('quality2_id', $term)
                     ->group_by('quality2_id')
                     ->order_by('quality2_id')
                     ->limit(100)
                     ->get()
                     ->result_array()
        );
    }

    public function resultEntity($entityid)
    {
        return $this->db->select('pipeline.pipeline_id, pipeline.name AS pipeline_name, `procedure`.procedure_id, `procedure`.name AS procedure_name, parameter.parameter_id, '
                               . 'parameter.name AS parameter_name, soppy.sop_id, ' . self::TABLE . '.' . self::PRIMARY_KEY . ', ' . self::TABLE . '.entity1_id, ' . self::TABLE . '.entity1_term, '
                               . self::TABLE . '.entity2_id, ' . self::TABLE . '.entity2_term, ' . self::TABLE . '.entity3_id, ' . self::TABLE . '.entity3_term, '
                               . self::TABLE . '.quality1_id, ' . self::TABLE . '.quality1_term, ' . self::TABLE . '.quality2_id, ' . self::TABLE . '.quality2_term')
                        ->from('pipeline, procedure, parameter, ' . self::TABLE . ', pipeline_has_procedures, procedure_has_parameters')
                        ->join('sop soppy', 'procedure.procedure_id = soppy.procedure_id', 'left outer')
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
                        ->where('(' . self::TABLE . '.entity1_id', $entityid)
                        ->or_where(self::TABLE . '.entity2_id = ' . $this->db->escape($entityid), NULL, FALSE)
                        ->or_where(self::TABLE . '.entity3_id = ' . $this->db->escape($entityid) . ')', NULL, FALSE)
                        ->group_by('pipeline.pipeline_id, `procedure`.procedure_id, parameter.parameter_id')
                        ->order_by('pipeline.pipeline_id, `procedure`.procedure_id, parameter.parameter_id')
                        ->get()
                        ->result_array();
    }

    public function resultQuality($qualityid)
    {
        return $this->db->select('pipeline.pipeline_id, pipeline.name AS pipeline_name, `procedure`.procedure_id, `procedure`.name AS procedure_name, parameter.parameter_id, '
                               . 'parameter.name AS parameter_name, soppy.sop_id, ' . self::TABLE . '.' . self::PRIMARY_KEY . ', ' . self::TABLE . '.entity1_id, ' . self::TABLE . '.entity1_term, '
                               . self::TABLE . '.entity2_id, ' . self::TABLE . '.entity2_term, ' . self::TABLE . '.entity3_id, ' . self::TABLE . '.entity3_term, '
                               . self::TABLE . '.quality1_id, ' . self::TABLE . '.quality1_term, ' . self::TABLE . '.quality2_id, ' . self::TABLE . '.quality2_term')
                        ->from('pipeline, procedure, parameter, ' . self::TABLE . ', pipeline_has_procedures, procedure_has_parameters')
                        ->join('sop soppy', 'procedure.procedure_id = soppy.procedure_id AND soppy.deleted = 0', 'left outer')
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
                        ->where('(' . self::TABLE . '.quality1_id', $qualityid)
                        ->or_where(self::TABLE . '.quality2_id = ' . $this->db->escape($qualityid) . ')', NULL, FALSE)
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
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete EQ Term ' . $id . ' because it does not exist! Probably already deleted', 'parameqterm', ImpressLogger::ACTION_DELETE);
            return 0;
        }
		
        //prepare record for insertion into deleted table
        $this->load->model('parameqtermdeletedmodel');
        $iid = $this->parameqtermdeletedmodel->insert($record);
	
        //delete the record
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
        
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        if($iid && (in_array($action, array(ChangeLogger::ACTION_CREATE, ChangeLogger::ACTION_IMPORT, ChangeLogger::ACTION_UNDELETE))))
            $this->_log($iid, $arr, $action);
        return $iid;
    }
    
    /**
     * Prevents duplicates
     * 
     * @param array $arr
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $eqs = $this->_getByParameter($arr[ParameterModel::PRIMARY_KEY]);
        foreach ($eqs as $eq) {
            if ($eq['selection_outcome'] == $arr['selection_outcome'] &&
                $eq['entity1_id'] == $arr['entity1_id'] &&
                $eq['entity2_id'] == @$arr['entity2_id'] &&
                $eq['entity3_id'] == @$arr['entity3_id'] &&
                $eq['quality1_id'] == $arr['quality1_id'] &&
                $eq['quality2_id'] == @$arr['quality2_id'] &&
                $eq['option_id'] == @$arr['option_id'] &&
                $eq['increment_id'] == @$arr['increment_id'] &&
                $eq['sex'] == @$arr['sex']
            ) {
                return false;
            }
        }
        return true;
    }

    /**
    * Copies EQ terms from old parameter record to new one
    * @param int $oldParameterId
    * @param int $newParameterId
	* @param array $newOrigin An array with the keys pipeline_id, procedure_id,
    * parameter_id to indicate where the new MP item will be created for log
	* @param string $action
    * @return array|bool The new EQTerm ids or FALSE on failure
    */
    public function copyEQTermsToNewParameter($oldParameterId, $newParameterId, array $newOrigin, $action = ChangeLogger::ACTION_VERSION)
    {
        $newEQTermIds = array();
        foreach ($this->getEQTermsByParameter($oldParameterId) AS $eq) {
            unset($eq[self::PRIMARY_KEY]);
            $eq['parameter_id'] = $newParameterId;
            $eq = array_merge($eq, $newOrigin);
            $id = $this->insert($eq, $action);
            if($id === FALSE)
                return FALSE;
            else
                $newEQTermIds[] = $id;
        }
        return $newEQTermIds;
    }

    public function cloneByParameter(array $source, array $destination)
    {
        $ids = $this->copyEQTermsToNewParameter($source['parameter_id'], $destination['parameter_id'], $destination, ChangeLogger::ACTION_CLONE);
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
    * @param int $eqid
    * @return bool
    */
    public function hasDeprecatedParent($eqid)
    {
        $eq = $this->_getById($eqid);
        if ( ! empty($eq)) {
            return $this->hasDeprecatedParentByParentId($eq[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasDeprecatedParentByParentId($pid)
    {
        return $this->parametermodel->isDeprecated($pid);
    }
	
    /**
    * @param int $eqid
    * @return bool
    */
    public function hasInternalParent($eqid)
    {
        $eq = $this->_getById($eqid);
        if ( ! empty($eq)) {
            return $this->hasInternalParentByParentId($eq[ParameterModel::PRIMARY_KEY]);
        }
        return false;
    }
	
    public function hasInternalParentByParentId($pid)
    {
        return $this->parametermodel->isInternal($pid);
    }
    
    public function hasParentInBeta($eqid)
    {
        $eq = $this->_getById($eqid);
        if ( ! empty($eq)) {
            return $this->hasParentInBetaByParentId($eq[ParameterModel::PRIMARY_KEY]);
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
            $message = 'Updated EQ Term (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['selection_outcome'] . ' for Parameter (' . @$param[ParameterModel::PRIMARY_KEY]
                     . ') ' . @$param['name'] . '. ';
            $fields = array(
                'entity1_term', 'entity1_id', 'entity2_term', 'entity2_id',
                'entity3_term', 'entity3_id', 'quality1_term', 'quality1_id',
                'quality2_term', 'quality2_id', 'selection_outcome', 'option_id',
                'increment_id', 'sex'
            );
            foreach ($fields AS $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . '. ';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new EQ Ontology (' . $id . ') '
                     . @$arr['selection_outcome'] . ' association for Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted EQ Ontology (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['selection_outcome'] . ' associated with Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted EQ Ontology (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['selection_outcome'] . ' associated with Parameter ('
                     . @$param[ParameterModel::PRIMARY_KEY] . ') ' . @$param['name'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
			$message = 'Imported a new EQ Ontology (' . $id . ') ' . @$arr['eq_id']
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
                ChangeLogger::FIELD_ITEM_TYPE => 'EQ Ontology',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$param['internal'] || $this->hasInternalParentByParentId(@$currentRecord[ParameterModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$arr[ParameterModel::PRIMARY_KEY]))
            )
        );
    }
}
