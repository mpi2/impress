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
 * Each Procedure has a type indicating it's general grouping of what type of
 * phenotyping experiment it is. A Procedure Type is represented using a TLA
 * such as XRY or BWT
 */
class ProcedureType
{
    /**
    * @var int id
    */
    private $_id;
    /**
    * @var string title of the type
    */
    private $_title;
    /**
    * @var string TLA
    */
    private $_key;
    /**
    * @deprecated
    * @var string num
    */
    private $_num;

    private $CI;


    /**
    * @param int $id
    */
    public function __construct($id = null)
    {
        $this->CI =& get_instance();
        $this->setId($id);
    }

    /**
    * @param int $id
    */
    public function setId($id = null)
    {
        if ($id != null) {
            $this->CI->load->model('proceduretypemodel');
            //fetch the row from the db by id
            $row = $this->CI->proceduretypemodel->getById($id);
            if ( ! empty($row)) {
                $this->_id    = $row['id'];
                $this->_title = $row['type'];
                $this->_key   = $row['key'];
                $this->_num   = $row['num'];
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
    * @return int id
    */
    public function getId()
    {
        return $this->_id;
    }

    /**
    * @return string title
    */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
    * @return string key
    */
    public function getKey()
    {
        return $this->_key;
    }

    /**
    * @deprecated use key instead
    * @return string num
    */
    public function getNum()
    {
        return $this->_num;
    }
}
