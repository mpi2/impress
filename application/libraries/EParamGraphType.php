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
 * A Parameter's graph should be one of these types or NULL
 */
class EParamGraphType
{
    const ONE_D = '1D';
    const TWO_D = '2D';
    const CATEGORICAL = 'CATEGORICAL';
    const IMAGE = 'IMAGE';
    const NULL = null;

    /**
    * @return string default value of CATEGORICAL
    */
    public function __toString()
    {
        return self::CATEGORICAL;
    }

    /**
    * @return array Value Types
    */
    public static function __toArray()
    {
        return array(
            self::NULL,
            self::ONE_D,
            self::TWO_D,
            self::CATEGORICAL,
            self::IMAGE
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
