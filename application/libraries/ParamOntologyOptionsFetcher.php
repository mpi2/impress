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
 * Fetches the OntologyOptions for an ontology group. Using ParamOntologyOptionsFetcher::fetchOptions()
 * will return an array containing the Ontology Options as objects ready for use
 */
class ParamOntologyOptionsFetcher
{
    /**
     * Returns all the Ontology Options for a given group id
     * @param int $groupId
     * @return ParamOntologyOption[] object array
     */
    public static function getOptions($groupId)
    {
        $ci =& get_instance();
        $ci->load->model('paramontologyoptionmodel');
        $options = array();
        foreach ($ci->paramontologyoptionmodel->getByOntologyGroup($groupId) as $option) {
            $o = new ParamOntologyOption();
            $o->seed($option);
            $options[] = $o;
        }
        return $options;
    }
}
