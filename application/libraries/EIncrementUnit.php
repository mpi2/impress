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
 * Increments should be one of these "Units"
 */

class EIncrementUnit
{
    const NULL = null;
    const NUMBER = 'number';
    const SECONDS = 'seconds';
    const MINUTES = 'minutes';
    const AGE_IN_DAYS = 'Age In Days';
    const TIHRTLO = 'Time in hours relative to lights out';
    const TIHRTLN = 'Time in hours relative to lights on';
    const MALPSC = 'Minutes after LPS challenge';

    /**
    * @return string default value of NULL
    */
    public function __toString()
    {
        return self::NULL;
    }

    /**
    * @return array Values
    */
    public static function __toArray()
    {
        return array(
            self::NULL,
            self::NUMBER,
            self::SECONDS,
            self::MINUTES,
            self::AGE_IN_DAYS,
            self::TIHRTLO,
            self::TIHRTLN,
            self::MALPSC
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
