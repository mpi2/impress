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
 * A Procedure is carried out during a week that occurs at this stage
 */
class EProcedureWeekStage
{
    const ADULT     = 'A';
    const EMBRYONIC = 'E';
    const TERMINAL  = 'T';
    const MIXED     = 'M';

    /**
     * Returns default value of A (Adult)
     * @return string default value of A (ADULT)
     */
    public function __toString()
    {
        return self::ADULT;
    }

    /**
    * @return array Stages
    */
    public static function __toArray()
    {
        return array(
            self::ADULT,
            self::EMBRYONIC,
            self::TERMINAL,
            self::MIXED
        );
    }

    /**
     * Returns hash of procedure stage keys and labels
     * 
     * <pre>["A" => "Adult", "E" => "Embryonic", "T" => "Terminal", "M" => "Mixed"]</pre>
     * 
     * @return array [EProcedureWeekStage Value] => label
     */
    public static function getLabels()
    {
        return array_combine(
            self::__toArray(),
            array('Adult', 'Embryonic', 'Terminal', 'Mixed')
        );
    }

    /**
     * @param mixed $value Value
     * @return bool
     */
    public static function validate($value = '')
    {
        return in_array($value, self::__toArray());
    }
}
