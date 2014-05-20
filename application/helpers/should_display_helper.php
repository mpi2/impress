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

if( ! function_exists('should_display')){

    /**
     * Determines if a 3P item should be displayed or not depending on what
     * server IMPReSS is running on
     * @param Cohort $p Pipeline, Procedure or Parameter object
     * @return bool True if it may be displayed or false if it shouldn't
     */
    function should_display(Cohort &$p)
    {
        $ci =& get_instance();
        return ! (
            //if it's marked as deleted then don't show it
            $p->isDeleted() ||
            //if it's the live server and the item is internal or marked not visible, then don't show it even if they are logged in
            ($ci->config->item('server') == 'live' && ($p->isInternal() || ! $p->isVisible())) ||
            //if it's the beta server and the item is internal then don't show it. Items on beta will be visible whether or not users are logged in
            ($ci->config->item('server') == 'beta' && $p->isInternal()) 
            //||
            //if the user is not logged in then don't show hidden items
            //(User::isLoggedIn() === false && $p->isVisible() === false)
        );
    }
}
