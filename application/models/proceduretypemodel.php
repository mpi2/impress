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
 * Procedure Type model
 */
class ProcedureTypeModel extends CI_Model
{
    const TABLE = 'procedure_type';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('key')
                        ->order_by('num')
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

    public function getByKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByNum($num)
    {
        return $this->db->from(self::TABLE)
                        ->where('num', $num)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function delete($id)
    {
        //only allow procedure types to be deleted if they are not in use by
        //any procedures or the active_item_deletion flag is on
        $procs = $this->proceduremodel->getNumProceduresWithType($id);
        if ($procs == 0 || $this->config->item('active_item_deletion')) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        }
        return 0;
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
		return array(self::PRIMARY_KEY, 'type', 'key', 'num');
	}

    /**
    * @param array hash of columns
    * @return int last insert id
    */
    public function insert($arr)
    {
        if (array_key_exists('num', $arr))
            $arr['num'] = $this->makeTriple($arr['num']);
        else
            $arr['num'] = $this->makeTriple(1 + $this->getLastNum());
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        return $this->db->insert_id();
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        if (array_key_exists('num', $arr))
            $arr['num'] = $this->makeTriple($arr['num']);
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }

    /**
    * @return int highest value in num column
    */
    public function getLastNum()
    {
        $rec = $this->db->select_max('num')
                        ->from(self::TABLE)
                        ->get()
                        ->row_array();
        return (int)@$rec['num'];
    }

    public function makeTriple($num)
    {
        if (empty($num))
            return FALSE;
        $num = (int)$num;
        if (strlen($num) == 1)
            return '00' . $num;
        else if (strlen($num) == 2)
            return '0' . $num;
        else
            return $num;
    }
}
