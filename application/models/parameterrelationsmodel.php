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
 * Parameter Relations model
 */
class ParameterRelationsModel extends CI_Model
{
    const TABLE = 'parameter_relations';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('parent_id')
                        ->get()
                        ->result_array();
    }

    public function getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByParameterKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('parameter_key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByParameterId($id)
    {
        return $this->db->from(self::TABLE)
                        ->where('parameter_id', $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByParentKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('parent_key', $key)
                        ->get()
                        ->result_array();
    }

    public function getByParentId($id)
    {
        return $this->db->from(self::TABLE)
                        ->where('parent_id', $id)
                        ->get()
                        ->result_array();
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
            self::PRIMARY_KEY, 'parameter_id', 'parameter_key', 'relationship',
            'parent_id', 'parent_key', 'description', 'connection'
        );
    }

    public function getByParameterAndParentId($paramId, $parentId)
    {
        return $this->db->query(
            'SELECT * FROM parameter_relations WHERE (parameter_id = ' .
            (int)$paramId .' AND parent_id = ' . (int)$parentId . ') OR ' .
            '(parameter_id = ' . (int)$parentId . ' AND parent_id = ' .
            (int)$paramId . ') GROUP BY parameter_id, parent_id ' .
            'ORDER BY parameter_id, parent_id'
        )
        ->result_array();
    }
    
    /**
    * @param int $paramId
    * @return array hash
    */
    public function getByParameterOrParent($paramId)
    {
        return $this->db->select(
                            'rel.' . self::PRIMARY_KEY . ', rel.parameter_id, p1.name AS parameter_name, rel.parameter_key, rel.relationship, ' .
                            'rel.parent_id, p2.name AS parent_name, rel.parent_key, rel.description, rel.connection'
                        )
                        ->from(self::TABLE . ' AS rel')
                        ->join('parameter p1', 'p1.parameter_id = rel.parameter_id', 'inner')
                        ->join('parameter p2', 'p2.parameter_id = rel.parent_id', 'inner')
                        ->where('rel.parameter_id', $paramId)
                        ->or_where('rel.parent_id', $paramId)
                        ->order_by('rel.parameter_id')
                        ->get()
                        ->result_array();
    }

    public function delete($id)
    {
        $record = $this->getById($id);
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        $ar = $this->db->affected_rows();
        if ($ar)
            $this->_log($id, $record, ChangeLogger::ACTION_DELETE);
        return $ar;
    }
    
    /**
     * @param int $paramId
     */
    public function deleteByParameterOrParent($paramId)
    {
        $rels = $this->getByParameterOrParent($paramId);
        foreach ($rels as $rel) {
            $this->delete($rel[self::PRIMARY_KEY]);
        }
    }

    /**
    * @param array hash of columns
    * @return int last insert id
    */
    public function insert($arr)
    {
        $existingRelationship = $this->getByParameterAndParentId($arr['parameter_id'], $arr['parent_id']);
        if( ! empty($existingRelationship))
            return 1;
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        if ($iid)
            $this->_log($iid, $arr, ChangeLogger::ACTION_CREATE);
        return $iid;
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int affected rows
    */
    public function update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $arr);
        $ar = $this->db->affected_rows();
        if ($ar)
            $this->_log($id, $arr, ChangeLogger::ACTION_UPDATE);
        return $ar;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about items being logged
        $currentRecord = $this->getById($id);
        $parent = $this->parametermodel->getById(@$arr['parent_id']);
        $parentIsInternal = $this->parametermodel->isInternal(@$arr['parent_id']) || $this->parametermodel->isInternal(@$currentRecord['parent_id']);
        $child  = $this->parametermodel->getById(@$arr['parameter_id']);
        $childIsInternal = $this->parametermodel->isInternal(@$arr['parameter_id']) || $this->parametermodel->isInternal(@$currentRecord['parameter_id']);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated relationship between Parameter (' . $parent['parameter_id']
                     . ') ' . $parent['name'] . ' and Parameter (' . $child['parameter_id']
                     . ') ' . $child['name'] . '. ';
            foreach ($this->_getFields() AS $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . ' .';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new ' . $arr['relationship'] . ' relationship ('
                     . $id . ') between Parameter (' . $parent['parameter_id']
                     . ') ' . $parent['name'] . ' and Parameter ('
                     . $child['parameter_id'] . ') ' . $child['name'] . '. ';
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted ' . $arr['relationship'] . ' relationship ('
                     . $id . ') between Parameter ' . $parent['parameter_key']
                     . ' and ' . $child['parameter_key'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['parameter_key'])) ? @$arr['parameter_key'] : @$currentRecord['parameter_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Parameter Relationship',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => (isset($arr['pipeline_id'])) ? @$arr['pipeline_id'] : null,
                ChangeLogger::FIELD_PROCEDURE => (isset($arr['procedure_id'])) ? @$arr['procedure_id'] : null,
                ChangeLogger::FIELD_PARAMETER => @$arr['parameter_id'],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$parent['internal'] || @$child['internal'] || $parentIsInternal || $childIsInternal)
            )
        );
    }
}
