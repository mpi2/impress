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
 * Parameter Option Relations model
 */
class ParamOptionRelationsModel extends CI_Model
{
    const TABLE = 'param_option_relations';
    const PRIMARY_KEY = 'id';
    
    /**
     * @return array
     */
    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('parent_id')
                        ->get()
                        ->result_array();
    }
    
    /**
     * @param int $id
     * @return array hash
     */
    public function getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }
    
    /**
     * @param int $id
     * @return array
     */
    public function getByParentId($id)
    {
        return $this->db->from(self::TABLE)
                        ->where('parent_id', $id)
                        ->order_by('param_option_id')
                        ->get()
                        ->result_array();
    }
    
    /**
     * @param int $id
     * @return array
     */
    public function getByOptionId($id)
    {
        return $this->db->from(self::TABLE)
                        ->where('param_option_id', $id)
                        ->order_by('parent_id')
                        ->get()
                        ->result_array();
    }
    
    /**
     * @param int $optionId
     * @param int $parentId
     * @return array hash
     */
    public function getByOptionAndParentId($optionId, $parentId)
    {
        return $this->db->query(
            'SELECT * FROM ' . self::TABLE . ' WHERE ' .
            '(param_option_id = ' . (int)$optionId .' AND parent_id = ' . (int)$parentId . ') OR ' .
            '(param_option_id = ' . (int)$parentId . ' AND parent_id = ' . (int)$optionId . ') ' .
            'GROUP BY parent_id, param_option_id ' .
            'ORDER BY parent_id, param_option_id'
        )
        ->result_array();
    }
    
    /**
    * @param int $optionId
    * @return array hash
    */
    public function getByOptionOrParent($optionId)
    {
        return $this->db->select(
                            'rel.' . self::PRIMARY_KEY . ', rel.param_option_id, rel.relationship, rel.parent_id, rel.description, rel.connection,' .
                            'opt1.name AS child_option_name, opt1.description AS child_option_description,' .
                            'opt2.name AS parent_option_name, opt2.description AS parent_option_description,' .
                            'param1.parameter_key AS child_parameter_key, param1.name AS child_parameter_name,' .
                            'param2.parameter_key AS parent_parameter_key, param2.name AS parent_parameter_name'
                        )
                        ->from(self::TABLE . ' AS rel')
                        ->join('param_option opt1', 'opt1.param_option_id = rel.param_option_id', 'inner')
                        ->join('parameter_has_options pho1', 'pho1.param_option_id = opt1.param_option_id', 'inner')
                        ->join('parameter param1', 'param1.parameter_id = pho1.parameter_id', 'inner')
                        ->join('param_option opt2', 'opt2.param_option_id = rel.parent_id', 'inner')
                        ->join('parameter_has_options pho2', 'pho2.param_option_id = opt2.param_option_id', 'inner')
                        ->join('parameter param2', 'param2.parameter_id = pho2.parameter_id', 'inner')
                        ->where('rel.param_option_id', $optionId)
                        ->or_where('rel.parent_id', $optionId)
                        ->order_by('param1.parameter_id, rel.parent_id, rel.param_option_id')
                        ->get()
                        ->result_array();
    }
    
    /**
     * @return array
     */
    private function _getFields()
    {
        return array(self::PRIMARY_KEY, 'param_option_id', 'relationship', 'parent_id', 'description', 'connection');
    }
    
    /**
     * @param array $arr hash of fields
     * @return array hash
     */
    private function _filterFields(array $arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
    
    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $record = $this->getById($id);
        $this->db->delete(self::TABLE)
                 ->where(self::PRIMARY_KEY, $id);
        $ar = $this->db->affected_rows();
        if ($ar)
            $this->_log($id, $record, ChangeLogger::ACTION_DELETE);
        return (bool)$ar;
    }
    
    /**
     * @param int $poId
     */
    public function deleteByOptionOrParent($poId)
    {
        $rels = $this->getByOptionOrParent($poId);
        foreach ($rels as $rel) {
            $this->delete($rel[self::PRIMARY_KEY]);
        }
    }
    
    /**
     * @param array $arr hash
     * @return int|bool insert id, true if already there or false on failure
     */
    public function insert(array $arr)
    {
        $existingRelationships = $this->getByOptionAndParentId($arr['param_option_id'], $arr['parent_id']);
        if (count($existingRelationships) == 0) {
            $this->db->insert(self::TABLE, $this->_filterFields($arr));
            $iid = $this->db->insert_id();
            if ($iid)
                $this->_log($iid, $arr, ChangeLogger::ACTION_CREATE);
            return $iid;
        }
        return true;
    }
    
    /**
     * @param int $id
     * @param array $arr
     * @param string $action
     * @return bool
     */
    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;
        
        //initialize vars to get info about items being logged
        $currentRecord = $this->getById($id);
        $this->load->model('paramoptionmodel');
        $parameter = $this->parametermodel->getById(@$arr[ParameterModel::PRIMARY_KEY]);
        $parent = $this->paramoptionmodel->getById(@$arr['parent_id']);
        $fromParam = $this->parametermodel->getById(@$arr['from_parameter']);
        $toParam = $this->parametermodel->getById(@$arr['to_parameter']);
        $isInternal = ($this->parametermodel->isInternal(@$toParam[ParameterModel::PRIMARY_KEY]) ||
                       $this->parametermodel->isInternal(@$fromParam[ParameterModel::PRIMARY_KEY]));
        $option = $this->paramoptionmodel->getById(@$arr[ParamOptionModel::PRIMARY_KEY]);
        
        if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new ' . @$arr['relationship'] . ' Parameter Option '
                     . 'relationship between Option (' . @$parent[ParamOptionModel::PRIMARY_KEY]
                     . ') ' . $parent['name'] . ' of Parameter [' . $fromParam['parameter_key'] . '] ' 
                     . $fromParam['name'] . ' and new Option (' . @$option[ParamOptionModel::PRIMARY_KEY] . ') '
                     . @$option['name'] . ' of Parameter [' . @$toParam['parameter_key'] . '] '
                     . @$toParam['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted relationship between Option (' 
                     . @$option[ParamOptionModel::PRIMARY_KEY] . ') ' . @$arr['name']
                     . ' and its Parent Option (' . @$parent[ParamOptionModel::PRIMARY_KEY]. ')';
        } else {
            return true;
        }
        
        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (isset($toParam['parameter_key'])) ? $toParam['parameter_key'] : $parameter['parameter_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'ParamOption Relationship',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => (isset($arr['pipeline_id'])) ? (int)$arr['pipeline_id'] : null,
                ChangeLogger::FIELD_PROCEDURE => (isset($arr['procedure_id'])) ? (int)$arr['procedure_id'] : null,
                ChangeLogger::FIELD_PARAMETER => (isset($toParam[ParameterModel::PRIMARY_KEY])) ? $toParam[ParameterModel::PRIMARY_KEY] : $parameter[ParameterModel::PRIMARY_KEY],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool)$isInternal
            )
        );
    }

}
