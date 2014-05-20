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
 * Procedure Week is also known as Procedure Stage. It is the period on which a
 * procedure is performed e.g. Week 9, E15.5, Unrestricted
 */
class ProcedureWeek
{
    /**
    * @var int id
    */
    private $_id;
    /**
    * @var string label
    */
    private $_label;
    /**
    * @var int week number
    */
    private $_num;
    /**
     * @var int weight
     */
    private $_weight;
    /**
     * @var string stage
     */
    private $_stage;

    private $CI;


    /**
    * @param int $weekId Procedure Week Id
    */
    public function __construct($weekId = null)
    {
        if ( ! is_null($weekId)) {
            $this->CI =& get_instance();
            $this->CI->load->model('procedureweekmodel');
            //fetch the row from the db by id
            $row = $this->CI->procedureweekmodel->getById($weekId);
            $this->seed($row);
        }
    }
    
    /**
     * @param array $row Key-value pairs for class properties
     */
    public function seed(array $row = array())
    {
        if ( ! empty($row)) {
            $this->_id     = $row['id'];
            $this->_label  = $row['label'];
            $this->_num    = $row['num'];
            $this->_stage  = $row['stage'];
            $this->_weight = $row['weight'];
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
    * @return string label
    */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
    * @return mixed Week Number
    */
    public function getWeekNumber()
    {
        return $this->_num;
    }
    
    /**
     * @return int
     */
    public function getWeight()
    {
        return (int)$this->_weight;
    }
    
    /**
     * @return string
     * @see EProcedureWeekStage
     */
    public function getStage()
    {
        return $this->_stage;
    }
    
    /**
     * @return string e.g. Adult
     */
    public function getStageLabel()
    {
        $labels = EProcedureWeekStage::getLabels();
        return (isset($labels[$this->getStage()])) ? $labels[$this->getStage()] : '';
    }
}
