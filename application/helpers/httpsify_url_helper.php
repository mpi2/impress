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

if ( ! function_exists('httpsify_url'))
{
    /**
     * Modifies a url to have https on front
     * @param string $url URL
     * @return string url with https on front
     */
    function httpsify_url($url = '') {
        return str_replace('http:', 'https:', $url);
    }

}

if ( ! function_exists('dehttpsify_url'))
{
    /**
     * Modifies a url to remove the s from https
     * @param string $url URL
     * @return string url with http on front
     */
    function dehttpsify_url($url = '') {
        return str_replace('https:', 'http:', $url);
    }

}
