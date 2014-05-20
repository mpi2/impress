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
* Original Pathways model
*/

class OriginalPathwaysModel extends CI_Model
{
    const TABLE = 'original_pathways';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('pipeline_id')
                        ->order_by('procedure_id')
                        ->order_by('parameter_id')
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
    * @param int $pipelineId
    * @param int $procedureId
    * @param int $parameterId
    */
    public function getPathwaysBy($pipelineId = null, $procedureId = null, $parameterId = null)
    {
        if(empty($pipelineId) && empty($procedureId) && empty($parameterId))
            return array();
        if ( ! empty($pipelineId))
            $this->db->where('pipeline_id', $pipelineId);
        if ( ! empty($procedureId))
            $this->db->where('procedure_id', $procedureId);
        if ( ! empty($parameterId))
            $this->db->where('parameter_id', $parameterId);
        return $this->db->from(self::TABLE)
                        ->order_by(self::PRIMARY_KEY, 'desc')
                        ->get()
                        ->result_array();
    }

    /**
    * @param array $origin
    */
    public function getPathwaysByOrigin(array $origin)
    {
        return $this->getPathwaysBy(
			(isset($origin['pipeline_id']))  ? $origin['pipeline_id']  : null,
			(isset($origin['procedure_id'])) ? $origin['procedure_id'] : null,
			(isset($origin['parameter_id'])) ? $origin['parameter_id'] : null
		);
    }
    
    /**
     * @param int $paramId
     * @return array
     */
    public function getPathwaysByParameter($paramId)
    {
        return $this->getPathwaysByOrigin(array('parameter_id' => $paramId));
    }
    
    /**
     * @param int $procId
     * @return array
     */
    public function getPathwaysByProcedure($procId)
    {
        return $this->getPathwaysByOrigin(array('procedure_id' => $procId));
    }
    
    /**
     * @param int $pipId
     * @return array
     */
    public function getPathwaysByPipeline($pipId)
    {
        return $this->getPathwaysByOrigin(array('pipeline_id' => $pipId));
    }


    /**
	* You supply an $origin - style array for this method with all the required
    * keys present or call this method with the defined parameters
    * @param int|array $pipeline_id pipeline id or $origin array
    * @param int $procedureId
    * @param int $parameterId
	*/
	public function getPathwayMatching($pipelineId = null, $procedureId = null, $parameterId = null)
	{
		if (is_array($pipelineId)) {
			$arr = $pipelineId;
			$pipelineId  = @$arr['pipeline_id'];
			$procedureId = @$arr['procedure_id'];
			$parameterId = @$arr['parameter_id'];
		}
	
		return $this->db->from(self::TABLE)
						->where('pipeline_id', $pipelineId)
						->where('procedure_id', $procedureId)
						->where('parameter_id', $parameterId)
						->limit(1)
						->get()
						->row_array();
	}

    /**
    * @param int|array $pipeline_id pipeline id or $origin array
    * @param int $procedureId
    * @param int $parameterId
	* @alias
	* @see OriginalPathwaysModel::getPathwaysMatching()
    */
    public function isUniquePathway($pipelineId = null, $procedureId = null, $parameterId = null)
    {
		$pathway = $this->getPathwayMatching($pipelineId, $procedureId, $parameterId);
        return empty($pathway);
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
        return array(self::PRIMARY_KEY, 'pipeline_id', 'procedure_id', 'parameter_id');
    }

    /**
    * @param array hash of columns
    * @return int last insert id
    */
    public function insert($arr)
    {
        $this->load->helper('array_keys_exist');
        if ( ! isset($arr['pipeline_id']) || ! isset($arr['procedure_id']))
            return 0;
        if ( ! $this->isUniquePathway($this->_filterFields($arr)))
            return 0;
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
    * @param int|array $id The id of the record as it is in the
    * original_pathways table or an $origin - style array to
    * search the records in the db
    * @return int rows affected
    */
    public function delete($id = array())
    {
        if (is_array($id)) {
            $arr = $id;
            if(empty($arr))
                return 0;
			//if origin-style array then do the first thing,
			//otherwise loop through the array
			$numItemsDeleted = 0;
			if (array_key_exists('procedure_id', $arr)) {
				$record = $this->getPathwayMatching($arr);
				if( ! empty($record))
					$numItemsDeleted += $this->delete($record[self::PRIMARY_KEY]);
			} else {
				foreach($arr AS $i)
					$numItemsDeleted += $this->delete($i);
			}
            return $numItemsDeleted;
        }

        $this->db->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        return $this->db->affected_rows();
    }
}
