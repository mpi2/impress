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

class ChangeLogger
{
    const ACTION_CREATE   = 'CREATED';
    const ACTION_UPDATE   = 'UPDATED';
    const ACTION_DELETE   = 'DELETED';
    const ACTION_VERSION  = 'VERSIONED';
    const ACTION_REVERT   = 'REVERTED';
    const ACTION_REORDER  = 'REORDER';
    const ACTION_IMPORT   = 'IMPORTED';
    const ACTION_UNDELETE = 'UNDELETED';
    const ACTION_CLONE    = 'CLONED';
    const ACTION_RELEASE  = 'RELEASED';
    const FIELD_PIPELINE  = 'pipeline_id';
    const FIELD_ACTION    = 'action_type';
    const FIELD_ITEM_TYPE = 'item_type';
    const FIELD_ITEM_ID   = 'item_id';
    const FIELD_ITEM_KEY  = 'item_key';
    const FIELD_PROCEDURE = 'procedure_id';
    const FIELD_PARAMETER = 'parameter_id';
    const FIELD_MESSAGE   = 'message';
    const FIELD_INTERNAL  = 'internal';
    protected static $_ci;

    /**
    * @param array $log Mandatory array keys are pipeline_id, item_type,
    * action_type and item_id. Optional keys: procedure_id, parameter_id,
    * message, item_key
	* @return bool
    */
    public static function log(array $log)
    {
        if (static::$_ci == null)
            static::$_ci =& get_instance();
		
        //if change_logging setting is switched off then don't log anything
        if(static::$_ci->config->item('change_logging') === false)
            return true;

        static::$_ci->load->model('changelogmodel');

        //check the $log array has the mandatory fields and validate/sanitize it
        static::$_ci->load->helper('array_keys_exist');
        if( ! array_keys_exist($log, array(self::FIELD_PIPELINE, self::FIELD_ACTION, self::FIELD_ITEM_TYPE, self::FIELD_ITEM_ID)))
            return false;
        if( ! in_array($log[self::FIELD_ACTION], static::getActions()))
            return false;
        static::$_ci->load->helper('keep_array_keys');
        $log = keep_array_keys(
            $log,
            array(
                self::FIELD_PIPELINE,  self::FIELD_ACTION,
                self::FIELD_ITEM_TYPE, self::FIELD_ITEM_ID,
                self::FIELD_ITEM_KEY,  self::FIELD_PROCEDURE,
                self::FIELD_PARAMETER, self::FIELD_MESSAGE,
                self::FIELD_INTERNAL
            )
        );

        //set default values for internal and optional cols
        $defaults = array(
            'datum'    => static::$_ci->config->item('timestamp'),
            'ip'       => static::getIP(),
            'user_id'  => User::getId(),
            'username' => User::getUser('name'),
            self::FIELD_PIPELINE  => null,
            self::FIELD_PROCEDURE => null,
            self::FIELD_PARAMETER => null,
            self::FIELD_MESSAGE   => null,
            self::FIELD_ITEM_KEY  => null,
            self::FIELD_INTERNAL  => 0
        );
		
        //username missing bug bodge fix
        if (empty($defaults['username'])) {
            $person = new Person(User::getId());
            $defaults['username'] = $person->getName();
        }

        //merge values of log with default values and insert into db
        return (bool) static::$_ci->changelogmodel->insert(array_merge($defaults, $log));
    }

    public static function getActions()
    {
        return array(
            static::ACTION_CREATE,
            static::ACTION_UPDATE,
            static::ACTION_DELETE,
            static::ACTION_VERSION,
            static::ACTION_REVERT,
            static::ACTION_REORDER,
            static::ACTION_IMPORT,
            static::ACTION_UNDELETE,
            static::ACTION_CLONE
        );
    }
	
    public static function getIP()
    {
        //get ip address of user even from behind proxy (I hope it works)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = null;
        return $ip;
    }
}
