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

if ( ! function_exists('delete_array_values')) {

    /**
     * Delete the specified values from the array
     * @param array $arr The array with all the values
     * @param array|string $values The values we want to delete from the array
     * @return array The array with the given values removed
     */
    function delete_array_values(array $arr, $values)
    {
        foreach ((array)$values as $val) {
            if(($i = array_search($val, $arr)) !== false)
                unset($arr[$i]);
        }
        return $arr;
    }

}
