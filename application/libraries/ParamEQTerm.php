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
 * Parameter EQTerm class
 */
class ParamEQTerm
{
    /**
    * @var int id
    */
    protected $_id;
    /**
    * @var string
    */
    protected $_entity1Term;
    /**
    * @var string
    */
    protected $_entity1Id;
    /**
    * @var string
    */
    protected $_entity2Term;
    /**
    * @var string
    */
    protected $_entity2Id;
    /**
    * @var string
    */
    protected $_entity3Term;
    /**
    * @var string
    */
    protected $_entity3Id;
    /**
    * @var string
    */
    protected $_quality1Term;
    /**
    * @var string
    */
    protected $_quality1Id;
    /**
    * @var string
    */
    protected $_quality2Term;
    /**
    * @var string
    */
    protected $_quality2Id;
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
            $this->CI->load->model('parameqtermmodel');
            //fetch the row from the db by id
            $row = $this->CI->parameqtermmodel->getById((int)$id);
            if ( ! empty($row)) {
                $this->_id = $row[ParamEQTermModel::PRIMARY_KEY];
                $this->_entity1Term = $row['entity1_term'];
                $this->_entity1Id = $row['entity1_id'];
                $this->_entity2Term = $row['entity2_term'];
                $this->_entity2Id = $row['entity2_id'];
                $this->_entity3Term = $row['entity3_term'];
                $this->_entity3Id = $row['entity3_id'];
                $this->_quality1Term = $row['quality1_term'];
                $this->_quality1Id = $row['quality1_id'];
                $this->_quality2Term = $row['quality2_term'];
                $this->_quality2Id = $row['quality2_id'];
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
    public function getEntity1Term()
    {
        return $this->_entity1Term;
    }

    /**
    * @return string entity id
    */
    public function getEntity1Id()
    {
        return $this->_entity1Id;
    }

    /**
    * @return string entity term
    */
    public function getEntity2Term()
    {
        return $this->_entity2Term;
    }

    /**
    * @return string entity id
    */
    public function getEntity2Id()
    {
        return $this->_entity2Id;
    }

    /**
    * @return string entity term
    */
    public function getEntity3Term()
    {
        return $this->_entity3Term;
    }

    /**
    * @return string entity id
    */
    public function getEntity3Id()
    {
        return $this->_entity3Id;
    }

    /**
    * @return string quality term
    */
    public function getQuality1Term()
    {
        return $this->_quality1Term;
    }

    /**
    * @return string quality id
    */
    public function getQuality1Id()
    {
        return $this->_quality1Id;
    }

    /**
    * @return string quality term
    */
    public function getQuality2Term()
    {
        return $this->_quality2Term;
    }

    /**
    * @return string quality id
    */
    public function getQuality2Id()
    {
        return $this->_quality2Id;
    }

    /**
    * @return array with keys entity(1|2|3)_(term|id), quality(1|2)_(term|id),
    */
    public function getEQs()
    {
        return array(
            'entity1_term'  => $this->_entity1Term,
            'entity1_id'    => $this->_entity1Id,
            'entity2_term'  => $this->_entity2Term,
            'entity2_id'    => $this->_entity2Id,
            'entity3_term'  => $this->_entity3Term,
            'entity3_id'    => $this->_entity3Id,
            'quality1_term' => $this->_quality1Term,
            'quality1_id'   => $this->_quality1Id,
            'quality2_term' => $this->_quality2Term,
            'quality2_id'   => $this->_quality2Id
        );
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
