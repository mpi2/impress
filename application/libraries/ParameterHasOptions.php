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
 * Fetch all the options belonging to a parameter
 */
class ParameterHasOptions
{
    /**
     * @param int $parameterId
     * @return ParamOption[] ParamOption objects array
     */
    public static function getOptions($parameterId)
    {
        $CI =& get_instance();
        $CI->load->model('parameterhasoptionsmodel');
        $options = array();
        foreach ($CI->parameterhasoptionsmodel->getByParameter($parameterId) as $option) {
            $o = new ParamOption();
            $o->seed($option);
            $o->setParameterId($parameterId);
            $options[] = $o;
        }
        return $options;
    }
}
