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
 * If the database table has a user_id field then it's model should be 
 * implementing this interface.
 * The purpose of this interface is to make it easy to check the permissions
 * of the users editing/deleting the record. For example, if the user has the
 * EDIT_OWN_ITEMS permission then the user_id of the record being edited needs
 * to match the id of the user doing the editing but if it is different a 
 * "permission denied" error message should be displayed
 */
interface IUserIdCheckable
{
}
