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
 * Creates the breadcrumb on admin pages
 * @version 2014030300 Moved function body over from admin controller
 */

if ( ! function_exists('admin_breadcrumb')) {
    /**
     * Creates the breadcrumb on admin pages
     * @param array $origin Origin-style array
     * @return string rendered HTML of breadcrumb
     */
    function admin_breadcrumb(array $origin = array()) {
        //div wrapper
        $wrap = function($breadcrumb = '') {
            return '<div id="breadcrumb">' . $breadcrumb . '</div>' . PHP_EOL;
        };

        //seperator
        $sep = ' &raquo; ';

        //root
        $bc = anchor(site_url('admin'), 'Admin Home');

        //objs
        $pipeline = $procedure = $parameter = null;

        //pipeline
        if (isset($origin['pipeline_id'])) {
            $pipeline = new Pipeline($origin['pipeline_id']);
            if ( ! $pipeline->exists()) {
                return $wrap($bc);
            } else {
                $bc .= $sep . anchor(site_url('admin') . '/procedure/' . $pipeline->getId(), e($pipeline->getItemName()));
            }
        }

        //procedure
        if (isset($origin['procedure_id'])) {
            $procedure = new Procedure($origin['procedure_id'], $origin['pipeline_id']);
            if ( ! $procedure->exists()) {
                return $wrap($bc);
            } else {
                $pipeline = new Pipeline(@$origin['pipeline_id']);
                $bc .= $sep . anchor(site_url('admin') . '/parameter/' . $procedure->getId() . '/' . $pipeline->getId(), e($procedure->getItemName()));
            }
        }

        //parameter 
        if (isset($origin['parameter_id'])) {
            $parameter = new Parameter($origin['parameter_id'], $origin['procedure_id']);
            if ( ! $parameter->exists()) {
                return $wrap($bc);
            } else {
                $bc .= $sep . e($parameter->getItemName());
            }
        }

        //everything else expected as a [Page Name] => 'Page URL or Path or null'
        foreach ($origin AS $key => $val) {
            if ( ! in_array($key, array('pipeline_id', 'procedure_id', 'parameter_id'))) {
                if ( ! empty($val))
                    $bc .= $sep . anchor($val, e($key));
                else
                    $bc .= $sep . e($key);
            }
        }

        return $wrap($bc);
    }
}
