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
 * Different versions of items in the database can have a relationship to each
 * other which is described by one of these relation types
 */
class ERelationType
{
    const EQUIVALENT  = 'EQUIVALENT';
    const CONVERTIBLE = 'CONVERTIBLE';
    const SIMILAR     = 'SIMILAR';
    const DIFFERENT   = 'DIFFERENT';
    const CONVERSE    = 'CONVERSE';

    /**
     * Default value of EQUIVALENT
     * @return string default value of EQUIVALENT
     */
    public function __toString()
    {
        return self::EQUIVALENT;
    }

    /**
     * @return array
     */
    public static function __toArray()
    {
        return array(
            self::EQUIVALENT,
            self::CONVERTIBLE,
            self::SIMILAR,
            self::DIFFERENT,
            self::CONVERSE
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
