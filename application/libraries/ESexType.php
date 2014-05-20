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
 * An Ontology classes' sex property should be one of these types
 */
class ESexType
{
    const MALE = 'M';
    const FEMALE = 'F';
    const NULL = null;

    /**
    * @return string default value of MALE
    */
    public function __toString()
    {
        return self::MALE;
    }

    /**
     * @return array
     */
    public static function __toArray()
    {
        return array(
            self::NULL,
            self::MALE,
            self::FEMALE
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
