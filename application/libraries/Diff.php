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

/**
* This class is just a facade so that we can swap out the current "diff" class
* (FineDiff) for another one if needs be without changing the code anywhere
* else, and this interface is a really simple static call too
*/

class Diff
{
    public static function display($from, $to, $option = NULL)
    {
        $diff = new FineDiff($from, $to, $option);
        return $diff->renderDiffToHTML();
    }
}
