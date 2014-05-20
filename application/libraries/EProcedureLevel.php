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
 * Procedures can be defined at a particular level of the Pipeline. Almost all
 * Procedures are normal EXPERIMENT level, but Housing and Husbandry is in the
 * HOUSING level and Viabiliy and Fertility at the LINE level.
 * In order to submit data to the DCC, a centre need to know what level of the
 * Pipeline a Procedure falls under so they can generate the correct XML. I use
 * it to generate valid XML Submission Examples too.
 * 
 * This ENUM is associated with procedure.level in the database
 */
class EProcedureLevel
{
    const EXPERIMENT = 'experiment';
    const HOUSING    = 'housing';
    const LINE       = 'line';
    
    /**
     * @return string default value of procedure
     */
    public function __toString()
    {
        return self::EXPERIMENT;
    }
    
    /**
     * @return array levels
     */
    public static function __toArray()
    {
        return array(
            self::EXPERIMENT,
            self::HOUSING,
            self::LINE
        );
    }
    
    /**
     * @param string $value
     * @return bool
     */
    public static function validate($value = '')
    {
        return in_array($value, self::__toArray());
    }
}
