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
 * Procedure Week model
 */
class ProcedureWeekModel extends CI_Model implements ISequenceable
{
    const TABLE = 'procedure_week';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('weight')
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

    public function getByNum($num)
    {
        return $this->db->from(self::TABLE)
                        ->where('num', $num)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }
    
    public function getByStage($stage)
    {
        return $this->db->from(self::TABLE)
                        ->where('stage', $stage)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function delete($id)
    {
        //only allow procedure weeks to be deleted if they are not in use by
        //any procedures, or if the active_item_deletion flag is on
        $procs = $this->proceduremodel->getNumProceduresWithWeek($id);
        if ($procs == 0 || $this->config->item('active_item_deletion')) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        }
        return 0;
    }

    private function _getFields()
    {
        return array(self::PRIMARY_KEY, 'label', 'num', 'stage', 'weight');
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }

    /**
    * @param array hash of columns
    * @return int last insert id
    */
    public function insert($arr)
    {
        if ( ! isset($arr['weight']) || $arr['weight'] == 0)
            $arr['weight'] = $this->_getMaxWeight($arr['stage']);
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        return $this->db->insert_id();
    }
    
    /**
     * @param string $stage
     * @return int
     */
    private function _getMaxWeight($stage = '')
    {
        if ($stage && EProcedureWeekStage::validate($stage))
            $this->db->where('stage', $stage);
        $w = $this->db->select_max('weight')
                      ->from(self::TABLE)
                      ->get()
                      ->row_array();
        return (isset($w['weight'])) ? $w['weight'] : 0;
    }
    
    /**
     * Move a record up or down in display order
     * @param int $weekId
     * @param string $direction should be either "up" or "dn"
     * @return bool success
     */
    public function move($weekId, $direction)
    {
        if ($direction != 'dn')
            $direction = 'up';

        $weeks = $this->fetchAll();

        $current = $other = null;

        if ($direction == 'dn') {
            $next = null;
            for ($i = 0; $i < count($weeks); $i++) {
                if ($weeks[$i][self::PRIMARY_KEY] == $weekId) {
                    $current = $weeks[$i];
                    if (isset($weeks[$i + 1]))
                        $next = $weeks[$i + 1];
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
        } else if ($direction == 'up') {
            $prev = null;
            for ($i = 0; $i < count($weeks); $i++) {
                if ($weeks[$i][self::PRIMARY_KEY] == $weekId) {
                    $current = $weeks[$i];
                    if (isset($weeks[$i - 1]))
                        $prev = $weeks[$i - 1];
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

    public function resequence()
    {
        $weeks = $this->fetchAll();
        $counter = 0;
        foreach ($weeks as $week) {
            $this->db->where(self::PRIMARY_KEY, $week[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $counter));
            $counter++;
        }
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }

}
