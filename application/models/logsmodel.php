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
 * Logs Model
 */
class LogsModel extends CI_Model
{
    const TABLE = 'logs';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('datum', 'desc')
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

    public function getByUserId($userId)
    {
        return $this->_getBy('user_id', $userId);
    }

    public function getByIP($ip)
    {
        return $this->_getBy('ip', $ip);
    }

    public function getByUrl($url)
    {
        return $this->_getBy('url', $url);
    }

    public function getByAction($action)
    {
        return $this->_getBy('action', $action);
    }

    public function getByItem($item)
    {
        return $this->_getBy('item', $item);
    }

    private function _getBy($field, $value)
    {
        return $this->db->from(self::TABLE)
                        ->where($field, $value)
                        ->order_by('datum', 'desc')
                        ->get()
                        ->result_array();
    }

    private function _fetchAllGroupedBy($field)
    {
        return $this->db->from(self::TABLE)
                        ->order_by($field)
                        ->order_by('datum', 'desc')
                        ->get()
                        ->result_array();
    }

    public function fetchAllGroupedByUserId()
    {
        return $this->_fetchAllGroupedBy('user_id');
    }

    public function fetchAllGroupedByIP()
    {
        return $this->_fetchAllGroupedBy('ip');
    }

    public function fetchAllGroupedByUrl()
    {
        return $this->_fetchAllGroupedBy('url');
    }

    public function fetchAllGroupedByAction()
    {
        return $this->_fetchAllGroupedBy('action');
    }

    public function fetchAllGroupedByItem()
    {
        return $this->_fetchAllGroupedBy('item');
    }

    /**
    * @param array hash of columns
    * @return int last insert id
    */
    public function insert($arr)
    {
        $this->db->insert(self::TABLE, $arr);
        return $this->db->insert_id();
    }

    /**
    * Get records with given conditions. For example, I only want records made
    * by user 3: $this->logsmodel->getRecordsWhere(array('user_id' => 3));
    * The keys you can use are action, ip, item, url, user_id and datum
    * @param array $conditions
    * @param int $limit max number of items returned
    * @return array resultset
    */
    public function getRecordsWhere($conditions = array(), $limit = 1000)
    {
        if (is_array($conditions)) {
            foreach (array('action', 'ip', 'item', 'url', 'user_id', 'datum') AS $key) {
                if (array_key_exists($key, $conditions))
                    $this->db->where($key, $conditions[$key]);
            }
        }
        $limit = (is_numeric($limit) && $limit >= 1) ? (int)$limit : 1000;
        return $this->db->from(self::TABLE)
                        ->order_by('datum', 'desc')
                        ->limit($limit)
                        ->get()
                        ->result_array();
    }

    /**
    * @return string timestamp of last record
    */
    public function getLastEntryDate()
    {
        $when = $this->db->select('datum')
                         ->from(self::TABLE)
                         ->where('type', ImpressLogger::INFO)
                         ->where('action !=', '')
                         ->order_by('datum', 'desc')
                         ->limit(1)
                         ->get()
                         ->row_array();
        return (empty($when)) ? '' : $when['datum'];
    }
}
