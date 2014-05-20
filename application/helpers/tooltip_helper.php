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

if ( ! function_exists('tooltip')) {
    /**
     * This function finds the tooltip in the language file by it's key and
     * echo's out an escaped string for the jquery tooltip to work. This function
     * assumes the CodeIgniter language class has already been loaded and
     * configured and the language file selected
     * @param string $key Key as defined in the language php file
     * @link http://ellislab.com/codeigniter/user-guide/libraries/language.html
     */
    function tooltip($key) {
        $ci =& get_instance();
        echo htmlentities($ci->lang->line($key), ENT_QUOTES, 'UTF-8');
    }
}
