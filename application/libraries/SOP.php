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
 * A SOP (Protocol) document contains Sections
 */
class SOP
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
    * @var int centre_id
    */
    private $_centreId;
    /**
    * @var array Section array
    */
    private $_sections = array();
    /**
    * @var int majorVersion
    */
    private $_majorVersion;
    /**
    * @var int minorVersion
    */
    private $_minorVersion;
    /**
    * @var int timeModified
    */
    private $_timeModified;
    /**
    * @var int userId
    */
    private $_userId;
    /**
    * @var int weight affects display order of items
    */
    private $_weight;
    /**
    * @var bool deleted
    */
    private $_deleted;
    /**
    * @var null|Procedure
    */
    private $_procedure;

    private $CI;

    /**
    * @param int $sopId
    * @param Procedure|int $procedure
    */
    public function __construct($sopId = null, $procedure = null)
    {
        $this->CI =& get_instance();
        if ($sopId != null) {
            $this->CI->load->model('sopmodel');
            //fetch the row from the db by id
            $row = $this->CI->sopmodel->getById($sopId);
            if ( ! empty($row)) {
                $this->_id = $row['sop_id'];
                $this->_title = $row['title'];
                $this->_centreId = $row['centre_id'];
                $this->_weight = $row['weight'];
                $this->_majorVersion = $row['major_version'];
                $this->_minorVersion = $row['minor_version'];
                $this->_timeModified = $row['time_modified'];
                $this->_userId = $row['user_id'];
                $this->_deleted = (bool) $row['deleted'];
                $this->_procedure = (isset($procedure)) ? $procedure : $row[ProcedureModel::PRIMARY_KEY];
            }
        }
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
    * @return int centre id
    */
    public function getCentreId()
    {
        return $this->_centreId;
    }

    /**
    * @return array Section object array
    */
    public function getSections()
    {
        if (empty($this->_sections)) {
            $this->CI->load->model('sectionmodel');
            foreach ($this->CI->sectionmodel->getSectionsBySOP($this->getId()) as $sec) {
                $this->_sections[] = new Section($sec[SectionModel::PRIMARY_KEY]);
            }
        }
        return $this->_sections;
    }

    /**
    * @return int major version
    */
    public function getMajorVersion()
    {
        return $this->_majorVersion;
    }

    /**
    * @return int minorVersion
    */
    public function getMinorVersion()
    {
        return $this->_minorVersion;
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

    /**
    * @return int user id
    */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
    * @return int weight The order to display the SOPs
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
    * @return Procedure
    */
    public function getProcedure()
    {
        if ( ! ($this->_procedure instanceof Procedure))
            $this->_procedure = new Procedure($this->_procedure);
        return $this->_procedure;
    }

    /**
    * @return bool
    */
    public function exists()
    {
        return ! empty($this->_id);
    }

    /**
    * @return bool
    */
    public function deletePDF()
    {
        return $this->CI->sopmodel->deletePDF($this->getId());
    }
}
