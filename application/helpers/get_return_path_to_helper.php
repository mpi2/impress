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

if ( ! function_exists('getReturnPathTo'))
{
    /**
     * This helper relies on admin_flash_helper having being loaded and uses it's
     * getFormReturnLocation() method
     *
     * This function ensures, after deleting, editing, or adding a new item in IMPReSS,
     * the user is returned to the correct page relevent to the item they modified. It
     * is used in conjunction with Admin::_displayFlashMessage() as its third argument-
     * the return location path
     * 
     * @uses admin_flash_helper.php getFormReturnLocation()
     * 
     * @param string $itemType e.g. pipeline, paramoption, section
     * @param array $origin An origin-style array
     * @return string A URL path to where the user should be redirected to on success
     */
    function getReturnPathTo($itemType, array $origin = array())
    {
        if ( ! function_exists('getFormReturnLocation')) {
            die('Admin flash helper needs to be loaded first');
        }
	
        foreach (array('pipeline', 'procedure', 'parameter') as $p) {
            if (isset($origin["nv$p"]) && ! empty($origin["nv$p"])) {
                $origin["{$p}_id"] = $origin["nv$p"];
            } else {
                $origin["{$p}_id"] = (isset($origin["{$p}_id"])) ? $origin["{$p}_id"] : '';
            }
        }

        if ($itemType == 'home' || $itemType == 'pipeline') {
            return 'admin';
        } else if ($itemType == 'procedure') {
            return 'admin/procedure/' . @$origin['pipeline_id'];
        } else if ($itemType == 'parameter') {
            return 'admin/parameter/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'sop') {
            return 'admin/sop/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'parammpterm' || $itemType == 'parameqterm') {
            return 'admin/ontology/' . @$origin['parameter_id'] . '/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'paramincrement') {
            return 'admin/increment/' . @$origin['parameter_id'] . '/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'paramoption') {
            return 'admin/option/' . @$origin['parameter_id'] . '/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'paramontologyoption') {
            return 'admin/ontologygroup/' . @$origin['parameter_id'] . '/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'section') {
            return 'admin/sop/' . @$origin['procedure_id'] . '/' . @$origin['pipeline_id'];
        } else if ($itemType == 'sectiontitle') {
            return 'admin/sectiontitles/' . @$origin['pipeline_id'] . '/' . @$origin['procedure_id'];
        } else {
            return getFormReturnLocation();
        }
    }
}
