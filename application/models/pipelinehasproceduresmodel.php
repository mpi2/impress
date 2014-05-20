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
 * Pipeline Has Procedures model
 */
class PipelineHasProceduresModel extends CI_Model
{
    const TABLE = 'pipeline_has_procedures';
    const PRIMARY_KEY = 'id';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function fetchAll()
    {
        return $this->db->get(self::TABLE)->result_array();
    }

    public function getById($id)
    {
        return $this->db->get_where(self::TABLE, array(self::PRIMARY_KEY => $id))->row_array();
    }

    public function getByPipeline($pid, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('php.is_deleted', 0);
        }
//        if ($this->config->item('server') != 'internal') {
//            $this->db->where('php.is_internal', 0);
//        }
        if ($selectAll) {
            $this->db->select('php.*, procedure.*, procedure_week.*');
        } else {
            $this->db->select('php.*');
        }
        return $this->db->from(self::TABLE . ' AS php')
                        ->join('procedure', 'procedure.' . ProcedureModel::PRIMARY_KEY . ' = php.' . ProcedureModel::PRIMARY_KEY, 'inner')
                        ->join('procedure_week', 'php.week = procedure_week.id', 'left')
                        ->where('php.' . PipelineModel::PRIMARY_KEY, $pid)
                        ->order_by('procedure_week.weight')
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }
	
    private function _getByPipeline($pid, $selectAll = true)
    {
        if ($selectAll) {
            $this->db->select('php.*, procedure.*, procedure_week.*');
        } else {
            $this->db->select('php.*');
        }

        return $this->db->from(self::TABLE . ' AS php')
                        ->join('procedure', 'procedure.' . ProcedureModel::PRIMARY_KEY . ' = php.' . ProcedureModel::PRIMARY_KEY)
                        ->join('procedure_week', 'php.week = procedure_week.id')
                        ->where('php.' . PipelineModel::PRIMARY_KEY, $pid)
                        ->order_by('procedure_week.weight')
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }

    public function getByProcedure($pid, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('php.is_deleted', 0);
        }
//        if ($this->config->item('server') != 'internal') {
//            $this->db->where('php.is_internal', 0);
//        }
        if ($selectAll) {
            $this->db->select('php.*, procedure.*');
        } else {
            $this->db->select('php.*');
        }
        return $this->db->from(self::TABLE . ' AS php')
                        ->join('procedure', 'procedure.' . ProcedureModel::PRIMARY_KEY . ' = php.' . ProcedureModel::PRIMARY_KEY)
                        ->where('php.' . ProcedureModel::PRIMARY_KEY, $pid)
                        ->order_by('php.' . PipelineModel::PRIMARY_KEY)
                        ->order_by('php.' . ProcedureModel::PRIMARY_KEY)
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }
	
    private function _getByProcedure($pid, $selectAll = true)
    {
        if ($selectAll) {
            $this->db->select('php.*, procedure.*');
        } else {
            $this->db->select('php.*');
        }
        return $this->db->from(self::TABLE . ' AS php')
                        ->join('procedure', 'procedure.' . ProcedureModel::PRIMARY_KEY . ' = php.' . ProcedureModel::PRIMARY_KEY)
                        ->where('php.' . ProcedureModel::PRIMARY_KEY, $pid)
                        ->order_by('php.' . PipelineModel::PRIMARY_KEY)
                        ->order_by('php.' . ProcedureModel::PRIMARY_KEY)
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }

    /**
     * @param int $pipelineId
     * @param int $procedureId
     * @param bool $selectAll Select all fields from tables?
     * @return array
     */
    public function getByPipelineAndProcedure($pipelineId, $procedureId, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('pipeline.deleted', 0);
            $this->db->where('php.is_deleted', 0);
        }
//        if ($this->config->item('server') != 'internal') {
//            $this->db->where('pipeline.internal', 0);
//            $this->db->where('php.is_internal', 0);
//        }
        if ($selectAll) {
            $this->db->select('procedure.*, php.*');
        } else {
            $this->db->select('php.*');            
        }
        return $this->db->from(self::TABLE . ' AS php')
                        ->join('pipeline', 'pipeline.' . PipelineModel::PRIMARY_KEY . ' = php.' . PipelineModel::PRIMARY_KEY, 'inner')
                        ->join('procedure', 'procedure.' . ProcedureModel::PRIMARY_KEY . ' = php.' . ProcedureModel::PRIMARY_KEY, 'inner')
                        ->where('php.' . ProcedureModel::PRIMARY_KEY, (int)$procedureId)
                        ->where('php.' . PipelineModel::PRIMARY_KEY, (int)$pipelineId)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }
        
    /**
     * @param int $pipelineId
     * @param int $procedureId
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    public function isDeleted($pipelineId, $procedureId = null, $bubble = false)
    {
        if (is_array($pipelineId))
            return $this->isDeleted($pipelineId[PipelineModel::PRIMARY_KEY], $pipelineId[ProcedureModel::PRIMARY_KEY], $bubble);
        return $this->_isX('is_deleted', $pipelineId, $procedureId, true, $bubble);
    }
    
    /**
     * @param int|array $pipelineId
     * @param int $procedureId
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    public function isVisible($pipelineId, $procedureId = null, $bubble = false)
    {
        if (is_array($pipelineId))
            return $this->isVisible($pipelineId[PipelineModel::PRIMARY_KEY], $pipelineId[ProcedureModel::PRIMARY_KEY], $bubble);
        return $this->_isX('is_visible', $pipelineId, $procedureId, false, $bubble);
    }
    
    /**
     * @param int|array $pipelineId
     * @param int $procedureId
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    public function isActive($pipelineId, $procedureId = null, $bubble = false)
    {
        if (is_array($pipelineId))
            return $this->isActive($pipelineId[PipelineModel::PRIMARY_KEY], $pipelineId[ProcedureModel::PRIMARY_KEY], $bubble);
        return $this->_isX('is_active', $pipelineId, $procedureId, false, $bubble);
    }
    
    /**
     * @param int|array $pipelineId
     * @param int $procedureId
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    public function isDeprecated($pipelineId, $procedureId = null, $bubble = true)
    {
        if (is_array($pipelineId))
            return $this->isDeprecated($pipelineId[PipelineModel::PRIMARY_KEY], $pipelineId[ProcedureModel::PRIMARY_KEY], $bubble);
        $isDeprecated = $this->_isX('is_deprecated', $pipelineId, $procedureId, false, $bubble);
    }
    
    /**
     * @param int|array $pipelineId
     * @param int $procedureId
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    public function isMandatory($pipelineId, $procedureId = null, $bubble = false)
    {
        if (is_array($pipelineId))
            return $this->isMandatory($pipelineId[PipelineModel::PRIMARY_KEY], $pipelineId[ProcedureModel::PRIMARY_KEY], $bubble);
        return $this->_isX('is_mandatory', $pipelineId, $procedureId, false, $bubble);
    }
    
    /**
     * @param int|array $pipelineId
     * @param int $procedureId
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    public function isInternal($pipelineId, $procedureId = null, $bubble = true)
    {
        if (is_array($pipelineId))
            return $this->isInternal($pipelineId[PipelineModel::PRIMARY_KEY], $pipelineId[ProcedureModel::PRIMARY_KEY], $bubble);
        return $this->_isX('is_internal', $pipelineId, $procedureId, false, $bubble);
    }
    
    /**
     * @param string $field
     * @param int $pipelineId
     * @param int $procedureId
     * @param bool $defaultResponse If the record is not found return this by default
     * @param bool $bubble Check parent Pipeline?
     * @return bool
     */
    private function _isX($field, $pipelineId, $procedureId, $defaultResponse = false, $bubble = false)
    {
        $p = $this->db->select($field)
                      ->from(self::TABLE)
                      ->where(PipelineModel::PRIMARY_KEY, $pipelineId)
                      ->where(ProcedureModel::PRIMARY_KEY, $procedureId)
                      ->limit(1)
                      ->get()
                      ->row_array();
        if (isset($p[$field])) {
            if ($p[$field]) {
                return true;
            } else if ($bubble) {
                if ($field == 'is_deleted')
                    return $this->pipelinemodel->isDeleted($pipelineId);
                else if ($field == 'is_internal')
                    return $this->pipelinemodel->isInternal($pipelineId);
                else if ($field == 'is_deprecated')
                    return $this->pipelinemodel->isDeprecated($pipelineId);
                else if ($field == 'is_active')
                    return $this->pipelinemodel->isActive($pipelineId);
                else if ($field == 'is_visible')
                    return $this->pipelinemodel->isVisible($pipelineId);
            } else {
                return false;
            }
        }
        return $defaultResponse;
    }

    /**
    * @param array $arr hash
    * @return int|bool Last insert id, true if it's already in there or false if something's not right
    */
    public function insert(array $arr)
    {
        if (empty($arr)) {
            return false;
        }

        if ( ! isset($arr['weight'])) {
            $arr['weight'] = ( ! is_null($arr['weight'])) ? abs((int)$arr['weight']) : 1 + $this->_getMaxProcedureWeightByPipeline($arr[PipelineModel::PRIMARY_KEY]);
        }
        if ( ! isset($arr['week'])) {
            $arr['week'] = 0;
        }
        if ( ! isset($arr['is_visible'])) {
            $arr['is_visible'] = 1;
        }
        if ( ! isset($arr['is_active'])) {
            $arr['is_active'] = 1;
        }
        $existingRelationships = $this->getByPipelineAndProcedure($arr[PipelineModel::PRIMARY_KEY], $arr[ProcedureModel::PRIMARY_KEY]);
        if (count($existingRelationships) == 0) {
            $arr = $this->_filterFields($arr);
            $this->db->insert(self::TABLE, $arr);
            return $this->db->insert_id();
        }
        return true;
    }

    /**
    * @param int $id Either supply a row id by itself or the pipeline_id as the first param
    * but if you supply the pipeline_id you are expected to also supply the procedure_id
    * @param int $procedureId When the procedure_id is supplied with the pipeline_id it
    * deletes the record(s) where these match with your supplied arguments
    * @return int no. of affected rows, usually 1
    */
    public function delete($id, $procedureId = null)
    {
        if(empty($id))
            return 0;

        //delete by id
        if ($procedureId === null) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        //delete by pipeline_id and procedure_id
        } else {
            $this->db->where('pipeline_id', $id)
                     ->where('procedure_id', $procedureId)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        }
    }

    /**
    * Move a record up or down in display order
    * @param int $procedureId procedure id
    * @param int $pipelineId the id of the pipeline in which this procedure resides
    * @param string $direction should be either "up" or "dn"
    * @return bool moved
    * @see proceduremodel::move()
    */
    public function move($procedureId, $pipelineId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $procs = $this->getByPipeline($pipelineId, false);

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($procs); $i++) {
                if ($procs[$i]['procedure_id'] == $procedureId) {
                    $current = $procs[$i];
                    if(isset($procs[$i + 1]))
                        $next = $procs[$i + 1];
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
            for ($i = 0; $i < count($procs); $i++) {
                if ($procs[$i]['procedure_id'] == $procedureId) {
                    $current = $procs[$i];
                    if(isset($procs[$i - 1]))
                        $prev = $procs[$i - 1];
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
	
    public function resequence($pipelineId = null)
    {
        $procs = $this->getByPipeline($pipelineId, false);
        $counter = 0;
        foreach ($procs as $p) {
            $this->db->where(self::PRIMARY_KEY, $p[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $counter));
            $counter++;
        }
    }

    /**
    * update a record
    * @param int $id record id
    * @param array $arr hash of cols
    * @return int no. rows affected
    */
    public function update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, (int)$id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }
    
    /**
     * @param int $pipelineId
     * @param int $procedureId
     * @param array $arr hash
     * @return int no. rows affected
     */
    public function updateByPipelineAndProcedure($pipelineId, $procedureId, array $arr)
    {
        $this->db->where('procedure_id', $procedureId)
                 ->where('pipeline_id', $pipelineId)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }

    private function _getMaxProcedureWeightByPipeline($pipelineId)
    {
        if(empty($pipelineId) || ! is_numeric($pipelineId))
            return 0;
        $r = $this->db->select_max('weight')
                      ->from(self::TABLE)
                      ->where('pipeline_id', (int)$pipelineId)
                      ->limit(1)
                      ->get()
                      ->row_array();
        return (empty($r)) ? 0 : $r['weight'];
    }
	
    private function _filterFields(array $arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
	
    private function _getFields()
    {
        return array(
            self::PRIMARY_KEY, 'procedure_id', 'pipeline_id',
            'weight', 'week', 'min_females',
            'min_males', 'min_animals', 'is_visible',
            'is_active', 'is_mandatory', 'is_internal',
            'is_deprecated', 'is_deleted'
        );
    }
	
    /**
    * Creates links between the new pipeline and the procedures of the original pipeline
    * @param int $origPipId
    * @param int $newPipId
    * @param int|array $exceptProcedure Id of procedure(s) we don't want to copy over
    * @return int|bool number of new links created or FALSE if original/new id not found
    */
    public function copyPipelineProceduresToNewPipeline($origPipId, $newPipId, $exceptProcedure = null)
    {
        $origPip = $this->pipelinemodel->getById($origPipId);
        $newPip  = $this->pipelinemodel->getById($newPipId);
        if (empty($origPip) || empty($newPip))
            return false;

        $procs = $this->_getByPipeline($origPip[PipelineModel::PRIMARY_KEY], false);
        $exceptProcedure = (array)$exceptProcedure;
        $linksCreated = 0;
        foreach ($procs as $proc) {
            if (in_array($proc[ProcedureModel::PRIMARY_KEY], $exceptProcedure))
                continue;

            $proc[PipelineModel::PRIMARY_KEY] = $newPip[PipelineModel::PRIMARY_KEY];
            
            $linksCreated += (int)(bool)$this->insert($proc);
        }
        return $linksCreated;
    }
    
    /**
     * @param int $weekId
     * @return int count
     */
    public function getNumProceduresWithWeek($weekId)
    {
        return (int) $this->db->from(self::TABLE)
                              ->where('week', $weekId)
                              ->count_all_results();
    }
    
    /**
     * @param type $pipId
     * @param type $procId
     * @param bool $deleted
     * @return bool success
     */
    public function setDeletedFlag($pipId, $procId, $deleted = true)
    {
        $this->db->where('pipeline_id', $pipId)
                 ->where('procedure_id', $procId)
                 ->update(self::TABLE, array('is_deleted' => (int)(bool)$deleted));
        return (bool) $this->db->affected_rows();
    }
}
