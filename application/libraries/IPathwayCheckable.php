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
* The purpose of this interface is to make it easy to check if an item is a
* Pipeline, Procedure, Parameter or an item belonging to it - it belongs to an
* item with a specific pathway like:
*
* <pre>
* IMPC_001 (Pipeline)
*     |
*     - IMPC_XRY_001 (Procedure)
*           |
*           - IMPC_XRY_001_001 (Parameter)
* </pre>
*
* This is important to know because if an item belongs to a parent that is
* deprecated then it should not be editable when the modify_deprecated setting
* is set to off.
* It is also important in identifying the item being edited is being edited
* from the same path it was created from. This stops IMPReSS adminstrators
* from accidently changing an imported item thinking it belongs to the current
* pathway when in fact it originated from elsewhere.
*
* Currently, the models that are PathwayCheckable are:
* pipeline, procedure, parameter, sop, section, parammpterm, parameqterm,
* paramincrement, paramoption, ontologygroup
*/
interface IPathwayCheckable
{
}
