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
 * The purpose of this interface is to identify any models that implement this
 * interface as being able to have their records restored from a *_deleted table
 * using the _hardUndelete() method
 */
interface IRecyclable
{
    /**
    * These constants are the array keys expected in the array returned by
    * calling getRecyclableFields() in a model that implements IRecyclable
    */
    const R_ID = 'id';
    const R_NAME = 'name';
    const R_DATE = 'time_modified';
    const R_USER = 'user_id';
    const R_FIELDS = 'fields';

    /**
    * Returns array with keys as defined by consts above with values being the
    * fields of that model. The R_FIELDS field expects to have an array of
    * additional fields to display in the view (if any).
    *
    * Example array that might be returned for the ParamMPTermDeletedModel:
    * array(
    *     'id' => 123,
    *     'name' => null,
    *     'time_modified' => '2012-12-17 09:49:03',
    *     'user_id' => 1,
    *     array(
    *        'mp_term' => 'Increased Grip Strength',
    *        'mp_id => 'MP:0010052'
    *     )
    * )
    */
    public static function getRecyclableFields();

    /**
    * @param int $id If the item has been soft-deleted then this is the current
    * id of the item in the table, otherwise, if it was hard-deleted then it is
    * the id of the record as found in the *_deleted table
    * @param array $origin The location (Pipeline/Procedure/Parameter) to where
    * the record will be restored
    */
    public function restore($id, array $origin);
}
