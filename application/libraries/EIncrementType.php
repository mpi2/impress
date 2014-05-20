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
 * Increments should be one of these "types"
 */
class EIncrementType
{
    const REPEAT   = 'repeat';
    const DEFINED  = 'float';
    const DATETIME = 'datetime';

    /**
    * @return string default value of REPEAT
    */
    public function __toString()
    {
        return self::REPEAT;
    }

    /**
    * @return array Types
    */
    public static function __toArray()
    {
        return array(
            self::REPEAT,
            self::DEFINED,
            self::DATETIME
        );
    }

    /**
    * @return array [EIncrementType Value] => label
    */
    public static function getLabels()
    {
        return array_combine(
            self::__toArray(),
            array('Repeat Readings', 'Single Defined Increment', 'DateTime Increments')
        );
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function validate($value = '')
    {
        return in_array($value, self::__toArray());
    }
}
