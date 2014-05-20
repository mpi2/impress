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
 * Fetches the OntologyGroups from the database. Using OntologyGroupsFetcher::fetchAll()
 * will return an array containing the Ontology Groups as objects ready for use.
 * 
 * @see ParameterHasOntologyGroups::getGroups() for getting ontology groups of
 * individual parameters
 */
class OntologyGroupsFetcher
{
    /**
    * Returns all the Ontology Groups in the db as an array of OntologyGroup objects
    * @return OntologyGroup[] Ontology Group object array
    */
    public static function getGroups()
    {
        $ci =& get_instance();
        $ci->load->model('ontologygroupmodel');
        $groups = array();
        foreach ($ci->ontologygroupmodel->fetchAll() as $group) {
            $g = new OntologyGroup();
            $g->seed($group);
            $groups[] = $g;
        }
        return $groups;
    }

    /**
     * @alias OntologyGroupsFetcher::getGroups()
     * @return OntologyGroup[] Ontology Group object array
     */
    public static function fetchAll()
    {
        return static::getGroups();
    }
}
