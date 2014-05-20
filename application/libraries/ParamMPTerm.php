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
 * Parameter MPTerm class
 *
 * @see ParamOntology
 */
class ParamMPTerm
{
    /**
    * @var int id
    */
    protected $_id;
    /**
    * @var string
    */
    protected $_mpTerm;
    /**
    * @var string
    */
    protected $_mpId;
    /**
    * @var int
    */
    protected $_weight;
    /**
    * @var bool
    */
    protected $_deleted;
    /**
    * @var int
    */
    protected $_parameterId;
    /**
    * @var ParamOption
    */
    protected $_option;
    /**
    * @var ParamIncrement
    */
    protected $_increment;
    /**
    * @var string
    */
    protected $_sex;
    /**
    * @var string
    */
    protected $_selectionOutcome;
	/**
    * @var string
    */
    protected $_timeModified;
    /**
    * @var int
    */
    protected $_userId;

    private $CI;

    public function __construct($id = null)
    {
        $this->CI =& get_instance();
        $this->setId($id);
    }

    public function setId($id = null)
    {
        if ($id != null) {
            $this->CI->load->model('parammptermmodel');
            //fetch the row from the db by id
            $row = $this->CI->parammptermmodel->getById((int)$id);
            if ( ! empty($row)) {
                $this->_id = $row[ParamMPTermModel::PRIMARY_KEY];
                $this->_mpTerm = $row['mp_term'];
                $this->_mpId = $row['mp_id'];
                $this->_weight = $row['weight'];
                $this->_deleted = (bool) $row['deleted'];
                $this->_parameterId = $row['parameter_id'];
                $this->_option = new ParamOption($row['option_id']);
                $this->_increment = new ParamIncrement($row['increment_id']);
                $this->_sex = $row['sex'];
                $this->_selectionOutcome = $row['selection_outcome'];
                $this->_userId = $row['user_id'];
                $this->_timeModified = $row['time_modified'];
            }
        }
    }

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
    * @return string entity term
    */
    public function getMPTerm()
    {
        return $this->_mpTerm;
    }

    /**
    * @return string entity id
    */
    public function getMPId()
    {
        return $this->_mpId;
    }

    /**
    * @return int sequence of appearance if displayed in a list
    */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
    * @return bool deleted
    */
    public function isDeleted()
    {
        return $this->_deleted;
    }

    /**
    * @return int
    */
    public function getParameterId()
    {
        return $this->_parameterId;
    }

    /**
    * @return ParamOption
    */
    public function getOption()
    {
        return $this->_option;
    }

    /**
    * @return ParamIncrement
    */
    public function getIncrement()
    {
        return $this->_increment;
    }

    /**
    * @return string
    */
    public function getSex()
    {
        return $this->_sex;
    }

    /**
    * @return string
    */
    public function getSelectionOutcome()
    {
        return $this->_selectionOutcome;
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
