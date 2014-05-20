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
 * Parameter Has Options model
 */
class ParameterHasOptionsModel extends CI_Model
{
    const TABLE = 'parameter_has_options';
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
     * @param boolean $selectAll return both parameter_has_options and param_option fields
     * @return array Result Set
     */
    public function getByParameter($pid, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('po.deleted', 0);
        }
        if ($selectAll) {
            $this->db->select('pho.*, po.*');
        } else {
            $this->db->select('pho.*');
        }
        return $this->db->from('parameter_has_options AS pho, param_option AS po')
                        ->join('parameter p', 'p.parameter_id = pho.parameter_id')
                        ->where('pho.parameter_id', $pid)
                        ->where('po.param_option_id', 'pho.param_option_id', false)
                        ->order_by('pho.weight')
                        ->get()
                        ->result_array();
    }

    public function getByOption($oid)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('po.deleted', 0);
        }
        return $this->db->select('pho.*')
                        ->from('parameter_has_options AS pho, parameter AS p')
                        ->join('param_option po', 'po.param_option_id = pho.param_option_id')
                        ->where('pho.param_option_id', $oid)
                        ->where('p.parameter_id', 'pho.parameter_id', false)
                        ->order_by('pho.weight')
                        ->get()
                        ->result_array();
    }

    /**
     * @param int $parameterId
     * @param int $optionId
     * @param boolean $selectAll return both parameter_has_options and param_option fields
     * @return array Result Set
     */
    public function getByParameterAndOption($parameterId, $optionId, $selectAll = true)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('po.deleted', 0);
        }
        if ($selectAll) {
            $this->db->select('pho.*, po.*');
        } else {
            $this->db->select('pho.*');
        }
        return $this->db->from('parameter_has_options AS pho')
                        ->join('parameter p', 'p.parameter_id = pho.parameter_id')
                        ->join('param_option po', 'po.param_option_id = pho.param_option_id')
                        ->where('pho.parameter_id', (int)$parameterId)
                        ->where('pho.param_option_id', (int)$optionId)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getChildOptionsForParent($parentId)
    {
        $this->load->model('paramoptionmodel');
        $options = $this->paramoptionmodel->getChildOptionsForParent($parentId);
        $ids = array_map(
            function($v){
                return $v['param_option_id'];
            },
            $options
        );
        if(empty($ids))
            return $ids;
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('p.deleted', 0);
            $this->db->where('po.deleted', 0);
        }
        return $this->db->select('pho.*')
                        ->from('parameter_has_options AS pho, parameter AS p')
                        ->join('param_option po', 'po.param_option_id = pho.param_option_id')
                        ->where_in('pho.param_option_id', $ids)
                        ->where('p.parameter_id', 'pho.parameter_id', FALSE)
                        ->order_by('pho.weight')
                        ->get()
                        ->result_array();
    }

    /**
    * @param array|int $p If a hashmap array is supplied it tries to insert it but
    * if a parameter_id is supplied then you are expected to supply the param_option_id
    * @param int $optionId param_option_id should be supplied with $parameter_id as
    * the first argument
    * @param int $weight The weight (display order) of the new item
    * @return int|bool Last insert id or false if something's not right
    */
    public function insert($p, $optionId = NULL, $weight = null)
    {
        if(empty($p))
            die('Function requires at least one argument');

        if (is_array($p)) {
            $this->db->insert(self::TABLE, $this->_filterFields($p));
            return $this->db->insert_id();
        } else if ($optionId != NULL && is_numeric($p)) {
            return $this->insert(
                array(
                    'parameter_id' => (int)$p,
                    'param_option_id' => (int)$optionId,
                    'weight' => ( ! is_null($weight)) ? abs((int)$weight) : 1 + $this->_getMaxOptionWeightByParameter($p)
                )
            );
        }

        //if bad arguments
        return FALSE;
    }

    /**
    * When you create a new version of a Parameter you need to associate the
    * Options from the old Parameter to the new one
    * @param int $oldParameterId
    * @param int $newParameterId
	* @param int|array $exceptOption Copy all options except this one (or these ones (array))
    * @return int|bool Numbers of new links created or FALSE if orig/new Parameter not found
    */
    public function copyOptionsToNewParameter($origParamId, $newParamId, $exceptOption = null)
    {
        $this->load->model('paramoptionmodel');
        $origParam = $this->parametermodel->getById($origParamId);
        $newParam  = $this->parametermodel->getById($newParamId);
        if (empty($origParam) || empty($newParam))
            return false;
        
        $options = $this->getByParameter($origParamId);
		$exceptOption = (array)$exceptOption;
        $linksCreated = 0;
        foreach ($options AS $option) {
			if (in_array($option[ParamOptionModel::PRIMARY_KEY], $exceptOption))
				continue;
            if($this->insert($newParamId, $option['param_option_id'], $option['weight']))
				$linksCreated++;
        }
        return $linksCreated;
    }


    /**
    * @param int $id Either supply a row id by itself or the parameter_id as the first param
    * but if you supply the parameter_id you are expected to also supply the param_option_id
    * @param int $optionId When the param_option_id is supplied with the parameter_id it
    * deletes the record(s) where these match with your supplied arguments
    * @return int no. of affected rows, usually 1
    */
    public function delete($id, $optionId = null)
    {
        if(empty($id))
            return 0;

        //delete by id
        if ($optionId == NULL) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            return $this->db->affected_rows();
        //delete by parameter_id and param_option_id
        } else {
            $pho = $this->getByParameterAndOption($id, $optionId);
            if(empty($pho)) return 0;
            return $this->delete($pho[self::PRIMARY_KEY]);
        }
    }

    /**
    * Move a record up or down in display order
    * @param int $optionId
    * @param int $parameterId the id of the parameter to which this option belongs
    * @param string $direction should be either "up" or "dn"
    * @return bool moved
    * @see parametermodel::move()
    */
    public function move($optionId, $parameterId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $options = $this->getByParameter($parameterId);

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($options); $i++) {
                if ($options[$i]['param_option_id'] == $optionId) {
                    $current = $options[$i];
                    if(isset($options[$i + 1]))
                        $next = $options[$i + 1];
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
            for ($i = 0; $i < count($options); $i++) {
                if ($options[$i]['param_option_id'] == $optionId) {
                    $current = $options[$i];
                    if(isset($options[$i - 1]))
                        $prev = $options[$i - 1];
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
		$opts = $this->getByParameter($parameterId);
		$counter = 0;
		foreach ($opts as $opt) {
			$this->db->where(self::PRIMARY_KEY, $opt[self::PRIMARY_KEY])
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

    private function _getMaxOptionWeightByParameter($parameterId)
    {
        if(empty($parameterId) || ! is_numeric($parameterId))
            return 0;
        $r = $this->db->select_max('weight')
                      ->from(self::TABLE)
                      ->where('parameter_id', (int)$parameterId)
                      ->limit(1)
                      ->get()
                      ->row_array();
        if(empty($r)) return 0;
        return (int) $r['weight'];
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
		return array(self::PRIMARY_KEY, 'parameter_id', 'param_option_id', 'weight');
	}
}
