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
class Procedure extends Cohort
{
    /**
     * @var array An array of all the Parameter for this Procedure
     */
    private $_parameters = array();

    /**
     * @var ProcedureWeek $_week
     */
    private $_week;

    /**
     * @var int $_pipelineId
     */
    private $_pipelineId;

    /**
     * @var int $_procedureId
     */
    private $_procedureId;

    /**
     * @var bool $_isMandatory
     */
    private $_isMandatory;

    /**
     * @var SOP $_sop
     */
    private $_sop;

    /**
     * @var ProcedureType $_type
     */
    private $_type;
    
    /**
     * @var string $level
     */
    private $_level;
    
    /**
     * @var int
     */
    private $_minFemales;
    
    /**
     * @var int
     */
    private $_minMales;
    
    /**
     * @var int
     */
    private $_minAnimals;

    /**
     * @param int|Procedure $procedureId
     * @param int|Pipeline $pipelineId optional
     */
    public function __construct($procedureId = null, $pipelineId = null)
    {
        parent::__construct();
        $this->setPipelineId($pipelineId);
        $this->setProcedureId($procedureId);
        $this->get();
    }

    /**
     * Initiates fetching the data from the model and populating the properties
     * of this class
     */
    public function get()
    {
        if (is_null($this->_pipelineId) && $this->_procedureId) {
            $this->_setByOriginalPathway();
        } else if ($this->_pipelineId && $this->_procedureId) {
            $this->CI->load->model('pipelinehasproceduresmodel');
            $row = $this->CI->pipelinehasproceduresmodel->getByPipelineAndProcedure($this->_pipelineId, $this->_procedureId);
            $this->seed($row);
        }
    }
    
    /**
     * @inherit
     */
    public function seed(array $row = array())
    {
        if ( ! empty($row)) {
            $this->_id = $row[ProcedureModel::PRIMARY_KEY];
            $this->_itemKey = $row['procedure_key'];
            $this->_itemName = $row['name'];
            $this->_type = $row['type'];
            $this->_level = $row['level'];
            $this->_visible = (bool) $row['is_visible'];
            $this->_active = (bool) $row['is_active'];
            $this->_deprecated = (bool) $row['is_deprecated'];
            $this->_isMandatory = (bool) $row['is_mandatory'];
            $this->_description = $row['description'];
            $this->_majorVersion = $row['major_version'];
            $this->_minorVersion = $row['minor_version'];
            $this->_timeModified = $row['time_modified'];
            $this->_userId = $row['user_id'];
            $this->_internal = (bool) $row['is_internal'];
            $this->_week = $row['week'];
            $this->_minFemales = $row['min_females'];
            $this->_minMales = $row['min_males'];
            $this->_minAnimals = $row['min_animals'];
            $this->_deleted = (bool) $row['is_deleted'];
        }
    }

    /**
     * If only Procedure Id is supplied it tries to look up the Pipeline from
     * the original pathways table
     */
    private function _setByOriginalPathway()
    {
        $this->CI->load->model('originalpathwaysmodel');
        $pathways = $this->CI->originalpathwaysmodel->getPathwaysByProcedure($this->_procedureId);
        $firstPathway = current($pathways);
        if ($firstPathway) {
            $this->_pipelineId = $firstPathway[PipelineModel::PRIMARY_KEY];
            $this->get();
        }
    }

    /**
     * @param mixed $pipelineId
     */
    public function setPipelineId($pipelineId = null)
    {
        if ($pipelineId instanceof Pipeline) {
            $this->_pipelineId = $pipelineId->getId();
        } else if (is_numeric($pipelineId)) {
            $this->_pipelineId = (int)$pipelineId;
        } else if (is_string($pipelineId)) {
            $pip = $this->CI->pipelinemodel->getByKey($pipelineId);
            if ( ! empty($pip))
                $this->_pipelineId = $pip[PipelineModel::PRIMARY_KEY];
        }
    }
    
    /**
     * @return int
     */
    public function getPipelineId()
    {
        return $this->_pipelineId;
    }
    
    /**
     * @return int
     */
    public function getProcedureId()
    {
        return $this->_procedureId;
    }

    /**
     * @param int|Procedure $procedureId
     */
    public function setProcedureId($procedureId = null)
    {
        if ($procedureId instanceof Procedure) {
            $this->_procedureId = $procedureId->getId();
        } else if (is_numeric($procedureId)) {
            $this->_procedureId = (int) $procedureId;
        } else if (is_string($procedureId)) {
            $proc = $this->CI->proceduremodel->getByKey($procedureId);
            if ( ! empty($proc))
                $this->_procedureId = $proc[ProcedureModel::PRIMARY_KEY];
        }
    }

    /**
     * @return array The Parameters for this procedure
     */
    public function getParameters()
    {
        if (empty($this->_parameters)) {
            $this->_parameters = ProcedureHasParameters::getParameters($this->_id);
        }
        return $this->_parameters;
    }

    /**
     * @return int the week this procedure should have been carried out
     */
    public function getWeek()
    {
        if ( ! ($this->_week instanceof ProcedureWeek))
            $this->_week = new ProcedureWeek($this->_week);
        return $this->_week->getWeekNumber();
    }
    
    /**
     * @return ProcedureWeek
     */
    public function getWeekObject()
    {
        if ( ! ($this->_week instanceof ProcedureWeek))
            $this->_week = new ProcedureWeek($this->_week);
        return $this->_week;
    }

    /**
     * @return string the week this procedure should be carried out in
     */
    public function getWeekLabel()
    {
        if ( ! ($this->_week instanceof ProcedureWeek))
            $this->_week = new ProcedureWeek($this->_week);
        return $this->_week->getLabel();
    }

    /**
     * @return bool Mandatory or not
     */
    public function isMandatory()
    {
        return (bool) $this->_isMandatory;
    }

    /**
     * @return SOP
     */
    public function getSOP()
    {
        if ($this->_sop != null)
            return $this->_sop;
        $this->CI->load->model('sopmodel');
        $sop = $this->CI->sopmodel->getByProcedure($this->getId());
        if ( ! empty($sop))
            $this->_sop = new SOP($sop[SOPModel::PRIMARY_KEY], $this);
        else
            $this->_sop = new SOP();
        return $this->_sop;
    }

    /**
     * @return ProcedureType
     */
    public function getType()
    {
        if ( ! ($this->_type instanceof ProcedureType))
            $this->_type = new ProcedureType($this->_type);
        return $this->_type;
    }
    
    /**
     * @return string level from EProcedureLevel
     */
    public function getLevel()
    {
        return $this->_level;
    }
    
    /**
     * @return int Minimum number of females for valid submission
     */
    public function getMinFemales()
    {
        return $this->_minFemales;
    }

    /**
     * @return int Minimum number of males for valid submission
     */
    public function getMinMales()
    {
        return $this->_minMales;
    }
    
    /**
     * @return int Minimum number of either female or male animals for valid submission
     */
    public function getMinAnimals()
    {
        return $this->_minAnimals;
    }
}
