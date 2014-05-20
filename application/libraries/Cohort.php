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
* This abstract super class is inherited by Pipeline, Procedure and Parameter
* and contains all the properties and methods they have in common
*/

abstract class Cohort
{
    /**
    * @var int $id record id
    */
    protected $_id;
    /**
    * @var string $itemKey holds an id like PIPE_{id/procType}_{majorVersion}_{minorVersion}
    */
    protected $_itemKey;
    /**
    * @var string $itemName name of entity
    */
    protected $_itemName;
    /**
    * @var bool $visible shows up site or not
    */
    protected $_visible;
    /**
    * @var bool $active enabled or not
    */
    protected $_active;
    /**
    * @var bool $deprecated - if an item is deprecated it cannot be edited,
    * deleted, imported, nor can a new version of it be created
    */
    protected $_deprecated;
    /**
    * @var string $description an explanation of the entity
    */
    protected $_description;
    /**
    * @var int $majorVersion the left most part of 1.2
    */
    protected $_majorVersion;
    /**
    * @var int $minorVersion the right most part of 1.2
    */
    protected $_minorVersion;
    /**
    * @var string $timeModified
    */
    protected $_timeModified;
    /**
    * @var int $userId
    */
    protected $_userId;
    /**
    * @var bool $internal - is the item internal (not for public display) or not
    */
    protected $_internal;
    /**
    * @var bool $deleted - is the item deleted
    */
    protected $_deleted;
    /**
    * @var stdClass $CI holds the current instance of the global CodeIgniter object
    */
    protected $CI;

    /**
    * @return string DateTime::W3C
    */
    public function getTimeModified()
    {
        if ($this->_timeModified != null) {
            try {
                $d = new DateTime($this->_timeModified);
                return $d->format(DateTime::W3C);
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
    * @return int User Id
    */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
    * @return bool Internal flag
    */
    public function isInternal()
    {
        return (bool) $this->_internal;
    }

    /**
    * @return int Major Version
    */
    public function getMajorVersion()
    {
        return $this->_majorVersion;
    }

    /**
    * @return int Minor Version
    */
    public function getMinorVersion()
    {
        return $this->_minorVersion;
    }

    /**
    * @return string Item Key
    */
    public function getItemKey()
    {
        return $this->_itemKey;
    }

    /**
    * @return int ID
    */
    public function getId()
    {
        return $this->_id;
    }

    /**
    * @return string Item Name
    */
    public function getItemName()
    {
        return $this->_itemName;
    }

    /**
    * @return bool visibility
    */
    public function isVisible()
    {
        return (bool) $this->_visible;
    }

    /**
    * @return bool active (enabled)
    */
    public function isActive()
    {
        return (bool) $this->_active;
    }

    /**
    * @return bool deprecated
    */
    public function isDeprecated()
    {
        return (bool) $this->_deprecated;
    }

    /**
    * @return bool deleted
    */
    public function isDeleted()
    {
        return (bool) $this->_deleted;
    }

    /**
    * @return string description
    */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
    * @return bool exists
    */
    public function exists()
    {
        return ! empty($this->_id);
    }

    /**
    * @return bool Checks if key of this item is deprecated
    */
    public function hasDeprecatedKey()
    {
        return KeyUtil::isDeprecatedKey($this->getItemKey());
    }

    /**
    * Constructor
    */
    public function __construct()
    {
        $this->CI =& get_instance();
    }
    
    /**
     * @param array $row Key-Value fields to populate class properties
     */
    public abstract function seed(array $row = array());
}
