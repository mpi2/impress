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
 * A person found in the drupal database
 */
class Person
{
    /**
    * @var array details - includes everything like id, name, email, etc
    */
    private $_details = array();

    private $CI;


    public function __construct($userId = null)
    {
        $this->CI =& get_instance();
        $this->setId($userId);
    }

    public function setId($userId)
    {
        if ($userId != null) {
            if (is_numeric($userId)) $row = $this->CI->usermodel->getByUserId($userId);
            else $row = $this->CI->usermodel->getByUsername($userId);
            if ( ! empty($row)) $this->_details = $row;
        }
    }

    public function exists()
    {
        $id = $this->getId();
        return ! empty($id);
    }

    /**
    * @return int|null id
    */
    public function getId()
    {
        return (array_key_exists('uid', $this->_details)) ? $this->_details['uid'] : null;
    }

    /**
    * @return string|null name
    */
    public function getName()
    {
        return (array_key_exists('name', $this->_details)) ? $this->_details['name'] : null;
    }

    /**
    * @return string|null email address
    */
    public function getEmail()
    {
        return (array_key_exists('mail', $this->_details)) ? $this->_details['mail'] : null;
    }

    /**
    * @return array
    */
    public function getDetailsArray()
    {
        return $this->_details;
    }

}
