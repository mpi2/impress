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
 * IMPReSS SOAP
 * @see Soap.php controller
 */
class ImpressSoap
{
    protected $_badPipMsg = 'The Pipeline key supplied does not exist';
    protected $_badProcMsg = 'The Procedure key supplied does not exist';
    protected $_badParamMsg = 'The Parameter key supplied does not exist';

    /**
    * @return string[] Pipeline keys
    */
    public function getPipelineKeys()
    {
        $keys = array();
        foreach (PipelinesFetcher::getPipelines() as $p)
            $keys[] = (string) $p->getItemKey();
        return $keys;
    }

    /**
    * @param string $procedureKey
    * @return string[] Parameter keys
    * @throws Exception
    */
    public function getParameterKeys($procedureKey = null)
    {
        $proc = new Procedure($procedureKey);
        if ( ! $proc->exists())
            throw new Exception($this->_badProcMsg);
        $params = array();
        foreach ($proc->getParameters() as $param)
            $params[] = (string) $param->getItemKey();
        return $params;
    }

    /**
    * @param string $procedureKey
    * @return mixed Parameters array hash
    * @throws Exception
    */
    public function getParameters($procedureKey = null)
    {
        $keys = $this->getParameterKeys($procedureKey);
        $parameters = array();
        foreach ($keys as $key)
            $parameters[] = $this->getParameter($key);
        return $parameters;
    }

    /**
    * @param string $parameterKey
    * @return mixed Options array hash
    * @see historic getOptions()
    * @throws Exception
    */
    public function getParameterOptions($parameterKey = null)
    {
        $param = new Parameter($parameterKey);
        if ( ! $param->exists())
            throw new Exception($this->_badParamMsg);
        $options = array();
        foreach ($param->getOptions() as $option) {
            $options[] = array(
                'name'        => (string) $option->getName(),
                'description' => (string) $option->getDescription(),
                'parent_name' => (string) ($option->getParent()->getName() == $option->getName()) ? '' : $option->getParent()->getName(),
                'parent_description' => (string) ($option->getParent()->getName() == $option->getName()) ? '' : $option->getParent()->getDescription(),
                'is_active'   => (bool) $option->isActive()
            );
        }
        return $options;
    }

    /**
    * @param string $parameterKey
    * @return mixed Increments array hash with 'string', 'type', 'unit' and 'min' as keys
    * @see historic getIncrement()
    * @throws Exception
    */
    public function getParameterIncrements($parameterKey = null)
    {
        $param = new Parameter($parameterKey);
        if( ! $param->exists())
            throw new Exception($this->_badParamMsg);
        $increments = array();
        foreach ($param->getIncrements() as $inc) {
            $increments[] = array(
                'string'    => (string) $inc->getIncrementString(),
                'type'      => (string) $inc->getIncrementType(),
                'unit'      => (string) $inc->getIncrementUnit(),
                'min'       => (is_null($inc->getIncrementMin())) ? null : (int)$inc->getIncrementMin(),
                'is_active' => (bool)   $inc->isActive()
            );
        }
        return $increments;
    }

    /**
    * @param string $parameterKey
    * @return mixed MP array hash with 'selection_outcome', 'mp_id', 'mp_term',
    * 'sex', 'option', 'increment' as keys
    * @throws Exception
    */
    public function getParameterMPTerms($parameterKey = null)
    {
        $param = new Parameter($parameterKey);
        if ( ! $param->exists())
            throw new Exception($this->_badParamMsg);
        $mpterms = array();
        foreach ($param->getOntology()->getMPTerms() as $mp) {
            $option = ($mp->getOption()->exists()) ? $mp->getOption()->getName() : null;
            $increment = ($mp->getIncrement()->exists()) ? $mp->getIncrement()->getIncrementString() : null;
            $mpterms[] = array(
                'selection_outcome' => $mp->getSelectionOutcome(),
                'mp_id'             => $mp->getMPId(),
                'mp_term'           => $mp->getMPTerm(),
                'sex'               => $mp->getSex(),
                'option'            => $option,
                'increment'         => $increment
            );
        }
        return $mpterms;
    }

    /**
    * @param string $parameterKey
    * @return mixed EQ array hash with 'selection_outcome', 'sex', 'option',
    * 'increment', entity(1|2|3)_(id|term) and quality(1|2)_(id|term)
    * @throws Exception
    */
    public function getParameterEQTerms($parameterKey = null)
    {
        $param = new Parameter($parameterKey);
        if ( ! $param->exists())
            throw new Exception($this->_badParamMsg);
        $eqterms = array();
        foreach ($param->getOntology()->getEQTerms() as $eq) {
            $option = ($eq->getOption()->exists()) ? $eq->getOption()->getName() : null;
            $increment = ($eq->getIncrement()->exists()) ? $eq->getIncrement()->getIncrementString() : null;
            $eqterms[] = array_merge(
                array(
                    'selection_outcome' => $eq->getSelectionOutcome(),
                    'sex'               => $eq->getSex(),
                    'option'            => $option,
                    'increment'         => $increment
                ),
                $eq->getEQs()
            );
        }
        return $eqterms;
    }

    /**
    * @param string $parameterKey
    * @return mixed Ontology array hash with 'ontology_id' and 'ontology_term' keys
    * @throws Exception
    */
    public function getParameterOntologyOptions($parameterKey = null)
    {
        $param = new Parameter($parameterKey);
        if ( ! $param->exists())
            throw new Exception($this->_badParamMsg);
        $options = array();
        foreach ($param->getOntologyGroups() as $group) {
            foreach ($group->getOntologyOptions() as $option) {
                $options[] = array(
                    'ontology_id'   => $option->getOntologyId(),
                    'ontology_term' => $option->getOntologyTerm()
                );
            }
        }
        return $options;
    }

    /**
    * @param string $pipelineKey
    * @return string[] Procedure keys
    * @see historic getProcedures()
    * @throws Exception
    */
    public function getProcedureKeys($pipelineKey = null)
    {
        $pip = new Pipeline($pipelineKey);
        if( ! $pip->exists())
            throw new Exception($this->_badPipMsg);
        $procs = array();
        foreach ($pip->getProcedures() as $proc)
            $procs[] = (string) $proc->getItemKey();
        return $procs;
    }

    /**
    * @param string $pipelineKey
    * @return mixed Procedures array hash
    * @throws Exception
    */
    public function getProcedures($pipelineKey = null)
    {
        $keys = $this->getProcedureKeys($pipelineKey);
        $procedures = array();
        foreach ($keys as $key)
            $procedures[] = $this->getProcedure($key, $pipelineKey);
        return $procedures;
    }

    /**
    * @param string $key
    * @param string $type 'pipeline', 'procedure' or 'parameter'
    * @return bool
    * @throws Exception if type not 'pipeline', 'procedure' or 'parameter'
    */
    private function _isValid($key, $type)
    {
        $type = ucfirst($type);
        if ( ! in_array($type, array('Pipeline', 'Procedure', 'Parameter'))) {
            throw new Exception('Invalid type supplied. Type must be pipeline, procedure or parameter.');
        }
        $p = new $type($key);
        return $p->exists();
    }

    /**
    * @param string $pipelineKey
    * @return bool
    */
    public function isValidPipeline($pipelineKey = null)
    {
        return (bool) $this->_isValid($pipelineKey, 'pipeline');
    }

    /**
    * @param string $procedureKey
    * @return bool
    */
    public function isValidProcedure($procedureKey = null)
    {
        return (bool) $this->_isValid($procedureKey, 'procedure');
    }

    /**
    * @param string $parameterKey
    * @return bool
    */
    public function isValidParameter($parameterKey = null)
    {
        return (bool) $this->_isValid($parameterKey, 'parameter');
    }

    /**
    * @param string $pipelineKey
    * @param string $procedureKey
    * @return bool
    * @throws Exception if pipelineKey not found
    */
    public function pipelineHasProcedure($pipelineKey = null, $procedureKey = null)
    {
        $pip = new Pipeline($pipelineKey);
        if ( ! $pip->exists())
            throw new Exception($this->_badPipMsg);
        foreach ($pip->getProcedures() as $proc) {
            if (is_numeric($procedureKey)) {
                $pid = $proc->getId();
            } else {
                $pid = $proc->getItemKey();
            }
            if($pid == $procedureKey)
                return true;
        }
        return false;
    }

    /**
    * @param string $procedureKey
    * @param string $parameterKey
    * @return bool
    * @throws Exception if procedureKey not found
    */
    public function procedureHasParameter($procedureKey = null, $parameterKey = null)
    {
        $proc = new Procedure($procedureKey);
        if ( ! $proc->exists())
            throw new Exception($this->_badProcMsg);
        foreach ($proc->getParameters() as $param) {
            if (is_numeric($parameterKey)) {
                $pid = $param->getId();
            } else {
                $pid = $param->getItemKey();
            }
            if ($pid == $parameterKey)
                return true;
        }
        return false;
    }

    /**
    * @param string $parameterKey
    * @return mixed Array hash
    * @throws Exception
    */
    public function getParameter($parameterKey = null)
    {
        $p = new Parameter($parameterKey);
        if ( ! $p->exists())
            throw new Exception($this->_badParamMsg);
        return array(
            'parameter_id'   => (int) $p->getId(),
            'parameter_key'  => (string) $p->getItemKey(),
            'type'           => (string) $p->getType(),
            'parameter_name' => (string) $p->getItemName(),
            'major_version'  => (int) $p->getMajorVersion(),
            'minor_version'  => (int) $p->getMinorVersion(),
            'derivation'     => (string) $p->getDerivation(),
            'description'    => (string) $p->getDescription(),
            'is_annotation'  => (bool) $p->isAnnotation(),
            'is_derived'     => (bool) $p->isDerived(),
            'is_increment'   => (bool) $p->isIncrement(),
            'is_option'      => (bool) $p->isOption(),
            'is_required'    => (bool) $p->isRequired(),
            'is_deprecated'  => (bool) $p->isDeprecated(),
            'unit'           => (string) $p->getUnit(),
            'qc_check'       => (bool) $p->qcCheck(),
            'qc_minimum'     => (float) $p->getQCMin(),
            'qc_maximum'     => (float) $p->getQCMax(),
            'qc_notes'       => (string) $p->getQCNotes(),
            'value_type'     => (string) $p->getValueType(),
            'graph_type'     => (string) $p->getGraphType(),
            'data_analysis_notes' => (string) $p->getDataAnalysisNotes(),
            'is_required_for_data_analysis' => (bool) $p->isImportant()
        );
    }

    /**
    * @param string $procedureKey
    * @param string $pipelineKey
    * @return mixed Array hash
    * @throws Exception
    */
    public function getProcedure($procedureKey = null, $pipelineKey = null)
    {
        $p = new Procedure($procedureKey, $pipelineKey);
        if ( ! $p->exists() || ! should_display($p))
            throw new Exception($this->_badProcMsg);
        return array(
            'procedure_id'   => (int) $p->getId(),
            'procedure_key'  => (string) $p->getItemKey(),
            'procedure_name' => (string) $p->getItemName(),
            'major_version'  => (int) $p->getMajorVersion(),
            'minor_version'  => (int) $p->getMinorVersion(),
            'stage'          => (string) $p->getWeekObject()->getStageLabel(), //(string) $p->getWeekObject()->getStage() . $p->getWeekObject()->getWeekNumber(),
            'stage_label'    => (string) $p->getWeekLabel(),
            'level'          => (string) $p->getLevel(),
            'min_females'    => (is_null($p->getMinFemales())) ? null : (int)$p->getMinFemales(),
            'min_males'      => (is_null($p->getMinMales())) ? null : (int)$p->getMinMales(),
            'min_animals'    => (is_null($p->getMinAnimals())) ? null : (int)$p->getMinAnimals(),
            'is_mandatory'   => (bool) $p->isMandatory(),
            'is_deprecated'  => (bool) $p->isDeprecated(),
            'description'    => (string) $p->getDescription()
        );
    }

    /**
    * @param string $pipelineKey
    * @return mixed Array hash
    * @throws Exception
    */
    public function getPipeline($pipelineKey = null)
    {
        $p = new Pipeline($pipelineKey);
        if ( ! $p->exists() || ! should_display($p))
            throw new Exception($this->_badPipMsg);
        return array(
            'pipeline_id'   => (int) $p->getId(),
            'pipeline_key'  => (string) $p->getItemKey(),
            'pipeline_name' => (string) $p->getItemName(),
            'major_version' => (int) $p->getMajorVersion(),
            'minor_version' => (int) $p->getMinorVersion(),
            'description'   => (string) $p->getDescription(),
            'is_deprecated' => (bool) $p->isDeprecated()
        );
    }

    /**
    * @return string DateTime in W3C format
    */
    public function getWhenLastModified()
    {
        $ci =& get_instance();
        $ci->load->model('changelogmodel');
        try {
            $now = new DateTime($ci->changelogmodel->getLastEntryDate());
        }
        catch (Exception $e) {
            $now = new DateTime();
        }
        return (string) $now->format(DateTime::W3C);
    }
}
