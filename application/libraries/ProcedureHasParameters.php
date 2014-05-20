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
 * Fetches the parameters belonging to a procedure
 */
class ProcedureHasParameters
{
    /**
    * @param int $procedureId
    * @param bool $displayAll Bypass should_display() check to return all items 
    * @return Parameter[] Parameter objects array
    */
    public static function getParameters($procedureId, $displayAll = false)
    {
        $CI =& get_instance();
        $CI->load->model('procedurehasparametersmodel');
        $parameters = array();
        foreach ($CI->procedurehasparametersmodel->getByProcedure($procedureId) as $parameter) {
            $p = new Parameter();
            $p->seed($parameter);
            $p->setProcedureId($procedureId);
            if ($displayAll || should_display($p)) {
                $parameters[] = $p;
            }
        }
        return $parameters;
    }
    
    /**
     * Returns all Parameters for a given Procedure bypassing should_display()
     * @param int $procedureId
     * @return Parameter[] Parameter objects array
     */
    public static function fetchAll($procedureId)
    {
        return static::getParameters($procedureId, true);
    }
}
