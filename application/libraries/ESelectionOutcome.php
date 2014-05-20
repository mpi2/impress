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
 * An ontology selection outcome should be one of these
 */
class ESelectionOutcome
{
    const INCREASED = 'INCREASED';
    const DECREASED = 'DECREASED';
    const ABNORMAL  = 'ABNORMAL';
    const INFERRED  = 'INFERRED';
    const SOTRAIT   = 'TRAIT';
    /** @deprecated */
    const INCREASED_N = 1;
    const DECREASED_N = 2;
    const ABNORMAL_N  = 3;
    const INFERRED_N  = 4;
    const TRAIT_N     = 5;

    /**
    * @return string default value of ABNORMAL
    */
    public function __toString()
    {
        return self::ABNORMAL;
    }

    /**
    * @return int default id of abnormal
    * @deprecated
    */
    public function __toString_n()
    {
        return self::ABNORMAL_N;
    }

    /**
    * @return array Value Types
    */
    public static function __toArray()
    {
        return array(
            self::INCREASED,
            self::DECREASED,
            self::ABNORMAL,
            self::INFERRED,
            self::SOTRAIT
        );
    }

    /**
    * @return array Value ids
    * @deprecated
    */
    public static function __toArray_n()
    {
        return array(
            self::INCREASED_N,
            self::DECREASED_N,
            self::ABNORMAL_N,
            self::INFERRED_N,
            self::TRAIT_N
        );
    }

    /**
    * @return bool
    */
    public static function validate($value = '')
    {
        return in_array($value, self::__toArray());
    }

    /**
    * @return bool
    * @deprecated
    */
    public static function validate_n($value = '')
    {
        return in_array($value, self::__toArray_n());
    }
}
