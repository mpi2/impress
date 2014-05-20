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
 * Glossary Model
 */
class GlossaryModel extends CI_Model
{
    const TABLE = 'glossary';
    const PRIMARY_KEY = 'glossary_id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('term')
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

    public function getByTerm($term = null)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('term', $term)
                        ->get()
                        ->result_array(); //because a term can have more than one meaning/record
                                          //e.g. date (fruit), date (event), date (social construct)
    }

    private function _isDeleted($id)
    {
        $term = $this->_getById($id);
        return (bool)$term['deleted'];
    }

    /**
    * @return bool success
    */
    public function delete($id)
    {
        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_permanentlyDelete($id);
        return (bool) $this->_update($id, array('deleted' => 1));
    }

    public function undelete($id)
    {
        if($this->_isDeleted($id))
            return (bool) $this->_update($id, array('deleted' => 0));

        if($this->config->item('delete_mode') == 'hard')
            return false; //there is no table for deleted glossary records to _hardUndelete() a term
        return (bool) $this->_update($id, array('deleted' => 0));
    }

    private function _permanentlyDelete($id)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        return $this->db->affected_rows();
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
    public function _update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int last updated id
    */
    public function update($id, $arr)
    {
        $oldrecord = $this->getById($id);
        $oldrecord['user_id'] = User::getId();
        $oldrecord['time_modified'] = $this->config->item('timestamp');
        $this->db->insert('glossary_oldedits', $this->_filterFieldsKeepPK($oldrecord));
        if ($this->db->insert_id()) {
            return $this->_update($id, $this->_filterFields($arr));
        }
        return 0;
    }

    public function getOldEditsByTermId($id)
    {
        return $this->db->where(self::PRIMARY_KEY, $id)
                        ->from('glossary_oldedits')
                        ->order_by('time_modified')
                        ->get()
                        ->result_array();
    }

    public function getOldEditsByTerm($term, $num = 0)
    {
        $rec = $this->getByTerm($term);
        return $this->getOldEditsByTermId($rec[$num][self::PRIMARY_KEY]);
    }
	
	private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
		$this->load->helper('delete_array_values');
		$keys = delete_array_values($this->getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
	
	private function _filterFieldsKeepPK($arr)
	{
		$this->load->helper('keep_array_keys');
        return keep_array_keys($arr, $this->getFields());
	}
	
	public function getFields()
	{
		return array(self::PRIMARY_KEY, 'term', 'definition', 'user_id', 'time_modified', 'deleted');
	}
}
