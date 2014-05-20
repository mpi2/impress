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
 * When provided with an MP, EQ or Trait Term ID it links it up to the correct website
 * 
 * @todo Maybe in the future include some length validation of the term ids, e.g. MP term ids are 10 chars long
 */
class TermLinker
{
    /**
     * Takes an Ontology Id and return the URL to the external website that lists
     * it. The supported ontologies currently are:
     * 
     * <ul>
     *  <li>BSPO</li>
     *  <li>CHEBI</li>
     *  <li>CL</li>
     *  <li>EMAP</li>
     *  <li>ENVO</li>
     *  <li>GO</li>
     *  <li>IMR</li>
     *  <li>MA</li>
     *  <li>MP</li>
     *  <li>MPATH</li>
     *  <li>PATO</li>
     * </ul>
     * 
     * If an ontology is unsupported the hyperlink will display an "Unrecognised
     * term" message in a js popup
     * 
     * @param string $id Ontology key like MP:0001240
     * @return string HTML hyperlink
     */
    public static function linkId($id = null)
    {
        $id = trim(strtoupper($id));
        if(empty($id))
            return '';

        //KEY is something like PATO, MP, etc
        $key = substr($id, 0, strpos($id, ':'));

        $terms = array(
            'MP' => array('url' => 'http://www.informatics.jax.org/searches/Phat.cgi?id=%s', 'class' => 'mpid'),
            'BSPO' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'eqbspo'),
            'CHEBI' => array('url' => 'http://www.ebi.ac.uk/chebi/searchId.do?chebiId=%s', 'class' => 'eqchebi'),
            'CL' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'eqcl'),
            'ENVO' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'eqenvo'),
            'GO' => array('url' => 'http://amigo.geneontology.org/cgi-bin/amigo/term_details?term=%s', 'class' => 'eqgo'),
            'IMR' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'eqimr'),
            'MA' => array('url' => 'http://www.informatics.jax.org/searches/AMA.cgi?id=%s', 'class' => 'eqma'),
            'PATO' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'eqpato'),
            'EMAP' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'mpid emap'),
            'MPATH' => array('url' => 'http://www.ebi.ac.uk/ontology-lookup/?termId=%s', 'class' => 'mpid mpath')
        );

        //check the key is recognised and return empty url if not
        if( ! array_key_exists($key, $terms))
            return '<a class="external" href="javascript:alert(\'Unrecognised term id schema supplied\')">' . $id . '</a>';

        //return valid url
        $arr = $terms[$key];
        return '<a href="' . sprintf($arr['url'], $id) . '" class="' . $arr['class'] . ' external" target="_blank">' . $id . '</a>';
    }
}
