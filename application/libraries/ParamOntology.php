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
 * Parameter Ontology class:
 * 
 * This class is part of a new approach to consolidate the mpterms and eqterms
 * tables into one class to access them together. This class is effectively a
 * conceptual crutch - I want to check a particular parameter has ontologies and
 * I want to categorize them into mp or eq terms.
 */
class ParamOntology
{
    /**
    * @var int $_parameterId The id of the parameter to which the ontologies belong
    */
    protected $_parameterId;
    /**
    * @var ParamEQTerm[] array
    */
    protected $_eqterms;
    /**
    * @var ParamMPTerm[] array
    */
    protected $_mpterms;

    private $CI;


    public function __construct($parameterId = null)
    {
        $this->CI =& get_instance();
        $this->setParameterId($parameterId);
    }

    public function setParameterId($pid = null)
    {
        $this->_parameterId = (int)$pid;
    }

    public function getParameterId()
    {
        return $this->_parameterId;
    }

    /**
    * @return array An array of ParamEQTerms
    */
    public function getEQTerms()
    {
        if ($this->_eqterms != null)
            return $this->_eqterms;

        //else
        $this->_eqterms = array();
        $this->CI->load->model('parameqtermmodel');
        foreach ($this->CI->parameqtermmodel->getEQTermsByParameter($this->_parameterId) AS $eq) {
            $this->_eqterms[] = new ParamEQTerm($eq[ParamEQTermModel::PRIMARY_KEY]);
        }
        return $this->_eqterms;
    }

    /**
    * @return array An array of ParamMPTerms
    */
    public function getMPTerms()
    {
        if ($this->_mpterms != null)
            return $this->_mpterms;

        //else
        $this->_mpterms = array();
        $this->CI->load->model('parammptermmodel');
        foreach ($this->CI->parammptermmodel->getMPTermsByParameter($this->_parameterId) AS $mp) {
            $this->_mpterms[] = new ParamMPTerm($mp[ParamMPTermModel::PRIMARY_KEY]);
        }
        return $this->_mpterms;
    }

    /**
    * @return bool
    */
    public function hasOntologies()
    {
        $this->getMPTerms();
        $this->getEQTerms();
        return ! (empty($this->_mpterms) && empty($this->_eqterms));
    }
}
