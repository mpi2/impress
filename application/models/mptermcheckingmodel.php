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
 * This class scans the MP OBO file and allows checking the terms
 */
class MPTermCheckingModel extends CI_Model
{
    /**
     * @var array $_allMPs array with keys 'id', 'term' and 'is_obsolete'
     */
    protected static $_allMPs = array();
    protected $_mpFile;

    public function __construct()
    {
        parent::__construct();
        $mpFile = $this->config->item('cache_path') . DIRECTORY_SEPARATOR . $this->config->item('mpfile');
        $remoteFile = $this->config->item('mpurl');
        $refreshInterval = time() - $this->config->item('mpfilerefreshinterval');

        if ( ! file_exists($mpFile) || filemtime($mpFile) < $refreshInterval) {
            $rfh = fopen($remoteFile, 'r');
            if ($rfh) {
                $lfh = file_put_contents($mpFile, $rfh, LOCK_EX);
                if ( ! $lfh) {
                    log_message('warn', 'Unable to save the latest MP file to ' . $mpFile);
                }
                fclose($rfh);
            } else {
                $message = 'Unable to download remote MP file';
                log_message('error', $message);
                throw new Exception($message);
            }
        }
        
        $this->_mpFile = $mpFile;
    }
    
    /**
     * @param array|string $ontologyIds
     * @return array Obsolete Ids
     */
    public function checkIdsObsolete($ontologyIds = array())
    {
        $obsoleteIds = array();
        
        $allObsoleteIds = $this->_getObsoleteMPIds();
        
        foreach ((array)$ontologyIds as $id) {
            if (in_array(strtoupper($id), $allObsoleteIds)) {
                $obsoleteIds[] = $id;
            }
        }
        
        return $obsoleteIds;
    }
    
    /**
     * Warning: This is a slow process
     * @return array Ids not present in the MP Ontology Database
     */
    public function checkInvalidIds()
    {
        $invalidIds = array();
        foreach ($this->_getAllParameterOntologyIds(false) as $id) {
            if (false === $this->checkValidId($id)) {
                $invalidIds[] = $id;
            }
        }
        return $invalidIds;
    }
    
    /**
     * @return array [[id=>1, term=>bla],...]
     */
    public function findUpdatedTerms()
    {
        $updatedIds = array();
        foreach ($this->_getAllParameterOntologyIds(true) as $myTerm) {
            $officialTerm = $this->getOntologyTerm($myTerm['id']);
            if (empty($officialTerm)) {
                continue;
            }
            if ($officialTerm['term'] != $myTerm['term']) {
                $updatedIds[] = $officialTerm;
            }
        }
        return $updatedIds;
    }

    /**
     * @return array Obsolete Ids in IMPReSS DB
     */
    public function checkAnyMPIdsObsolete()
    {
        $impressObsoleteMPs = array();
        
        $allObsoleteMPs = $this->_getObsoleteMPIds();
        
        foreach ($this->_getAllParameterOntologyIds() as $id) {
            if (in_array(strtoupper($id), $allObsoleteMPs)) {
                $impressObsoleteMPs[] = $id;
            }
        }
        
        return $impressObsoleteMPs;
    }

    /**
     * @param bool $includeTerms return values as array of hashes with keys 'id' and 'term'?
     * Setting this to false just returns a flat array of ontology ids
     * @return array An array of all the ids in the database
     */
    protected function _getAllParameterOntologyIds($includeTerms = false)
    {
        $q = "SELECT id, term FROM (
                  SELECT mp_id AS id, mp_term AS term FROM param_mpterm
                  UNION ALL
                  SELECT entity1_id AS id, entity1_term AS term FROM param_eqterm
                  UNION ALL
                  SELECT entity2_id AS id, entity2_term AS term FROM param_eqterm
                  UNION ALL
                  SELECT entity3_id AS id, entity3_term AS term FROM param_eqterm
                  UNION ALL
                  SELECT quality1_id AS id, quality1_term AS term FROM param_eqterm
                  UNION ALL
                  SELECT quality2_id AS id, quality2_term AS term FROM param_eqterm
                  UNION ALL
                  SELECT ontology_id AS id, ontology_term AS term FROM param_ontologyoption
              ) AS allids
              WHERE allids.id IS NOT NULL AND allids.id != '' AND allids.id LIKE 'MP:%'
              GROUP BY allids.id ORDER BY allids.id";
        $result = $this->db->query($q)->result_array();
        return ($includeTerms) ? $result : array_map(function($v){return $v['id'];}, $result);
    }
    
    /**
     * @param string $id
     * @return bool
     */
    public function checkValidId($id)
    {
        return (bool) $this->getOntologyTerm($id);
    }

    /**
     * @param string|array $id
     * @return array|bool Array with keys 'id', 'term' and 'is_obsolete' or false if not found
     */
    public function getOntologyTerm($id)
    {
        if (is_array($id)) {
            $result = array();
            foreach ($id as $i) {
                $result[] = $this->getOntologyTerm($i);
            }
            return $result;
        }
        
        foreach($this->_getAllMPTerms() as $mp) {
            if ($mp['id'] == $id) {
                return $mp;
            }
        }
        return false;
    }
    
    /**
     * @return array [[id=>1, term=>bla, is_obsolete=>true],...]
     */
    protected function _getAllMPTerms()
    {
        if (empty(static::$_allMPs)) {
            $handle = fopen($this->_mpFile, 'r');
            $lineReader = new PhpObo\LineReader($handle);
            $parser = new PhpObo\Parser($lineReader);
            $parser->retainTrailingComments(true);
            $parser->getDocument()->mergeStanzas(false);
            $parser->parse();
            foreach ($parser->getDocument()->getStanzas('Term') as $term) {
                static::$_allMPs[] = array(
                    'id' => $term['id'],
                    'term' => $term['name'],
                    'is_obsolete' => (isset($term['is_obsolete']) && $term['is_obsolete'] == 'true')
                );
            }
        }
        return static::$_allMPs;
    }

    /**
     * @return array
     */
    protected function _getObsoleteMPIds()
    {
        $obsoleteMPs = array_filter($this->_getAllMPTerms(), function($a){return $a['is_obsolete'];});
        return array_map(function($a){return $a['id'];}, $obsoleteMPs);
    }
}
