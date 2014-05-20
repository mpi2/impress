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
 * Fetches the pipelines from the database. Using PipelinesFetcher::getPipelines()
 * will return an array containing the Pipelines as objects ready for use
*/
class PipelinesFetcher
{
    /**
     * Returns all the visibile Pipelines in the db as an array of Pipeline objects
     * @param bool $displayAll Bypass should_display() check to return all items
     * @return Pipeline[] Pipeline object array
     */
    public static function getPipelines($displayAll = false)
    {
        $ci =& get_instance();
        $pipelines = array();
        foreach ($ci->pipelinemodel->fetchAll() as $pipeline) {
            $p = new Pipeline();
            $p->seed($pipeline);
            if ($displayAll || should_display($p)) {
                $pipelines[] = $p;
            }
        }
        return $pipelines;
    }

    /**
     * Returns all pipelines bypassing should_display()
     * @return Pipeline[] Pipeline object array
     */
    public static function fetchAll()
    {
        return static::getPipelines(true);
    }
}
