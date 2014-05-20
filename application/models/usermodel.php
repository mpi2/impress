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
 * Drupal User model
 */
class UserModel extends CI_Model
{
    /**
    * @var string $_db the name of the drupal (mousephenotype.org) database
    */
    private $_db;

    public function __construct()
    {
        parent::__construct();
        $this->_db = $this->config->item('mousephenotypedb') . '.';
    }

    public function getByUserId($id)
    {
        return $this->db->from($this->_db . 'users')
                        ->where('uid', $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getByUsername($username)
    {
        return $this->db->from($this->_db . 'users')
                        ->where('name', $username)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getUserRoles($userId)
    {
        return $this->db->select('r.*')
                        ->from($this->_db . 'role AS r, ' . $this->_db . 'users_roles AS u')
                        ->where('u.rid', 'r.rid', FALSE)
                        ->where('u.uid', $userId)
                        ->get()
                        ->result_array();
    }

    // public function getUserPermissions($userId, $module = null)
    // {
        // $q = $this->db->select('rp.*')
                      // ->from($this->_db . 'role_permission AS rp, ' . $this->_db . 'role AS r, '
                      // . $this->_db . 'users_roles AS u')
                      // ->where('u.rid', 'r.rid', FALSE)
                      // ->where('rp.rid', 'u.rid', FALSE)
                      // ->where('u.uid', $userId);
        // if( ! empty($module))
            // $q = $q->where('module', $module);
        // return $q->get()->result_array();
    // }

    public function getSession($key, $val)
    {
        return $this->db->select('uid')
                        ->from($this->_db . 'sessions')
                        ->where('sid', $val)
                        ->or_where('ssid', $val)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

}
