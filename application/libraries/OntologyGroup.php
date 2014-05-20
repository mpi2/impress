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
 * An Ontology Group has a unique name, belongs to one or more parameters and
 * contains ontology options
 */
class OntologyGroup
{
    /**
    * @var int id
    */
    protected $_id;
    /**
    * @var ParamOntologyOption[] Ontology Options in this group
    */
    protected $_ontologyOptions = array();
    /**
    * @var string name of group
    */
    protected $_name;
    /**
    * @var bool
    */
    protected $_isActive;
    /**
    * @var int
    */
    protected $_userId;
    /**
    * @var bool
    */
    protected $_deleted;
    /**
    * @var string
    */
    protected $_timeModified;

    private $CI;


    public function __construct($groupId = null)
    {
        $this->setId($groupId);
    }

    public function setId($groupId = null)
    {
        $this->CI =& get_instance();
        $this->CI->load->model('ontologygroupmodel');
        if ($groupId != null) {
            $row = $this->CI->ontologygroupmodel->getById($groupId);
            $this->seed($row);
        }
    }
    
    /**
     * @param array $row Key-value pairs of fields
     */
    public function seed(array $row = array())
    {
        if ( ! empty($row)) {
            $this->_id       = $row[OntologyGroupModel::PRIMARY_KEY];
            $this->_name     = $row['name'];
            $this->_isActive = (bool)$row['is_active'];
            $this->_userId   = $row['user_id'];
            $this->_deleted  = (bool)$row['deleted'];
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
    * @return string name
    */
    public function getName()
    {
        return $this->_name;
    }

    /**
    * @return ParamOntologyOption[] Array of Ontology Option objects contained in this group
    */
    public function getOntologyOptions()
    {
//        if (empty($this->_ontologyOptions)) {
//            $this->CI->load->model('paramontologyoptionmodel');
//            foreach($this->CI->paramontologyoptionmodel->getByOntologyGroup($this->_id) as $o) {
//                $this->_ontologyOptions[] = new ParamOntologyOption($o[ParamOntologyOptionModel::PRIMARY_KEY]);
//            }
//        }
//        return $this->_ontologyOptions;
        if (empty($this->_ontologyOptions)) {
            $this->_ontologyOptions = ParamOntologyOptionsFetcher::getOptions($this->getId());
        }
        return $this->_ontologyOptions;
    }

    /**
    * @return int
    */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
    * @return bool
    */
    public function isDeleted()
    {
        return (bool)$this->_deleted;
    }

    /**
    * @return bool
    */
    public function isActive()
    {
        return (bool)$this->_isActive;
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
