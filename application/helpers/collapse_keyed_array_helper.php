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

if ( ! function_exists('collapse_keyed_array')) {

    /**
    * Takes a keyed multidimensional array and collapses it into a flat 1D hash
    * 
    * Example:
    * 
    * <pre>
    * print_r(collapse_keyed_array(
    *        array(
    *            'red' => 'red',
    *            'ora' => 'ora',
    *            'yel' => 'yel',
    *            array(
    *                'gre' => 'gre',
    *                'blu' => 'blu',
    *                array(
    *                    'ind' => 'ind',
    *                    'vio' => 'vio'
    *                )
    *            ),
    *            'vio' => 'pur'
    *        )
    * ));
    * #output: Array ( [red] => red [ora] => ora [yel] => yel [gre] => gre [blu] => blu [ind] => ind [vio] => pur )
    * </pre>
    * 
    * @param array $arr Hash
    * @return array flat array
    */
    function collapse_keyed_array(array $arr = array())
    {
        $carr = array();
        foreach ($arr as $key => $val) {
            if ( ! is_array($val)) {
                $carr[$key] = $val;
            } else {
                $carr = array_merge_recursive(
                    $carr,
                    collapse_keyed_array($arr[$key])
                );
            }
        }
        return $carr;
    }
}
