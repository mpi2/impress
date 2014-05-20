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

if ( ! function_exists('is_valid_ontology_key')) {

    /**
     * Validates ontology keys by syntax and length. Returns false if the key is
     * invalid or ontology key type is unrecognized. The check is case-independent.
     * Supported types:
     * 
     * <ul>
     *  <li>BSPO</li>
     *  <li>CHEBI</li>
     *  <li>CL</li>
     *  <li>EMAP</li>
     *  <li>ENVO</li>
     *  <li>GO</li>
     *  <li>IMR</li>
     *  <li>MA</li>
     *  <li>MP</li>
     *  <li>PATO</li>
     * </ul>
     * 
     * @param string $key e.g. MP:0001240
     * @param string $type Optionally you can specify the type to validate
     * against, e.g. MP. If not supplied it will try to autodetect it
     * @return bool True if valid key
     */
    function is_valid_ontology_key($key = '', $type = null)
    {
        $type = strtoupper($type);

        //try to work out the type from the key if $type unspecified
        if ($type == null) {
            $pos = strpos($key, ':');
            if($pos === false || $pos < 2)
                return false;
            $type = strtoupper(substr($key, 0, $pos));
        }

        $len = 7;
        //check if valid key
        switch ($type) {
            case 'MP':
            case 'PATO':
            case 'CL':
            case 'BSPO':
            case 'GO':
            case 'IMR':
            case 'MA':
                $len = 7;
                break;
            case 'ENVO':
                $len = 8;
                break;
            case 'CHEBI':
                $len = '4,6';
                break;
            case 'EMAP':
                $len = '3,5';
                break;
            default:
                return false;
                break;
        }

        return preg_match('/^' . $type . ':[0-9]{' . $len . '}$/i', $key);
    }

}
