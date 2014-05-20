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
class Parameter extends Cohort
{
    /**
    * @var string $_type Enum value from EParamType
    */
    private $_type;
    /**
    * @var string $_valueType Enum value from EParamValueType
    */
    private $_valueType;
    /**
    * @var array $_properties containing a hashmap of this objects' properties
    */
    private $_properties;
    /**
    * @var array ParamIncrement array, $_increments, contains the increment object of a Parameter, if it has any increments
    */
    private $_increments;
    /**
    * @var array $_ontologies Ontology array, containes the Ontology objects associated with a Parameter, if any
    */
    private $_ontologies;
    /**
    * @var ParamOntology
    */
    private $_ontology;
    /**
    * @var array $_options ParamOption array
    */
    private $_options;
    /**
    * @var Unit $_unit e.g. bpm
    */
    private $_unit;
    /**
    * @var OntologyGroup[] $_ontologyGroups
    */
    private $_ontologyGroups;
    /**
    * @var string derivation description
    */
    private $_derivation;
    /**
    * @var int procedureId
    */
    private $_procedureId;
    /**
    * @var bool QCCheck flag to say if QC check should be carried out or not
    */
    private $_qcCheck;
    /**
    * @var float QCMin
    */
    private $_qcMin;
    /**
    * @var float QCMax
    */
    private $_qcMax;
    /**
    * @var string QCNotes
    */
    private $_qcNotes;
    /**
    * @var string graphType Enum value from EParamGraphType
    */
    private $_graphType;
    /**
    * @var string dataAnalysisNotes
    */
    private $_dataAnalysisNotes;


    /**
    * @param int|string $parameterId
    * @param int $procedureId optional
    */
    public function __construct($parameterId = null, $procedureId = null)
    {
        parent::__construct();
        $this->_prefix = 'PARA';
        $this->setProcedureId($procedureId);
        $this->setParameterId($parameterId);
    }

    /**
    * @param int|string $parameterId
    */
    public function setParameterId($parameterId = null)
    {
        if ($parameterId != null) {
            //fetch the row from the db by id
            if ( ! is_numeric($parameterId)) {
                $row = $this->CI->parametermodel->getByKey($parameterId);
            } else {
                $row = $this->CI->parametermodel->getById((int)$parameterId);
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
            $this->_id = $row['parameter_id'];
            $this->_itemKey = $row['parameter_key'];
            $this->_type = $row['type'];
            $this->_itemName = $row['name'];
            $this->_visible = (bool) $row['visible'];
            $this->_active = (bool) $row['active'];
            $this->_deprecated = (bool) $row['deprecated'];
            $this->_derivation = $row['derivation'];
            $this->_description = $row['description'];
            $this->_majorVersion = $row['major_version'];
            $this->_minorVersion = $row['minor_version'];
            $this->_timeModified = $row['time_modified'];
            $this->_userId = $row['user_id'];
            $this->_internal = (bool) $row['internal'];
            $this->_valueType = $row['value_type'];
            $this->_unit = new Unit($row['unit']);
            //$this->_ontologyGroup = new OntologyGroup($row['ontology_group_id']);
            $this->_qcCheck = (bool) $row['qc_check'];
            $this->_qcMin = $row['qc_min'];
            $this->_qcMax = $row['qc_max'];
            $this->_qcNotes = $row['qc_notes'];
            $this->_graphType = $row['graph_type'];
            $this->_dataAnalysisNotes = $row['data_analysis_notes'];
            $this->_deleted = (bool) $row['deleted'];
            $this->_properties = array(
                'derived'   => (bool) $row['is_derived'],
                'increment' => (bool) $row['is_increment'],
                'option'    => (bool) $row['is_option'],
                'required'  => (bool) $row['is_required'],
                'important' => (bool) $row['is_important'],
                'annotation'=> (bool) $row['is_annotation']
            );
        }
    }

    /**
    * @param int $procedureId
    */
    public function setProcedureId($procedureId = null)
    {
        if ($procedureId != null)
            $this->_procedureId = (int)$procedureId;
    }

    /**
    * @return bool
    */
    public function isRequired()
    {
        return (bool) $this->_properties['required'];
    }

    /**
    * @return bool
    */
    public function isDerived()
    {
        return (bool) $this->_properties['derived'];
    }

    /**
    * @return bool
    */
    public function isIncrement()
    {
        return (bool) $this->_properties['increment'];
    }

    /**
    * @return bool
    */
    public function isOption()
    {
        return (bool) $this->_properties['option'];
    }

    /**
    * @return bool
    */
    public function isAnnotation()
    {
        return (bool) $this->_properties['annotation'];
    }

    /**
    * @return bool
    */
    public function isImportant()
    {
        return (bool) $this->_properties['important'];
    }

    /**
    * @return string EParamType string
    */
    public function getType()
    {
        return $this->_type;
    }

    /**
    * @return OntologyGroup
    */
    public function getOntologyGroups()
    {
		if ($this->_ontologyGroups == null)
			$this->_ontologyGroups = ParameterHasOntologyGroups::getGroups($this->_id);
		return $this->_ontologyGroups;
    }

    /**
    * @return string EParamValueType string
    */
    public function getValueType()
    {
        return $this->_valueType;
    }

    /**
    * @return ParamOption[] array of ParamOption objects
    */
    public function getOptions()
    {
        if ($this->_options == null)
            $this->_options = ParameterHasOptions::getOptions($this->_id);
        return $this->_options;
    }

    /**
    * @return ParamIncrement[] array of ParamIncrement objects
    */
    public function getIncrements()
    {
        if ($this->_increments != null)
            return $this->_increments;

        //else create and fill Increments objects array
        $this->_increments = array();
        $this->CI->load->model('paramincrementmodel');
        foreach ($this->CI->paramincrementmodel->getByParameter($this->_id) AS $pi) {
            $this->_increments[] = new ParamIncrement($pi[ParamIncrementModel::PRIMARY_KEY]);
        }
        return $this->_increments;
    }

    public function getOntologies()
    {
        //metadata parameters do not (should not) have ontologies
        if ($this->getType() == EParamType::METADATA)
            return array();

        if ($this->_ontologies != null)
            return $this->_ontologies;

        //else fill the var with Ontology objects that are associated with this parameter
        $this->_ontolgies = array();
        $this->CI->load->model('ontologymodel');
        foreach ($this->CI->ontologymodel->getByParameter($this->_id) as $ont) {
            $this->_ontolgies[] = new Ontology($ont['id']);
        }
        return $this->_ontolgies;
    }

    /**
    * @return ParamOntology
    */
    public function getOntology()
    {
        if ($this->_ontology == null)
            $this->_ontology = new ParamOntology($this->_id);
        return $this->_ontology;
    }

    /**
    * @return string unit
    */
    public function getUnit()
    {
        if($this->_unit instanceof Unit)
            return (string) $this->_unit->getUnit();
        return '';
    }

    /**
    * @return string derivation description
    */
    public function getDerivation()
    {
        return $this->_derivation;
    }

    /**
    * @return int The id of the Procedure for this Parameter
    */
    public function getProcedureId()
    {
        return $this->_procedureId;
    }

    /**
    * @return bool|array false if QC Check not set/false otherwise a hash of the min and max e.g. array('min'=>null,'max'=>1.0)
    */
    public function qcCheck()
    {
        if ($this->_qcCheck)
            return array('min' => $this->getQCMin(), 'max' => $this->getQCMax());
        return false;
    }

    /**
    * @return float|null null if QCCheck false otherwise the min value
    */
    public function getQCMin()
    {
        if ($this->_qcCheck)
            return (is_null($this->_qcMin)) ? null : (float)$this->_qcMin;
        return null;
    }

    /**
    * @return float|null null if QCCheck false otherwise the max value
    */
    public function getQCMax()
    {
        if ($this->_qcCheck)
            return (is_null($this->_qcMax)) ? null : (float)$this->_qcMax;
        return null;
    }

    /**
    * @return string qc notes
    */
    public function getQCNotes()
    {
        return $this->_qcNotes;
    }

    /**
    * @return string EParamGraphType value
    */
    public function getGraphType()
    {
        return $this->_graphType;
    }

    /**
    * @return string data analysis notes
    */
    public function getDataAnalysisNotes()
    {
        return $this->_dataAnalysisNotes;
    }
}
