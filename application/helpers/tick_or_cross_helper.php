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

if ( ! function_exists('tick_or_cross')) {
    /**
     * Returns HTML to display big green tick image or spaces if false
     * @param $val Pass true or 1 to display tick
     * @param $spaces The number of whitespaces to display with alternating " " and "&amp;nbsp;"
     * @return HTML for the tick image or spaces
     */
    function tick_or_cross($val = null, $spaces = 0) {
        if ($val) {
            return '<img src="' . base_url() . 'images/tick.png" border="0" alt="Tick">';
        } else {
            $s = '';
            while ($spaces > 0) {
                $s .= ($spaces % 2 == 0) ? ' ' : '&nbsp;';
                $spaces--;
            }
            return $s;
        }
    }

}
