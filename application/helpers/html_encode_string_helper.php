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

if ( ! function_exists('html_encode_string')) {
    /**
     * Encodes each character to decimal or hex representation randomly.
     * Idea modified from example by Sameer Borate of CodeDiesel
     * @link http://www.codediesel.com/security/encode-your-email-links-to-prevent-spam/
     * @param string $str The string to convert
     * @return string HTML encoded string
     */
    function html_encode_string($str = '') {
        $s = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $s .= '&#' . ((rand(0, 1)) ? ord($str[$i]) : 'X' . dechex(ord($str[$i]))) . ';';
        }
        return $s;
    }
}
