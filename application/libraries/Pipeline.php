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
 * <pre>
 * Pipeline
 *     ^| {contains}
 *       --- Procedures
 *             ^| {contains}
 *               --- Parameters
 * </pre>
 */
class Pipeline extends Cohort
{
    /**
    * @var array An array of all the Procedures for this pipeline
    */
    private $_procedures = array();
    /**
    * @var int $weight the display order of the records - heavier items to bottom, lighter to top
    */
    protected $_weight;

    /**
    * @param int $pipelineId
    */
    public function __construct($pipelineId = null)
    {
        parent::__construct();
        $this->_prefix = 'PIPE';
        $this->setPipelineId($pipelineId);
    }

    /**
    * @param int|string $pipelineId
    */
    public function setPipelineId($pipelineId = null)
    {
        if ($pipelineId != null) {
            //fetch the row from the db by id or key
            if ( ! is_numeric($pipelineId)) {
                $row = $this->CI->pipelinemodel->getByKey($pipelineId);
            } else {
                $row = $this->CI->pipelinemodel->getById((int)$pipelineId);
            }
            $this->seed($row);
        }
    }
    
    /**
     * @inherit
     */
    public function seed(array $row = array())
    {
        if ( ! empty($row)) {
            $this->_id = $row['pipeline_id'];
            $this->_itemKey = $row['pipeline_key'];
            $this->_itemName = $row['name'];
            $this->_weight = $row['weight'];
            $this->_visible = (bool) $row['visible'];
            $this->_deprecated = (bool) $row['deprecated'];
            $this->_active = (bool) $row['active'];
            $this->_description = $row['description'];
            $this->_majorVersion = $row['major_version'];
            $this->_minorVersion = $row['minor_version'];
            $this->_timeModified = $row['time_modified'];
            $this->_userId = $row['user_id'];
            $this->_internal = (bool) $row['internal'];
            $this->_deleted = (bool) $row['deleted'];
        }
    }

    /**
    * @return array An array of Procedures in this Pipeline
    */
    public function getProcedures()
    {
        if (empty($this->_procedures)) {
            $this->_procedures = PipelineHasProcedures::getProcedures($this->_id);
        }
        return $this->_procedures;
    }

    /**
    * @return int weight
    */
    public function getWeight()
    {
        return $this->_weight;
    }
}
