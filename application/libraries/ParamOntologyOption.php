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
 * If a Parameter has options then an array of ParamOntologyOption is created
 */
class ParamOntologyOption
{
    /**
    * @var int id
    */
    protected $_id;
    /**
    * @var bool Is default choice from other options in group
    */
    protected $_isDefault;
    /**
    * @var bool Is Active
    */
    protected $_isActive;
    /**
    * @var string ontology term
    */
    protected $_ontologyTerm;
    /**
    * @var string ontology id
    */
    protected $_ontologyId;
    /**
    * @var bool deleted
    */
    protected $_deleted;
    /**
    * @var bool is collapsed?
    */
    protected $_isCollapsed;
    /**
    * @var int weight
    */
    protected $_weight;
    /**
    * @var string
    */
    protected $_timeModified;
    /**
    * @var int
    */
    protected $_userId;

    private $CI;

    /**
     * @param int $paramOntologyOptionId
     */
    public function __construct($paramOntologyOptionId = null)
    {
        $this->setId($paramOntologyOptionId);
    }
    
    /**
     * @param int $id gets record from db to set class properties
     */
    public function setId($id)
    {
        if ($id != null) {
            $this->CI =& get_instance();
            $this->CI->load->model('paramontologyoptionmodel');
            $row = $this->CI->paramontologyoptionmodel->getById($id);
            $this->seed($row);
        }
    }
    
    /**
     * @param array $row Key-value data to set class properties
     */
    public function seed(array $row = array())
    {
        if ( ! empty($row)) {
            $this->_id = $row[ParamOntologyOptionModel::PRIMARY_KEY];
            $this->_isDefault = (bool) $row['is_default'];
            $this->_isActive = (bool) $row['is_active'];
            $this->_ontologyTerm = $row['ontology_term'];
            $this->_ontologyId = $row['ontology_id'];
            $this->_deleted = (bool) $row['deleted'];
            $this->_isCollapsed = (bool) $row['is_collapsed'];
            $this->_weight = (int)$row['weight'];
            $this->_userId = $row['user_id'];
            $this->_timeModified = $row['time_modified'];
        }
    }

    /**
    * @return bool
    */
    public function exists()
    {
        return ! empty($this->_id);
    }

    /**
    * @return int id
    */
    public function getId()
    {
        return $this->_id;
    }

    /**
    * @return bool isDefault
    */
    public function isDefault()
    {
        return $this->_isDefault;
    }

    /**
    * @return bool isActive
    */
    public function isActive()
    {
        return $this->_isActive;
    }

    /**
    * @return string
    */
    public function getOntologyTerm()
    {
        return $this->_ontologyTerm;
    }

    /**
    * @return string
    */
    public function getOntologyId()
    {
        return $this->_ontologyId;
    }

    /**
    * @return bool
    */
    public function isCollapsed()
    {
		return (bool) $this->_isCollapsed;
    }

    /**
    * @return bool deleted
    */
    public function isDeleted()
    {
        return (bool) $this->_deleted;
    }

    /**
    * @return int
    */
    public function getWeight()
    {
        return (int)$this->_weight;
    }
	
    /**
    * @return int
    */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
    * @return string DateTime::W3C
    */
    public function getTimeModified()
    {
        if ($this->_timeModified != null) {
            try {
                $d = new DateTime($this->_timeModified);
                return $d->format(DateTime::W3C);
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }
}
