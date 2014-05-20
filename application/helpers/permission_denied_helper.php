<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

if ( ! function_exists('permissionDenied'))
{
    /**
     * This loads the Permission Denied page and stops any script proceeding
     * @param string $message Message
     */
    function permissionDenied($message = '')
    {
        $ci =& get_instance();
        $content = "<h1>Permission Denied</h1>\n<p><b>Access is denied to the current page with this error message:</b></p>\n";
        $content .= "<p>" . (empty($message) ? 'You have insufficient permissions to continue with this action.' : $message) . "</p>\n";
        die($ci->load->view('impress', array('content' => $content, 'title' => 'Permission denied'), true));
    }
}
