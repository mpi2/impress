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
* The ParamIncrement object is created when the Parameter::getIncrement() method
* is called but only when the Parameter actually has increments. If a Parameter
* hasIncrements() then an array of ParamIncrement objects is generated for it
*/
class ParamIncrement
{
    /**
    * @var int $id
    */
    private $_id;
    /**
    * @var int $parameterId
    */
    private $_parameterId;
    /**
    * @var int $weight number determines the order of items, heavier items go to the bottom, lighter items to top
    */
    private $_weight;
    /**
    * @var bool $isActive
    */
    private $_isActive;
    /**
    * @var string $incrementString
    */
    private $_incrementString;
    /**
    * @var string $incrementType
    */
    private $_incrementType;
    /**
    * @var string $incrementUnit
    */
    private $_incrementUnit;
    /**
    * @var int incrementMin the minimum number of readings required for a value to be accepted
    */
    private $_incrementMin;
    /**
    * @var bool deleted
    */
    private $_deleted;
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
    * @param int $paramincrementId
    */
    public function __construct($paramincrementId = null)
    {
        $this->CI =& get_instance();
        $this->setId($paramincrementId);
    }

    /**
    * @param int $incrementId
    */
    public function setId($paramincrementId = null)
    {
        if ($paramincrementId != null) {
            $this->CI->load->model('paramincrementmodel');
            //fetch the row from the db by id
            $row = $this->CI->paramincrementmodel->getById($paramincrementId);
            if ( ! empty($row)) {
                $this->_id = $row[ParamIncrementModel::PRIMARY_KEY];
                $this->_weight = $row['weight'];
                $this->_isActive = (bool) $row['is_active'];
                $this->_incrementString = $row['increment_string'];
                $this->_incrementType = $row['increment_type'];
                $this->_incrementUnit = $row['increment_unit'];
                $this->_incrementMin = $row['increment_min'];
                $this->_deleted = (bool) $row['deleted'];
                $this->_parameterId = $row['parameter_id'];
                $this->_userId = $row['user_id'];
                $this->_timeModified = $row['time_modified'];
            }
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
    * @return int
    */
    public function getId()
    {
        return $this->_id;
    }

    /**
    * @return int
    */
    public function getParameterId()
    {
        return $this->_parameterId;
    }

    /**
    * @return int
    */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
    * @return bool
    */
    public function isActive()
    {
        return $this->_isActive;
    }

    /**
    * @return string
    */
    public function getIncrementString()
    {
        return $this->_incrementString;
    }

    /**
    * @return string
    */
    public function getIncrementType()
    {
        return $this->_incrementType;
    }

    /**
    * @return string
    */
    public function getIncrementUnit()
    {
        return $this->_incrementUnit;
    }

    /**
    * @return int Minimum number of readings required for valid submission
    */
    public function getIncrementMin()
    {
        return $this->_incrementMin;
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
