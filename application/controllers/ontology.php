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
 * This controller class outputs the ontology documents of IMPReSS
 */
class ontology extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        header('Content-Type: text/xml');
    }
    
    /**
     * Output the IMPReSS OWL Ontology
     */
    public function impress()
    {
        $owlfile = $this->config->item('impressowlfilepath');
        
        //get the last time the database was updated
        $dbLastUpdated = null;
        $this->load->model('changelogmodel');
        try {
            $dbLastUpdated = new DateTime($this->changelogmodel->getLastEntryDate());
        }
        catch (Exception $e) {
            $dbLastUpdated = new DateTime();
        }
        
        //(re-)create the owl file
        if ( ! file_exists($owlfile) || filemtime($owlfile) < $dbLastUpdated->getTimestamp()) {
            $owljar = $this->config->item('impressowlexecutable');
            $firstArg = $owlfile;
            $secondArg = 'jdbc:' . $this->db->dbdriver . '://'
                       . $this->db->username . ':' . $this->db->password . '@'
                       . $this->db->hostname . ':' . ((empty($this->db->port)) ? 3306 : $this->db->port)
                       . '/' . $this->db->database;
            $output = null;
            $return_var = null;
            exec("java -jar $owljar $firstArg $secondArg", $output, $return_var);
            
            if ( ! empty($output)) {
                $error = '<?xml version="1.0"?' . '><error>'
                       . '<errorcode>' . $return_var . '</errorcode>'
                       . '<errormsg>An error occured while trying to run impressowl: ' . htmlentities($output) . '</errormsg>'
                       . '</error>';
                die($error);
            }
        }
        
        readfile($owlfile);
    }
    
    /**
     * Output the High-Throughput Phenotyping Ontology
     */
    public function htp()
    {
        readfile($this->config->item('htpontologyfilepath'));
    }
}
