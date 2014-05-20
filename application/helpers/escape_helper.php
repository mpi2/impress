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

if ( ! function_exists('e')) {
    /**
     * Escapes strings to make them suitable for display on the web (& => &amp;amp;)
     * @param string $s
     * @return string Escaped string
     */
    function e($s = '') {
        return htmlentities($s, ENT_QUOTES, 'UTF-8');
    }
}

if ( ! function_exists('dexss')) {
    /**
     * Removes any potential XSS code from a string
     * @link http://ellislab.com/codeigniter/user-guide/libraries/security.html
     * @param string $s
     * @return string Cleaned-up string
     */
    function dexss($s = '') {
        $ci =& get_instance();
        return $ci->security->xss_clean($s);
    }
}
