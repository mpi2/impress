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
 * Pipeline Has Procedures - Get procedures for a given Pipeline
 */
class PipelineHasProcedures
{
    /**
    * @param int $pipelineId
    * @param bool $displayAll Bypass should_display() check to return all items
    * @return Procedure[] Procedure objects array
    */
    public static function getProcedures($pipelineId, $displayAll = false)
    {
        $CI =& get_instance();
        $CI->load->model('pipelinehasproceduresmodel');
        $procedures = array();
        foreach ($CI->pipelinehasproceduresmodel->getByPipeline($pipelineId) as $procedure) {
            $p = new Procedure();
            $p->seed($procedure);
            $p->setPipelineId($pipelineId);
            if ($displayAll || should_display($p)) {
                $procedures[] = $p;
            }
        }
        return $procedures;
    }
    
    /**
     * Returns all procedures for a given parameter bypassing should_display()
     * @param int $pipelineId
     * @return Procedure[] Procedure objects array
     */
    public static function fetchAll($pipelineId)
    {
        return static::getProcedures($pipelineId, true);
    }
}
