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
 * This model is used to override other settings.
 * The first port of loading up settings are those set in config files in the
 * ./application/config directory.
 * Next, some settings can be overriden using a custom settings file
 * in root ./impress.ini.
 * Finally, all these different settings can be overriden by a database-based
 * settings table for which this model was created. The settings set in the
 * database override everything else when the method
 * OverrideSettingsModel::updateRunningConfig() is run.
 * 
 * Order of settings execution/overriding
 * - config files
 * - impress.ini
 * - database config
 */

class OverrideSettingsModel extends CI_Model
{
    const TABLE = 'override_settings';
    const PRIMARY_KEY = 'key';
    
    public function fetchAll($returnRawValues = true)
    {
        $settings = $this->db->from(self::TABLE)
                             ->order_by(self::PRIMARY_KEY)
                             ->get()
                             ->result_array();
        if ( ! $returnRawValues) {
            $updatedSettings = array();
            foreach ($settings as $setting)
                $updatedSettings[] = $this->_prepareValue($setting);
            return $updatedSettings;
        }
        return $setting;
    }
    
    public function getByKey($key, $returnRawValue = true)
    {
        $setting = $this->db->from(self::TABLE)
                            ->where(self::PRIMARY_KEY, $key)
                            ->get()
                            ->row_array();
        if ( ! $returnRawValue)
            $setting = $this->_prepareValue($setting);
        return $setting;
    }
    
    private function _prepareValue(array $setting)
    {
        switch ($setting['type']) {
            case 'boolean':
                $setting['value'] = (bool)$setting['value'];
                break;
            case 'integer':
                $setting['value'] = (int)$setting['value'];
                break;
            case 'float':
                $setting['value'] = (float)$setting['value'];
                break;
            default:
                $setting['value'] = (string)$setting['value'];
        }
        return $setting;
    }
    
    public function insert(array $arr)
    {
        $this->load->helper('array_keys_exist');
        if ( ! array_keys_exist($arr, array('key', 'value', 'type')))
            return false;
        $this->db->insert(self::TABLE, $arr);
        return $this->db->insert_id();
    }
    
    public function updateValue($key, $value)
    {
        $ar = $this->db->where(self::PRIMARY_KEY, $key)
                       ->update(self::TABLE, array('value' => $value));
        if ($ar) {
            $this->updateRunningConfig();
        }
        return $this->db->affected_rows();
    }
    
    public function update($key, array $arr)
    {
        if ( ! array_key_exists('value', $arr) || ! array_key_exists('type', $arr))
            return false;
        $this->db->where(self::PRIMARY_KEY, $key)
                 ->update(self::TABLE, array('value' => $arr['value'], 'type' => $arr['type']));
        $ar = $this->db->affected_rows();
        if ($ar) {
            $this->updateRunningConfig();
        }
        return $ar;
    }
    
    public function updateRunningConfig()
    {
        $settings = $this->fetchAll(false);
        foreach ($settings as $setting) {
            $this->config->set_item($setting['key'], $setting['value']);
        }
    }

    public function delete($key)
    {
        $this->db->where(self::PRIMARY_KEY, $key)
                 ->delete(self::TABLE);
        return $this->db->affected_rows();
    }
}
