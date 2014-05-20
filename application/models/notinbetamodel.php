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
 * The purpose of this class is to keep a track of what items have been pushed
 * to the Beta Server. If an item has a record in the in_beta table, then that
 * item or some of its child-items may NOT be edited on the Internal Server
 * without requiring the creation of a new Version of that item or its parent.
 * 
 * The contents of this table are populated with the keys of the newly created
 * keys of 3P items when they are created. Once the database has been pushed to
 * the Beta server, this table would be truncated, ready to start the cycle of
 * creation again.
 * 
 * The release structure of IMPReSS works in this way:
 * - Item created on Internal Server. It's key is added to the not_in_beta table automatically
 * - When editing of everything has completed, the database is pushed on the Beta server
 * - The not_in_beta table is truncated on Internal and optionally on Beta
 * 
 * This class was created to manage items being edited between versions being
 * edited on Internal and after they are released to Beta; If I have released
 * something to Beta already then any changes I make to it on Internal should
 * make IMPReSS warn me that this item has been released and ask me if I want to
 * create a new version. If something has yet to be released to Beta then it
 * should allow me to update the item unfettered.
 */
class NotInBetaModel extends CI_Model
{
    const TABLE = 'not_in_beta';
    const PRIMARY_KEY = 'id';
    
    /**
     * @return array
     */
    public function fetchAll()
    {
        return $this->db->get(self::TABLE)->result_array();
    }
    
    /**
     * @param int $id
     * @return array
     */
    public function getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }
    
    /**
     * @param string $key
     * @return array
     */
    public function getByKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('key', trim($key))
                        ->limit(1)
                        ->get()
                        ->row_array();
    }
    
    /**
     * @param string $key
     * @return bool
     */
    public function keyIsInBeta($key)
    {
        $key = $this->getByKey($key);
        return empty($key);
    }
    
    /**
     * @return array Keys
     */
    public function keysNotInBeta()
    {
        return array_map(function($r){return $r['key'];}, $this->fetchAll());
    }
    
    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return (bool) $this->db->where(self::PRIMARY_KEY, $id)->delete(self::TABLE);
    }
    
    /**
     * @param string $key
     * @return bool
     */
    public function deleteByKey($key)
    {
        return (bool) $this->db->where('key', trim($key))->delete(self::TABLE);
    }
    
    /**
     * @param string|mixed $key Key string or a hash where one of parameter_key,
     * procedure_key or pipeline_key value is extracted by its key
     * @return int Last insert id
     */
    public function insert($key)
    {
        if (is_array($key)) {
            if (isset($key['parameter_key']))
                $key = $key['parameter_key'];
            else if (isset($key['procedure_key']))
                $key = $key['procedure_key'];
            else if (isset($key['pipeline_key']))
                $key = $key['pipeline_key'];
            else
                $key = current(array_values($key));
        }
        $this->db->insert(self::TABLE, array('key' => trim((string)$key)));
        return $this->db->insert_id();
    }
}
