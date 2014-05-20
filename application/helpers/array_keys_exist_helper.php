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

if ( ! function_exists('array_keys_exist')) {

    /**
    * Checks a hash array to see if the given keys exists in it
    * @param array $arr The hash array with all the keyed data in it
    * @param array $keys The keys we are checking for
    * @return bool FALSE if one or more keys don't exist, else TRUE
    */
    function array_keys_exist(array $arr, array $keys)
    {
        foreach ($keys as $key) {
            if ( ! array_key_exists($key, $arr))
                return false;
        }
        return true;
    }

}
