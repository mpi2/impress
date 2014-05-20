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
 * Version Logging class
 */
class ImpressLogger
{
    const INFO     = 'INFO';
    const WARNING  = 'WARN';
    const ERROR    = 'ERROR';
    const SECURITY = 'SECURITY';
    const ACTION_CREATE   = 'CREATED';
    const ACTION_UPDATE   = 'UPDATED';
    const ACTION_DELETE   = 'DELETED';
    const ACTION_VERSION  = 'VERSIONED';
    const ACTION_REORDER  = 'REORDER';
    const ACTION_IMPORT   = 'IMPORT';
    const ACTION_UNDELETE = 'UNDELETE';
    protected static $CI;

    /**
    * @param string|array $type You should provide a log type here (@see ImpressLogger::getLogTypes()) or
    * provide an array with the arguments to be passed to this method (@see ImpressLogger::_logArray())
    * @param string $message What's been going on
    * @param string $item What item type this message concerns, e.g. parameter
    * @param int $itemId The id of the item this message affects
    * @param string You should provide an action type here (@see ImpressLogger::getActions())
    * @param bool $alsoerrorlogit If you want to also stick this error into the
    * CodeIgniter error log then set this parameter to TRUE
    */
    public static function log(
        $type = 'INFO',
        $message = null,
        $item = null,
        $itemId = null,
        $action = null,
        $alsoerrorlogit = false
    )
    {
        if (static::$CI == null)
            static::$CI =& get_instance();

        if(is_array($type))
            return static::_logArray($type);

        static::$CI->load->model('logsmodel');

        //get ip address of user even from behind proxy (I hope it works)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
        else $ip = null;

        static::$CI->logsmodel->insert(
            array(
                'user_id'  => User::getId(),
                'username' => User::getUser('name'),
                'type'     => (in_array($type, static::getLogTypes())) ? $type : static::INFO,
                'item'     => strtolower($item),
                'item_id'  => (empty($itemId)) ? NULL : (int)$itemId,
                'action'   => (in_array($action, static::getActions())) ? $action : NULL,
                'url'      => current_url(),
                'message'  => $message,
                'post'     => print_r(@$_POST, TRUE), //useful for debugging
                'ip'       => $ip,
                'datum'    => static::$CI->config->item('timestamp')
            )
        );
        if ($alsoerrorlogit)
            static::errorlog($type, $message);
    }

    /**
    * @see ImpressLogger::log()
    * @param array $arr an array with keys containing parameters to pass to the ImpressLogger::log() method
    * Allowed Keys: type, message, item, item_id, action, alsoerrorlogit
    */
    protected static function _logArray($arr)
    {
        $args = array_merge(
            array(
                'type'    => static::INFO,
                'message' => null,
                'item'    => null,
                'item_id' => null,
                'action'  => null,
                'alsoerrorlogit' => false
            ),
            $arr
        );
        return static::log($args['type'], $args['message'], $args['item'], $args['item_id'], $args['action'], $args['alsoerrorlogit']);
    }

    public static function getActions()
    {
        return array(
            static::ACTION_CREATE,
            static::ACTION_UPDATE,
            static::ACTION_DELETE,
            static::ACTION_VERSION,
            static::ACTION_REORDER,
            static::ACTION_IMPORT,
            static::ACTION_UNDELETE
        );
    }

    public static function getLogTypes()
    {
        return array(
            static::INFO,
            static::WARNING,
            static::ERROR,
            static::SECURITY
        );
    }

    public static function errorlog($type = null, $message = null)
    {
        if (static::$CI == null)
            static::$CI =& get_instance();

        switch ($type) {
            case static::INFO:
                $type = 'info';
                break;
            case static::WARNING:
                $type = 'debug';
                break;
            default:
                $type = 'error';
        }

        static::$CI->log_message($type, $message);
    }

}
