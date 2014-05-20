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
 * Parameters should be one of these types
 */
class EParamType
{
    const SIMPLE = 'simpleParameter';
    const METADATA = 'procedureMetadata';
    const ONTOLOGY = 'ontologyParameter';
    const SERIES = 'seriesParameter';
    const MEDIA = 'mediaParameter';
    const MEDIA_SAMPLE = 'mediaSampleParameter';
    const SERIES_MEDIA = 'seriesMediaParameter';

    /**
    * @return string default value of simpleParameter
    */
    public function __toString()
    {
        return self::SIMPLE;
    }

    /**
    * @return array Parameter Types
    */
    public static function __toArray()
    {
        return array(
            self::SIMPLE,
            self::METADATA,
            self::ONTOLOGY,
            self::SERIES,
            self::MEDIA,
            self::MEDIA_SAMPLE,
            self::SERIES_MEDIA
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
