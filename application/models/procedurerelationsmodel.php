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
 * Procedure Relations model
 */
class ProcedureRelationsModel extends CI_Model
{

    const TABLE = 'procedure_relations';
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

    public function getByProcedureKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('procedure_key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByProcedureId($id)
    {
        return $this->db->from(self::TABLE)
                        ->where('procedure_id', $id)
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
            self::PRIMARY_KEY, 'procedure_id', 'procedure_key', 'relationship',
            'parent_id', 'parent_key', 'description', 'connection'
        );
    }

    public function getByProcedureAndParentId($procId, $parentId)
    {
        return $this->db->query(
            'SELECT * FROM ' . self::TABLE . ' WHERE ' .
            '(procedure_id = ' . (int)$procId .' AND parent_id = ' . (int)$parentId . ') OR ' .
            '(procedure_id = ' . (int)$parentId . ' AND parent_id = ' . (int)$procId . ') ' .
            'GROUP BY procedure_id, parent_id ' .
            'ORDER BY procedure_id, parent_id'
        )
        ->result_array();
    }
    
    /**
    * @param int $procId
    * @return array hash
    */
    public function getByProcedureOrParent($procId)
    {
        return $this->db->select(
                            'rel.' . self::PRIMARY_KEY . ', rel.procedure_id, p1.name AS procedure_name, rel.procedure_key, rel.relationship, ' .
                            'rel.parent_id, p2.name AS parent_name, rel.parent_key, rel.description, rel.connection'
                        )
                        ->from(self::TABLE . ' AS rel')
                        ->join('procedure p1', 'p1.procedure_id = rel.procedure_id', 'inner')
                        ->join('procedure p2', 'p2.procedure_id = rel.parent_id', 'inner')
                        ->where('rel.procedure_id', $procId)
                        ->or_where('rel.parent_id', $procId)
                        ->order_by('rel.procedure_id')
                        ->get()
                        ->result_array();
    }

    /**
     * @param int $id
     * @return int
     */
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
     * @param int $procId
     */
    public function deleteByProcedureOrParent($procId)
    {
        $rels = $this->getByProcedureOrParent($procId);
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
        $existingRelationship = $this->getByProcedureAndParentId($arr['procedure_id'], $arr['parent_id']);
        if ( ! empty($existingRelationship))
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
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
        if ($ar)
            $this->_log($id, $arr, ChangeLogger::ACTION_UPDATE);
        return $ar;
    }

    /**
    * If you give it an argument of XRY it will find the highest version by procedure key
    * @param string $type Procedure Type TLA or id of type
    * @return string procedure_key with highest version as identified by its key or empty string if nothing was found
    * @deprecated DO NOT USE
    */
    public function getKeyWithMaxVersionForType($type)
    {
        if (is_numeric($type)) {
            $this->load->model('proceduretypemodel');
            $r = $this->proceduretypemodel->getById($type);
            if (empty($r))
                return FALSE;
            $type = $r['key'];
        }

        $arr = $this->db->query(
            "SELECT `procedure_key` FROM `" . self::TABLE . "`
            WHERE SUBSTRING(`procedure_key`, INSTR(`procedure_key`, '_')+1, 3)=" .
            $this->db->escape(substr($type, 0, 3)) . "
            UNION
            SELECT `procedure_key` FROM `procedure`
            WHERE SUBSTRING(`procedure_key`, INSTR(`procedure_key`, '_')+1, 3)=" .
            $this->db->escape(substr($type, 0, 3)) . "
            ORDER BY SUBSTRING(`procedure_key`, -3) DESC
            LIMIT 1"
        )
        ->row_array();

        return (empty($arr)) ? '' : $arr['procedure_key'];
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about items being logged
        $currentRecord = $this->getById($id);
        $parent = $this->proceduremodel->getById(@$arr['parent_id']);
        $parentIsInternal = $this->proceduremodel->isInternal(@$arr['parent_id']);
        $child  = $this->proceduremodel->getById(@$arr[ProcedureModel::PRIMARY_KEY]);
        $childIsInternal = $this->proceduremodel->isInternal(@$arr[ProcedureModel::PRIMARY_KEY]);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated relationship between Procedure (' . $parent['procedure_id']
                     . ') ' . $parent['name'] . ' and Procedure (' . $child['procedure_id']
                     . ') ' . $child['name'] . '. ';
            foreach ($this->_getFields() AS $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . ' .';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new ' . $arr['relationship'] . ' relationship ('
                     . $id . ') between Procedure (' . $parent['procedure_id']
                     . ') ' . $parent['name'] . ' and Procedure ('
                     . $child['procedure_id'] . ') ' . $child['name'] . '. ';
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted ' . $arr['relationship'] . ' relationship ('
                     . $id . ') between Procedure ' . $parent['procedure_key']
                     . ' and ' . $child['procedure_key'];
        } else {
			return true;
		}

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['procedure_key'])) ? @$arr['procedure_key'] : @$currentRecord['procedure_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Procedure Relationship',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => (isset($arr['pipeline_id'])) ? @$arr['pipeline_id'] : null,
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => null,
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$parent['is_internal'] || @$child['internal'] || $parentIsInternal || $childIsInternal)
            )
        );
    }
}
