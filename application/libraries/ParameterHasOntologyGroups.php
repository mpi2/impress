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
 * Fetch the ontology groups for a given Parameter
 */
class ParameterHasOntologyGroups
{
    /**
     * @param int $parameterId
     * @return OntologyGroup[] OntologyGroup objects array
     */
    public static function getGroups($parameterId)
    {
        $CI =& get_instance();
        $CI->load->model('parameterhasontologygroupsmodel');
        $groups = array();
        foreach ($CI->parameterhasontologygroupsmodel->getByParameter($parameterId) as $group) {
            $g = new OntologyGroup();
            $g->seed($group);
            $groups[] = $g;
        }
        return $groups;
    }
}
