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

if ( ! function_exists('array_key_values')) {

    /**
    * Extracts the value of the specified key from a hashmap array and returns
    * a simple flat array with the values
    *
    * @param array $arr The hashmap array e.g. [['a'=>1,'b'=>2], ['a'=>3,'b'=>4]]
    * @param string $key (optional) The key in each hashmap whose value we want, e.g. 'a'
    * @param bool $unique Return distinct values? default is true
    * @param bool $keepNull Allow a null to be in returned array? default is false
    * @return array A flat array of values e.g. [1, 3]
    */
    function array_key_values(array $arr, $key = null, $unique = true, $keepNull = false)
    {
        $flatArr = array();
        foreach ($arr as $hash) {
            if (isset($hash[$key])) {
                if ($hash[$key] === null && $keepNull === false)
                    continue;
                $flatArr[] = $hash[$key];
            }
        }
        return ($unique) ? array_unique($flatArr) : $flatArr;
    }

}
