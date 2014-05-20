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
 * Measured parameters can have units
 */
class Unit
{
    /**
    * @var int $_id
    */
    protected $_id;
    /**
    * @var string $_unit
    */
    protected $_unit;

    private $CI;


    public function __construct($unitId = null)
    {
        $this->CI =& get_instance();
        $this->setId($unitId);
    }

    public function setId($unitId = null)
    {
        $this->CI->load->model('unitmodel');
        $row = $this->CI->unitmodel->getById($unitId);
        if ( ! empty($row)) {
            $this->_id = $row[UnitModel::PRIMARY_KEY];
            $this->_unit = $row['unit'];
        }
    }

    /**
    * @return bool
    */
    public function exists()
    {
        return ! is_null($this->_id);
    }

    /**
    * @return int|null
    */
    public function getId()
    {
        return $this->_id;
    }

    /**
    * @return string
    */
    public function getUnit()
    {
        return (string) $this->_unit;
    }
}
