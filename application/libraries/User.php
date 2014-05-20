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
 * The User class gets info about the user. Data about the user is collected as and when needed
 * from the mousephenotype.org Drupal database which is on the same server.
 * The class works by looking for the Drupal Cookie to see if they are logged in. Calls to the
 * class methods checks the sessions table and if the session exists then the userId is used
 * to fetch the information for that user from the different tables.
 * It should be pointed out that User Role "IMPReSS Admin" needs to have been created in
 * Drupal for this class to work. But Permissions are created and handled by this class alone
 * and have nothing to do with Drupal-specific configurations or permissions.
 */
class User
{
    protected static $_sessKey;
    protected static $_sessVal;
    protected static $CI;
    protected static $perms;
    protected static $user = array();
    /**
    * User roles - these are set for a user within Drupal
    */
    const SUPERADMIN = 'administrator'; //'IMPReSS SuperAdmin';
    const ADMIN      = 'IMPReSS Admin';
    const USER       = 'IMPReSS User'; //this role is not in use
    /**
    * Permissions - assigned selectivley based on user role
    */
    const ACCESS_ADMIN       = 'access admin';
    const CREATE_ITEM        = 'create item';
    const DELETE_ITEM        = 'delete item';
    const DELETE_OWN_ITEM    = 'delete own item';
    const EDIT_ITEM          = 'edit item';
    const EDIT_OWN_ITEM      = 'edit own item';
    const CREATE_VERSION     = 'create version';
    const REVERT_VERSION     = 'revert version';
    const REVERT_OWN_VERSION = 'revert own version';
    const REORDER_ITEMS      = 'reorder items';
    const IMPORT_ITEM        = 'import items';
    const VIEW_DELETED       = 'view deleted';
    const PURGE_DELETED_ITEM = 'purge_deleted_item';
    /** 
    * @deprecated Permission now deprecated. Now any internal items are 
    * displayed on the internal server but hidden from other servers
    */
    const VIEW_INTERNAL      = 'view internal';


    final protected static function _initialize()
    {
        //make sure the CI object and the cookie is initialized only once
        if ( ! isset(static::$CI)) {
            static::$CI =& get_instance();
            static::_getSess();
        }
    }

    /**
    * Reads in the cookies, finds the drupal login cookie and sets the session key and value class properties
    */
    private static function _getSess()
    {
        if ( ! isset(static::$_sessKey) || ! isset(static::$_sessVal)) {
            //static::_initialize();
            foreach ($_COOKIE AS $cookiekey => $cookieval) {
                //Drupal cookie name begins with SESS
                //$sess = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'SSESS' : 'SESS'; //conditional test doesn't work on a proxy server!
                if (strpos($cookiekey, 'SSESS') === 0 || strpos($cookiekey, 'SESS') === 0) {
                    static::$_sessKey = $cookiekey;
                    static::$_sessVal = $cookieval;
                    return TRUE;
                }
            }
            static::$_sessKey = null;
            static::$_sessVal = null;
            return FALSE;
        }
        return TRUE;
    }

    public static function isLoggedIn()
    {
        static::_initialize();
        if ( ! array_key_exists('uid', static::$user)) {
            $uid = static::getId();
            return ($uid === FALSE) ? FALSE : TRUE;
        }
        return TRUE;
    }

    public static function getId()
    {
        static::_initialize();
        if ( ! array_key_exists('uid', static::$user)) {
            $x = static::$CI->usermodel->getSession(static::$_sessKey, static::$_sessVal);
            if(empty($x))
                return FALSE;
            static::$user['uid'] = $x['uid'];
        }
        return static::$user['uid'];
    }

    public static function getRoles()
    {
        static::_initialize();
        $userId = static::getId();
        if($userId === FALSE)
            return array();
        if ( ! array_key_exists('roles', static::$user)) {
            $rs = static::$CI->usermodel->getUserRoles($userId);
            if(empty($rs))
                return array();
            $roles = array();
            foreach($rs AS $role)
                $roles[] = $role['name'];
            static::$user['roles'] = $roles;
        }
        return static::$user['roles'];
    }

    /**
    * Only works with the impress_ajax drupal module which defines the permissions
    */
    // public static function getPermissions()
    // {
        // static::_initialize();
        // $userId = static::getId();
        // if($userId === FALSE)
            // return FALSE;
        // $ps = static::$CI->usermodel->getUserPermissions($userId);
        // if(empty($ps))
            // return array();
        // $perms = array();
        // foreach($ps AS $perm)
            // $perms[] = $perm['permission'];
        // return $perms;
    // }

    public static function getPermissions()
    {
        static::_initialize();
        if ( ! array_key_exists('permissions', static::$user)) {
            $perms = array();
            if (static::isSuperAdmin()) {
                $perms = array(
                    self::ACCESS_ADMIN,
                    self::CREATE_ITEM,
                    self::DELETE_ITEM,
                    self::DELETE_OWN_ITEM,
                    self::EDIT_ITEM,
                    self::EDIT_OWN_ITEM,
                    self::CREATE_VERSION,
                    self::REVERT_VERSION,
                    self::REVERT_OWN_VERSION,
                    self::REORDER_ITEMS,
                    self::IMPORT_ITEM,
                    self::VIEW_INTERNAL,
                    self::VIEW_DELETED,
                    self::PURGE_DELETED_ITEM
                );
            } else if (static::isAdmin()) {
                $perms = array(
                    self::ACCESS_ADMIN,
                    self::CREATE_ITEM,
                    self::DELETE_ITEM,
                    self::DELETE_OWN_ITEM,
                    self::EDIT_ITEM,
                    self::EDIT_OWN_ITEM,
                    self::CREATE_VERSION,
                    self::REVERT_VERSION,
                    self::REVERT_OWN_VERSION,
                    self::REORDER_ITEMS,
                    self::IMPORT_ITEM,
                    self::VIEW_INTERNAL,
                    self::VIEW_DELETED
                );
            } else if (static::isLoggedIn()) {
                $perms = array(self::ACCESS_ADMIN);
            }
            static::$user['permissions'] = $perms;
        }
        return static::$user['permissions'];
    }

    public static function isSuperAdmin()
    {
        static::_initialize();
        return in_array(self::SUPERADMIN, static::getRoles());
    }

    public static function isAdmin()
    {
        static::_initialize();
        return (self::isSuperAdmin() || in_array(self::ADMIN, static::getRoles()));
    }

    public static function hasPermission($perm)
    {
        static::_initialize();
        return in_array($perm, static::getPermissions());
    }

    public static function hasPermissions($perms)
    {
        static::_initialize();
        if(empty($perms))
            return FALSE;

        $userpermissions = static::getPermissions();
        $hasperms = TRUE;
        foreach ((array)$perms AS $perm) {
            if( ! in_array($perm, $userpermissions))
                $hasperms = FALSE;
        }
        return $hasperms;
    }

    public static function hasAnyOfThesePermissions($perms)
    {
        static::_initialize();
        if(empty($perms))
            return FALSE;

        $userpermissions = static::getPermissions();
        foreach ((array)$perms AS $perm) {
            if(in_array($perm, $userpermissions))
                return TRUE;
        }
        return FALSE;
    }

    /**
    * @param string $field A field name in uid, roles (array), permissions (array),
    * name, pass, mail, theme, signature, signature_format, created, access, login,
    * status (flag), timezone, language, picture (flag), init (initial email address),
    * data (serialized array)
    * @return mixed It returns FALSE if the user was not found or the $field was not found.
    * If the $field argument is not supplied it returns the $user array.
    * If the $field is found it fetches the value of the field requested.
    */
    public static function getUser($field = null)
    {
        static::_initialize();
        $userId = static::getId();
        if($userId === FALSE)
            return FALSE;
        $user = null;
        if( ! array_key_exists('name', static::$user))
            $user = static::$CI->usermodel->getByUserId($userId);
        if(empty($user))
            return FALSE;
        if( ! array_key_exists('roles', static::$user)) static::getRoles();
        if( ! array_key_exists('permissions', static::$user)) static::getPermissions();
        static::$user = array_merge(static::$user, $user);
        if(empty($field))
            return static::$user;
        if(array_key_exists($field, static::$user))
            return static::$user[$field];
        else
            return FALSE;
    }

    public static function getSessKey()
    {
        return static::$_sessKey;
    }

    public static function getSessVal()
    {
        return static::$_sessVal;
    }
}
