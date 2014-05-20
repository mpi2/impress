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
 * This is an example model class used by the CI_Unit testing framewok and the phone_carrier table resides in the test database
 * @ignore
 */
class Phone_carrier_model extends CI_Model {
	
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
	
	function getCarriers(array $attributes)
	{
		foreach ($attributes as $field)
		{
			$this->db->select($field)->from('phone_carrier');
			$query = $this->db->get();
			foreach ($query->result_array() as $row)
			{
				$data[] = array($field, $row[$field]);
			}
		}
		
		return $data;
	}
	
}
