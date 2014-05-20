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
 * There are many Sections in a SOP
 */
class Section
{
    /**
    * @var int id
    */
    private $_id;
    /**
    * @var null|SectionTitle section - a Section will have one SectionTitle associated with
    * it which will hold things like the title and display order
    */
    private $_sectionTitle;
    /**
    * @var string sectionText will hold the actual text of the section
    */
    private $_sectionText;
    /**
    * @var int weight Sets the display order of Section records that share sectionTitles
    */
    private $_weight;
    /**
    * @var int level The hierarchical level at which this subsection will be
    * displayed
    */
    private $_level;
    /**
    * @var string levelText title for the new level
    */
    private $_levelText;
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
    * @var bool deleted
    */
    private $_deleted;
    /**
    * @var int sopId
    */
    private $_sopId;

    private $CI;


    /**
    * @param int $subsectionId
    */
    public function __construct($sectionId = null)
    {
        if ($sectionId != null) {
            $this->CI =& get_instance();
            $this->CI->load->model('sectionmodel');
            //fetch the row from the db by id
            $row = $this->CI->sectionmodel->getById($sectionId);
            if ( ! empty($row)) {
                $this->_id = $row['section_id'];
                $this->_sectionTitle = new SectionTitle($row['section_title_id']);
                $this->_sectionText = $row['section_text'];
                $this->_weight = $row['weight'];
                $this->_level = $row['level'];
                $this->_levelText = $row['level_text'];
                $this->_majorVersion = $row['major_version'];
                $this->_minorVersion = $row['minor_version'];
                $this->_timeModified = $row['time_modified'];
                $this->_userId = $row['user_id'];
                $this->_deleted = (bool) $row['deleted'];
                $this->_sopId = $row['sop_id'];
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
    * @return SectionTitle SectionTitle object contains title and weight
    */
    public function getSectionTitle()
    {
        if($this->_sectionTitle instanceof SectionTitle)
            return $this->_sectionTitle;
        return new SectionTitle();
    }

    /**
    * @return string sectionText
    */
    public function getSectionText()
    {
        return $this->_sectionText;
    }

    /**
    * @return int
    * instead
    */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
    * @return int level The level at which this item is displayed
    */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
    * @return string levelText - probably will never be used
    */
    public function getLevelText()
    {
        return $this->_levelText;
    }

    /**
    * @return int majorVersion
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
    * @return int userId
    */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
    * @return bool deleted
    */
    public function isDeleted()
    {
        return $this->_deleted;
    }

    /**
    * @return int sopId - The Parent of this section
    */
    public function getSopId()
    {
        return $this->_sopId;
    }
}
