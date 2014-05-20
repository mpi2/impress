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
 * Parameter Unit model
 */
class UnitModel extends CI_Model
{
    const TABLE = 'units';
    const PRIMARY_KEY = 'id';

    /**
    * @return array row array with keys: id, unit, freq
    */
    public function fetchAll()
    {
        return $this->db->select('u.' . self::PRIMARY_KEY . ', u.unit, COUNT(p.unit) AS freq')
                        ->from(self::TABLE . ' AS u')
                        ->join(ParameterModel::TABLE . ' p', 'u.' . self::PRIMARY_KEY . ' = p.unit', 'left')
                        ->group_by('u.' . self::PRIMARY_KEY)
                        ->order_by('freq', 'desc')
                        ->order_by('u.' . self::PRIMARY_KEY)
                        ->get()
                        ->result_array();
    }

    /**
    * @param int $unitId
    * @return int how often the specified unit is used
    */
    public function getUnitUsageFrequency($unitId = null)
    {
        return $this->parametermodel->getUnitUsageFrequency($unitId);
    }

    /**
    * @return array row array with keys: id, unit, freq
    * @alias UnitModel::fetchAll()
    */
    public function getUnitUsageFrequencies()
    {
        return $this->fetchAll();
    }

    public function getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByUnit($unit = null)
    {
        return $this->db->from(self::TABLE)
                        ->where('unit', $unit)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        return keep_array_keys($arr, array('unit'));
    }

    /**
    * @param array hash of columns
    * @return int last insert id
    */
    public function insert($arr)
    {
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        return $this->db->insert_id();
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int rows affected
    */
    public function update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }

    /**
    * @param int $id
    * @return int rows affected
    */
    public function delete($id)
    {
        //delete the record - this sets the unit to NULL in the db
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        $ar = $this->db->affected_rows();
        //we want the unit to be 0 in the db rather than NULL so it
        //still aligns with the units table
        $this->db->where('unit', null)
                 ->update(ParameterModel::TABLE, array('unit' => 0));
        return $ar;
    }
}
