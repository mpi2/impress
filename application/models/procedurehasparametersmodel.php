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
 * Procedure Has Parameters model
 */
class ProcedureHasParametersModel extends CI_Model
{
    const TABLE = 'procedure_has_parameters';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->get(self::TABLE)
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

    /**
     * @param int $pid
     * @param boolean $selectAll Also return parameter fields?
     * @return array Result Set
     */
    public function getByProcedure($pid, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('parameter.deleted', 0);
        }
//        if ($this->config->item('server') != 'internal') {
//            $this->db->where('parameter.internal', 0);
//        }
        if ($selectAll) {
            $this->db->select('php.*, parameter.*');
        } else {
            $this->db->select('php.*');
        }
        return $this->db->from(self::TABLE . ' AS php')
                        ->join('parameter', 'parameter.parameter_id = php.parameter_id')
                        ->where('php.procedure_id', $pid)
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }

    /**
     * @param int $pid
     * @param boolean $selectAll Also return parameter fields?
     * @return array Result Set
     */
    private function _getByProcedure($pid, $selectAll = true)
    {
        if ($selectAll) {
            $this->db->select('php.*, parameter.*');
        } else {
            $this->db->select('php.*');
        }
        return $this->db->from(self::TABLE . ' AS php')
                        ->join('parameter', 'parameter.parameter_id = php.parameter_id')
                        ->where('php.procedure_id', $pid)
                        ->order_by('php.weight')
                        ->get()
                        ->result_array();
    }

    public function getByParameter($pid)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('parameter.deleted', 0);
//        if ($this->config->item('server') != 'internal')
//            $this->db->where('parameter.internal', 0);
        return $this->db->select('php.*')
                        ->from(self::TABLE . ' AS php')
                        ->join('parameter', 'parameter.parameter_id = php.parameter_id')
                        ->where('php.parameter_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function getByProcedureAndParameter($procedureId, $parameterId)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('parameter.deleted', 0);
            $this->db->where('procedure.deleted', 0);
        }
//        if ($this->config->item('server') != 'internal') {
//            $this->db->where('parameter.internal', 0);
//            $this->db->where('procedure.internal', 0);
//        }
        return $this->db->select('php.*')
                        ->from(self::TABLE . ' AS php')
                        ->join('procedure', 'procedure.procedure_id = php.procedure_id')
                        ->join('parameter', 'parameter.parameter_id = php.parameter_id')
                        ->where('php.procedure_id', (int)$procedureId)
                        ->where('php.parameter_id', (int)$parameterId)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }
    
    private function _getByProcedureAndParameter($procedureId, $parameterId)
    {
        return $this->db->select('php.*')
                        ->from(self::TABLE . ' AS php')
                        ->join('procedure', 'procedure.procedure_id = php.procedure_id')
                        ->join('parameter', 'parameter.parameter_id = php.parameter_id')
                        ->where('php.procedure_id', (int)$procedureId)
                        ->where('php.parameter_id', (int)$parameterId)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    /**
    * @param array|int $p If a hashmap array is supplied it tries to insert it but
    * if a procedure_id is supplied then you are expected to supply the parameter_id
    * @param int $parameterId parameter_id should be supplied with $procedure_id as
    * the first argument
    * @param int $weight The weight (display order) of the new item
    * @return int|bool Last insert id or false if something's not right
    */
    public function insert($p, $parameterId = null, $weight = null)
    {
        if (is_array($p)) {
            $p['weight'] = (isset($p['weight'])) ? abs((int)$p['weight']) : 1 + $this->_getMaxParameterWeightByProcedure($p[ProcedureModel::PRIMARY_KEY]);
            $existingRelationships = $this->_getByProcedureAndParameter($p[ProcedureModel::PRIMARY_KEY], $p[ParameterModel::PRIMARY_KEY]);
            if (count($existingRelationships) == 0) {
                $this->db->insert(self::TABLE, $this->_filterFields($p));
                return $this->db->insert_id();
            }
            return true;
        } else if (is_numeric($p) && $parameterId != null) {
            return $this->insert(array(
                ProcedureModel::PRIMARY_KEY => (int)$p,
                ParameterModel::PRIMARY_KEY => (int)$parameterId,
                'weight' => ( ! is_null($weight)) ? abs((int)$weight) : 1 + $this->_getMaxParameterWeightByProcedure($p)
            ));
        }
        
        return false;
    }

    /**
    * @param int $id Either supply a row id by itself or the procedure_id as the first param
    * but if you supply the procedure_id you are expected to also supply the parameter_id
    * @param int $parameterId When the parameter_id is supplied with the procedure_id it
    * deletes the record(s) where these match with your supplied arguments
    * @return int no. of affected rows, usually 1
    */
    public function delete($id, $parameterId = null)
    {
        if (empty($id))
            return 0;

        //delete by id
        if ($parameterId == NULL) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        //delete by procedure_id and parameter_id
        } else {
            $this->db->where('procedure_id', $id)
                     ->where('parameter_id', $parameterId)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        }
    }

    /**
    * Move a record up or down in display order
    * @param int $parameterId parameter id
    * @param int $procedureId the id of the procedure in which this parameter resides
    * @param string $direction should be either "up" or "dn"
    * @return bool moved
    * @see parametermodel::move()
    */
    public function move($parameterId, $procedureId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $params = $this->getByProcedure($procedureId, false);

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($params); $i++) {
                if ($params[$i]['parameter_id'] == $parameterId) {
                    $current = $params[$i];
                    if(isset($params[$i + 1]))
                        $next = $params[$i + 1];
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
            for ($i = 0; $i < count($params); $i++) {
                if ($params[$i]['parameter_id'] == $parameterId) {
                    $current = $params[$i];
                    if(isset($params[$i - 1]))
                        $prev = $params[$i - 1];
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
	
    public function resequence($procedureId) {
        $params = $this->_getByProcedure($procedureId);
        $counter = 0;
        foreach ($params as $p) {
            $this->db->where(self::PRIMARY_KEY, $p[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $counter));
            $counter++;
        }
    }

    private function _filterFields($arr) {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }

    private function _getFields() {
        return array(self::PRIMARY_KEY, 'procedure_id', 'parameter_id', 'weight');
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

    private function _getMaxParameterWeightByProcedure($procedureId)
    {
        if (empty($procedureId) || ! is_numeric($procedureId))
            return 0;
        $r = $this->db->select_max('weight')
                      ->from(self::TABLE)
                      ->where('procedure_id', (int)$procedureId)
                      ->limit(1)
                      ->get()
                      ->row_array();
        return (empty($r)) ? 0 : (int)$r['weight'];
    }

    /**
    * Creates links between the new procedure and the parameters of the original procedure
    * @param array $arr The expected keys are old_procedure_id, old_pipeline_id,
    * new_procedure_id, new_pipeline_id and except_parameter
    * @return int|bool number of new links created or FALSE if original/new id not found or bad argument
    */
    public function copyProcedureParametersToNewProcedure(array $arr)
    {        
        if ( ! is_array($arr))
            return false;
        
        $oldProc = $this->proceduremodel->getByPipelineAndProcedure($arr['old_pipeline_id'], $arr['old_procedure_id']);
        $newProc  = $this->proceduremodel->getByPipelineAndProcedure($arr['new_pipeline_id'], $arr['new_procedure_id']);
        if (empty($oldProc) || empty($newProc))
            return false;

        $params = $this->_getByProcedure($arr['old_procedure_id']);
        $exceptParameter = (isset($arr['except_parameter'])) ? (array)$arr['except_parameter'] : array();
        $linksCreated = 0;
        foreach ($params as $param) {
            if (in_array($param[ParameterModel::PRIMARY_KEY], $exceptParameter))
                continue;

            if($this->insert($arr['new_procedure_id'], $param[ParameterModel::PRIMARY_KEY], $param['weight']))
                $linksCreated++;
        }
        return $linksCreated;
    }
}
