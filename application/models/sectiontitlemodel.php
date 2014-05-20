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
 * SOP Section Title model
 */
class SectionTitleModel extends CI_Model implements ISequenceable
{
    const TABLE = 'section_title';
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

    public function delete($id)
    {
        //only unused titles can be deleted unless active_item_deletion flag set
        $this->load->model('sectionmodel');
        $secs = $this->sectionmodel->getNumSectionsWithTitle($id);
        if ($secs == 0 || $this->config->item('active_item_deletion')) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        }
        return 0;
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
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }
	
	/**
    * Move a record up or down in display order
    * @param int $titleId
    * @param string $direction should be either "up" or "dn"
    * @return bool success
    */
	public function move($titleId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $titles = $this->fetchAll();

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($titles); $i++) {
                if ($titles[$i][self::PRIMARY_KEY] == $titleId) {
                    $current = $titles[$i];
                    if(isset($titles[$i + 1]))
                        $next = $titles[$i + 1];
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
            for ($i = 0; $i < count($titles); $i++) {
                if ($titles[$i][self::PRIMARY_KEY] == $titleId) {
                    $current = $titles[$i];
                    if(isset($titles[$i - 1]))
                        $prev = $titles[$i - 1];
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
		$titles = $this->fetchAll();
		$counter = 0;
		foreach ($titles as $title) {
			$this->db->where(self::PRIMARY_KEY, $title[self::PRIMARY_KEY])
					 ->update(self::TABLE, array('weight' => $counter));
			$counter++;
		}
	}
	
	private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
		$this->load->helper('delete_array_values');
		$keys = delete_array_values($this->_getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
	
	public function _getFields()
	{
		return array(self::PRIMARY_KEY, 'title', 'centre_id', 'weight');
	}
}
