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
 * Parameter Has Ontology Groups model
 */
class ParameterHasOntologyGroupsModel extends CI_Model
{
    const TABLE = 'parameter_has_ontologygroups';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('parameter_id')
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

    /**
     * @param int $pid
     * @param bool $selectAll
     * @return array hash
     */
    public function getByParameter($pid, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('og.deleted', 0);
        }
        if ($selectAll) {
            $this->db->select('pho.*, og.*');
        } else {
            $this->db->select('pho.*');
        }
        return $this->db->from(self::TABLE . ' AS pho, ontology_group AS og')
                        ->join('parameter p', 'p.parameter_id = pho.parameter_id')
                        ->where('pho.parameter_id', $pid)
                        ->where('og.ontology_group_id', 'pho.ontology_group_id', false)
                        ->order_by('pho.weight')
                        ->get()
                        ->result_array();
    }

    public function getByOntologyGroup($ogId)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('og.deleted', 0);
        }
        return $this->db->select('pho.*')
                        ->from(self::TABLE . ' AS pho, parameter AS p')
                        ->join('ontology_group og', 'og.ontology_group_id = pho.ontology_group_id')
                        ->where('pho.ontology_group_id', $ogId)
                        ->where('p.parameter_id', 'pho.parameter_id', false)
                        ->order_by('pho.weight')
                        ->get()
                        ->result_array();
    }

    /**
     * @param int $parameterId
     * @param int $groupId
     * @param bool $selectAll
     * @return array hash
     */
    public function getByParameterAndOntologyGroup($parameterId, $groupId, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('po.deleted', 0);
        }
        if ($selectAll) {
            $this->db->select('pho.*, og.*');
        } else {
            $this->db->select('pho.*');
        }
        return $this->db->from(self::TABLE . ' AS pho')
                        ->join('parameter p', 'p.parameter_id = pho.parameter_id')
                        ->join('ontology_group og', 'og.ontology_group_id = pho.ontology_group_id')
                        ->where('pho.parameter_id', (int)$parameterId)
                        ->where('pho.ontology_group_id', (int)$groupId)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    /**
    * @param array|int $p If a hashmap array is supplied it tries to insert it but
    * if a parameter_id is supplied then you are expected to supply the ontology_group_id
    * @param int $groupId ontology_group_id should be supplied with $parameter_id as
    * the first argument
    * @return int|bool Last insert id or false if something's not right
    */
    public function insert($p, $groupId = null)
    {
        if(empty($p))
            die('Function requires at least one argument');

        if (is_array($p)) {
            $this->db->insert(self::TABLE, $this->_filterFields($p));
            return $this->db->insert_id();
        } else if ($groupId != null && is_numeric($p)) {
            return $this->insert(
                array(
                    'parameter_id' => (int)$p,
                    'ontology_group_id' => (int)$groupId,
                    'weight' => 1 + $this->_getMaxOntologyGroupWeightByParameter($p)
                )
            );
        }

        //if bad arguments
        return false;
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        return keep_array_keys($arr, array('parameter_id', 'ontology_group_id', 'weight'));
    }

    /**
    * When you create a new version of a Parameter you need to associate the
    * Ontology Groups from the old Parameter to the new one
    * @param int $oldParameterId
    * @param int $newParameterId
    * @return array|bool An array of the ids inserted or FALSE if an error occured
    */
    public function copyGroupsToNewParameter($oldParameterId, $newParameterId)
    {
        $newOntologyGroupIds = array();
        foreach ($this->getByParameter($oldParameterId) as $row) {
            $id = $this->insert($newParameterId, $row['ontology_group_id']);
            if($id === false)
                return false;
            else
                $newOntologyGroupIds[] = $id;
        }
        return $newOntologyGroupIds;
    }


    /**
    * @param int $id Either supply a row id by itself or the parameter_id as the first param
    * but if you supply the parameter_id you are expected to also supply the ontology_group_id
    * @param int $ontologyGroupId When the ontology_group_id is supplied with the parameter_id it
    * deletes the record where these match with your supplied arguments
    * @return int num affected rows
    */
    public function delete($id, $ontologyGroupId = null)
    {
        if(empty($id))
            return 0;

        //delete by id
        if ($ontologyGroupId == null) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        //delete by parameter_id and ontology_group_id
        } else {
            $pho = $this->getByParameterAndOntologyGroup($id, $ontologyGroupId);
            return (empty($pho)) ? 0 : $this->delete($pho[self::PRIMARY_KEY]);
        }
    }

    /**
    * Move a record up or down in display order
    * @param int $groupId
    * @param int $parameterId the id of the parameter to which this ontology group belongs
    * @param string $direction should be either "up" or "dn"
    * @return bool moved
    * @see parametermodel::move()
    */
    public function move($groupId, $parameterId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $groups = $this->getByParameter($parameterId);

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($groups); $i++) {
                if ($groups[$i]['ontology_group_id'] == $groupId) {
                    $current = $groups[$i];
                    if(isset($groups[$i + 1]))
                        $next = $groups[$i + 1];
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
            for ($i = 0; $i < count($groups); $i++) {
                if ($groups[$i]['ontology_group_id'] == $groupId) {
                    $current = $groups[$i];
                    if(isset($groups[$i - 1]))
                        $prev = $groups[$i - 1];
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

    public function resequence($parameterId)
    {
        $groups = $this->getByParameter($parameterId);
        $counter = 0;
        foreach ($groups as $group) {
            $this->db->where(self::PRIMARY_KEY, $group[self::PRIMARY_KEY])
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

    private function _getMaxOntologyGroupWeightByParameter($parameterId)
    {
        if(empty($parameterId) || ! is_numeric($parameterId))
            return 0;
        $r = $this->db->select_max('weight')
                      ->from(self::TABLE)
                      ->where('parameter_id', (int)$parameterId)
                      ->limit(1)
                      ->get()
                      ->row_array();
        return (empty($r)) ? 0 : (int)$r['weight'];
    }
}
