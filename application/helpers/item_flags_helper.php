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

if ( ! function_exists('item_flags'))
{
    /**
     * Returns the status flags for 3P items as HTML image tags with different
     * colored flag images indicating status such as internal or deprecated
     * @param Cohort $obj Pipeline/Procedure/Parameter
     * @return string Flags HTML
     */
    function item_flags(Cohort &$obj = null) {
        if ($obj == null || !($obj instanceof Cohort))
            return '';

        $flags = '';
        if (!$obj->isVisible())
            $flags .= pick_a_flag('hiddenitem');
        if (!$obj->isActive())
            $flags .= pick_a_flag('inactiveitem');
        if ($obj->isDeleted())
            $flags .= pick_a_flag('deleteditem');
        if ($obj->isInternal())
            $flags .= pick_a_flag('internalitem');
        if ($obj->isDeprecated())
            $flags .= pick_a_flag('deprecateditem');
        if ($obj instanceof Parameter && $obj->isRequired()) {
            $flags .= pick_a_flag('requireditem');
        } else if ($obj instanceof Procedure && $obj->isMandatory()) {
            $flags .= pick_a_flag('requireditem');
        }
        if (empty($flags))
            $flags .= pick_a_flag('regularitem');

        return $flags;
    }

}

if( ! function_exists('pick_a_flag'))
{
    /**
     * Pass a key string such as "requireditem" and it will return the HTML image for that flag
     * @param string $key Key
     * @return string HTML image tag
     */
    function pick_a_flag($key) {
        if (is_array($key)) {
            $s = '';
            foreach ($key AS $k)
                $s .= pick_a_flag($k);
            return $s;
        }

        $flags = array(
            'hiddenitem' => array('blue', 'Item Hidden'),
            'inactiveitem' => array('yellow', 'Item Inactive'),
            'deleteditem' => array('red', 'Item Deleted'),
            'internalitem' => array('purple', 'Item Internal'),
            'deprecateditem' => array('black', 'Item Deprecated'),
            'requireditem' => array('orange', 'Item Required'),
            'mandatoryitem' => array('orange', 'Item Mandatory'),
            'regularitem' => array('green', 'Item Normal')
        );

        // if(array_key_exists($key, $flags))
        // return "<span class='$key' title='$flags[$key]'></span>";
        if (array_key_exists($key, $flags))
            return "<img src='" . base_url() . "/images/flag_{$flags[$key][0]}.png' style='cursor:help' title='{$flags[$key][1]}'> ";
        return '';
    }

}
