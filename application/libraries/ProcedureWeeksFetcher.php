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
 * Fetches the Procedure Weeks as an array of ProcedureWeek Objects
 */
class ProcedureWeeksFetcher
{
    /**
    * Returns all the Procedure Weeks as an array of ProcedureWeek Objects
    * @return ProcedureWeek[] ProcedureWeek object array
    */
    public static function getProcedureWeeks()
    {
        $CI =& get_instance();
        $CI->load->model('procedureweekmodel');
        $weeks = array();
        foreach ($CI->procedureweekmodel->fetchAll() as $week) {
            $w = new ProcedureWeek();
            $w->seed($week);
            $weeks[] = $w;
        }
        return $weeks;
    }

    /**
     * @alias ProcedureWeeksFetcher::getProcedureWeeks()
     * @return ProcedureWeek[] ProcedureWeek object array
     */
    public static function fetchAll()
    {
        return static::getProcedureWeeks();
    }
}
