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
 * If a Parameter has options then an array of ParamOption is created
 */
class ParamOption
{
    /**
    * @var int id
    */
    protected $_id;
    /**
     * @var ParamOption $_parent
     */
    protected $_parent;
    /**
    * @var string name
    */
    protected $_name;
    /**
    * @var bool Is default choice from other options in group
    */
    protected $_isDefault;
    /**
    * @var bool Is Active
    */
    protected $_isActive;
    /**
    * @var string description
    */
    protected $_description;
    /**
    * @var bool deleted
    */
    protected $_deleted;
    /**
    * @var int parameterId
    */
    protected $_parameterId;
    /**
    * @var string
    */
    protected $_timeModified;
    /**
    * @var int
    */
    protected $_userId;

    private $CI;


    public function __construct($paramoptionId = null, $parameterId = null)
    {
        if ($paramoptionId != null) {
            $this->CI =& get_instance();
            $this->CI->load->model('paramoptionmodel');
            if ($parameterId) {
                $this->_parameterId = $parameterId;
            }
            //fetch the row from the db by id
            $row = $this->CI->paramoptionmodel->getById($paramoptionId);
            $this->seed($row);
        }
    }
    
    /**
     * @param array $row Key-Value pairs of options fields
     */
    public function seed(array $row = array())
    {
        if ( ! empty($row)) {
            $this->_id = $row['param_option_id'];
            $this->_name = $row['name'];
            $this->_isDefault = (bool) $row['is_default'];
            $this->_isActive = (bool) $row['is_active'];
            $this->_description = $row['description'];
            $this->_deleted = (bool) $row['deleted'];
            $this->_parent = ($this->_id == $row['parent_id'] || empty($row['parent_id'])) ? $this : new ParamOption($row['parent_id']);
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
    * @return string name
    */
    public function getName()
    {
        return $this->_name;
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
    * @return string description
    */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
    * @return int parameter id
    */
    public function getParameterId()
    {
        return $this->_parameterId;
    }
    
    /**
     * @param int $parameterId
     */
    public function setParameterId($parameterId)
    {
        $this->_parameterId = $parameterId;
    }

    /**
    * @return bool deleted
    */
    public function isDeleted()
    {
        return $this->_deleted;
    }

    /**
    * @return ParamOption parent
    */
    public function getParent()
    {
        return $this->_parent;
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
