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

if ( ! function_exists('titlize'))
{
    /**
     * Converts a table heading and makes it suitable to display as a title by
     * removing underscores and capitalising each word and converting 'No' or 'Num'
     * to No.. To display Number instead of No., set the second argument as true
     * @param string $title Title
     * @param bool $lengthen Choose longer word rather than shorter one -
     * currently lengthens No or Num to Number
     * @return string Nice Title
     */
    function titlize($title = '', $lengthen = false) {
        $title = ucwords(strtolower(str_replace('_', ' ', $title)));
        $title = preg_replace('/(^No |^Num | No | Num | No$| Num$)/', ' No. ', $title);
        if ($lengthen)
            $title = str_replace(' No. ', 'Number', $title);
        return trim(preg_replace('/\s+/', ' ', $title));
    }
}
