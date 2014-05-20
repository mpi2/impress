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
 * Each Section belongs to a SectionTitle such as Purpose, Equipment, Notes, etc.
 */
class SectionTitle
{
    /**
    * @var int id
    */
    private $_id;
    /**
    * @var string title
    */
    private $_title;
    /**
    * @var int centreId
    */
    private $_centreId;
    /**
    * @var int weight
    */
    private $_weight;

    private $CI;


    /**
    * @param int $sectionTitleId
    */
    public function __construct($sectionTitleId = null)
    {
        $this->CI =& get_instance();
        $this->setId($sectionTitleId);
    }

    /**
    * @param int $sectionTitleId
    */
    public function setId($sectionTitleId = null)
    {
        if ($sectionTitleId != null) {
            $this->CI->load->model('sectiontitlemodel');
            //fetch the row from the db by id
            $row = $this->CI->sectiontitlemodel->getById($sectionTitleId);
            if ( ! empty($row)) {
                $this->_id = $row['id'];
                $this->_title = $row['title'];
                $this->_weight = $row['weight'];
                $this->_centreId = $row['centre_id'];
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
    * @return int weight
    */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
    * @return int centreId
    */
    public function getCentreId()
    {
        return $this->_centreId;
    }
}
