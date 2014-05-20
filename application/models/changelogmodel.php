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
 * Change Log model
 */
class ChangeLogModel extends CI_Model
{
    const TABLE = 'change_log';
    const PRIMARY_KEY = 'id';

    public function fetchAll($limit = 200)
    {
        $limit = abs((int)$limit);
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('datum', 'desc')
                        ->limit(($limit > 0) ? $limit : 200)
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

    public function getByUsername($username)
    {
        return $this->_getBy('username', $username);
    }

    public function getByIP($ip)
    {
        return $this->_getBy('ip', $ip);
    }

    public function getByAction($action)
    {
        return $this->_getBy('action', $action);
    }

    private function _getBy($field, $value)
    {
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->where($field, $value)
                        ->order_by('datum', 'desc')
                        ->get()
                        ->result_array();
    }

    private function _fetchAllGroupedBy($field)
    {
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
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

    public function fetchAllGroupedByAction()
    {
        return $this->_fetchAllGroupedBy('action');
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
            self::PRIMARY_KEY, 'datum','ip',
            'user_id','username','pipeline_id',
            'procedure_id','parameter_id','action_type',
            'item_type','item_id','item_key',
            'message','beta_release_date','live_release_date',
            'internal'
        );
    }

    /**
    * Get records with given conditions. For example, I only want records made
    * by user 3: $this->logsmodel->getRecordsWhere(array('user_id' => 3));
    * The keys you can use are action, ip, item, url, user_id and datum
    * @param array $conditions
    * @param int $limit max number of items returned
    * @return array resultset
    */
    public function getRecordsWhere(array $conditions = array(), $limit = 200)
    {
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        foreach ($this->_getFields() AS $key) {
            if (array_key_exists($key, $conditions))
                $this->db->where($key, $conditions[$key]);
        }
        $limit = abs((int)$limit);
        return $this->db->from(self::TABLE)
                        ->order_by('datum', 'desc')
                        ->limit(($limit > 0) ? $limit : 200)
                        ->get()
                        ->result_array();
    }

    public function getByPipeline($pipelineId, $limit = 200)
    {
        $limit = abs((int)$limit);
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->where('pipeline_id', $pipelineId)
                        ->or_where('action_type', ChangeLogger::ACTION_RELEASE)
                        ->order_by('datum', 'desc')
                        ->limit(($limit > 0) ? $limit : 200)
                        ->get()
                        ->result_array();
    }

    public function getByPipelineAndProcedure($pipelineId, $procedureId, $limit = 200)
    {
//        $limit = abs((int)$limit);
//        if($this->config->item('server') != 'internal')
//            $this->db->where('internal', 0);
//        return $this->db->from(self::TABLE)
//                        ->where('pipeline_id', $pipelineId)
//                        ->where('procedure_id', $procedureId)
//                        ->or_where('action_type', ChangeLogger::ACTION_RELEASE)
//                        ->order_by('datum', 'desc')
//                        ->limit(($limit > 0) ? $limit : 200)
//                        ->get()
//                        ->result_array();
        
        //more robust implementation:
        
        $limit = abs((int)$limit);
        
        //get parameter keys in procedure
        $paramKeys = $this->db->select('pa.parameter_key')
                              ->from(ParameterModel::TABLE . ' as pa')
                              ->join('procedure_has_parameters php1', 'php1.parameter_id = pa.parameter_id', 'inner')
                              ->join(ProcedureModel::TABLE . ' pr', 'pr.' . ProcedureModel::PRIMARY_KEY . ' = php1.' . ProcedureModel::PRIMARY_KEY, 'inner')
                              ->join('pipeline_has_procedures php2', 'php2.' . ProcedureModel::PRIMARY_KEY . ' = pr.' . ProcedureModel::PRIMARY_KEY, 'inner')
                              ->where('php2.' . PipelineModel::PRIMARY_KEY, $pipelineId)
                              ->where('pr.' . ProcedureModel::PRIMARY_KEY, $procedureId)
                              ->get()
                              ->result_array();
        $paramKeys = (empty($paramKeys)) ? array(-1) : array_map(function($k){return $k['parameter_key'];}, $paramKeys);
        
        //get parameter keyss present in this procedure but not for this pipeline and procedure combination
        $marapKeys = $this->db->select('pa.parameter_key')
                              ->from(ParameterModel::TABLE . ' as pa')
                              ->join('procedure_has_parameters php1', 'php1.parameter_id = pa.parameter_id', 'inner')
                              ->join(ProcedureModel::TABLE . ' pr', 'pr.' . ProcedureModel::PRIMARY_KEY . ' = php1.' . ProcedureModel::PRIMARY_KEY, 'inner')
                              ->join('pipeline_has_procedures php2', 'php2.' . ProcedureModel::PRIMARY_KEY . ' = pr.' . ProcedureModel::PRIMARY_KEY, 'inner')
                              ->where('php2.' . PipelineModel::PRIMARY_KEY . ' !=', $pipelineId)
                              ->where('pr.' . ProcedureModel::PRIMARY_KEY, $procedureId)
                              ->get()
                              ->result_array();
        $marapKeys = (empty($marapKeys)) ? array(-1) : array_map(function($k){return $k['parameter_key'];}, $marapKeys);
        
        //putting it all together
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->where_in('item_key', $paramKeys)
                        ->or_where(ProcedureModel::PRIMARY_KEY, $procedureId)
                        ->where_not_in('item_key', $marapKeys)
                        ->or_where('action_type', ChangeLogger::ACTION_RELEASE)
                        ->order_by('datum', 'desc')
                        ->limit(($limit > 0) ? $limit : 200)
                        ->get()
                        ->result_array();
    }

    /**
    * @return string timestamp of last record
    */
    public function getLastEntryDate()
    {
        $when = $this->db->select_max('datum')
                         ->get(self::TABLE)
                         ->row_array();
        return (empty($when)) ? '' : $when['datum'];
    }

    /**
    * @return array all release-style logs
    */
    public function getReleases()
    {
        return $this->db->from(self::TABLE)
                        ->where('action_type', ChangeLogger::ACTION_RELEASE)
                        ->order_by('datum', 'desc')
                        ->get()
                        ->result_array();
    }

    /**
    * @return int last insert id
    */
    public function insertRelease($message = null, $date = null)
    {
        try {
            $date = new DateTime($date);
        }
        catch (Exception $e) {
            $date = new DateTime();
        }
        $this->db->insert(self::TABLE, array(
            'ip' => ChangeLogger::getIP(),
            'username' => User::getUser('name'),
            'user_id' => User::getId(),
            'action_type' => ChangeLogger::ACTION_RELEASE,
            'message' => $message,
            'datum' => $date->format(DateTime::W3C)
        ));
        return $this->db->insert_id();
    }

    /**
    * @return int num rows affected
    */
    public function deleteRelease($id)
    {
        $this->db->where('action_type', ChangeLogger::ACTION_RELEASE)
                 ->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        return $this->db->affected_rows();
    }

    /**
    * @return string latest live date
    */
    public function getLatestLiveReleaseDate()
    {
        $query = $this->db->select_max('live_release_date')
                          ->from(self::TABLE)
                          ->get()
                          ->row_array();
        return $query['live_release_date'];
    }

    /**
    * @return string latest beta date
    */
    public function getLatestBetaReleaseDate()
    {
        $query = $this->db->select_max('beta_release_date')
                          ->from(self::TABLE)
                          ->where('beta_release_date !=', 'NULL', true)
                          ->where('live_release_date', null)
                          ->get()
                          ->row_array();
        return $query['beta_release_date'];
    }
}
