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
 * The Admin Portal of IMPReSS conveys messages on success or failure of actions
 * with the use of flash messages which are a sessions-based technology. This
 * helper is for the controllers of the admin portal
 * @link http://ellislab.com/codeigniter/user-guide/libraries/sessions.html
 */

if ( ! function_exists('getFlashMessage'))
{
    /**
     * Returns the HTML to display the flash message if it is present
     * @return string Flash message inside div#flash{success|failure} element
     */
    function getFlashMessage()
    {
        $ci =& get_instance();
        $message = $ci->session->flashdata('message');
        return (empty($message)) ? '' : '<div id="flash' . $message["status"] . '">' . $message["text"] . '</div>';
    }
}

if ( ! function_exists('displayFlashMessage'))
{
    /**
     * Redirects page (using HTTP_303) and sets flash message that will appear on
     * the next page
     * @param string $message Message to display on next page
     * @param bool $success Set true if the action resulted in success or false if failure
     * @param string $redirectTo If left empty will return to refering page
     * @link http://en.wikipedia.org/wiki/Post/Redirect/Get
     */
    function displayFlashMessage($message, $success = false, $redirectTo = null)
    {
        $ci =& get_instance();
        $ci->session->set_flashdata('message', array(
            'text' => $message,
            'status' => ($success) ? 'success' : 'failure')
        );
        //prevents form resubmission with HTTP_303
        redirect((empty($redirectTo)) ? $_SERVER['HTTP_REFERER'] : $redirectTo, 'location', 303);
    }
}

if ( ! function_exists('getFormReturnLocation'))
{
    /**
     * This method determines where the user user is redirected after pressing
     * the submit button on a form. It gets this from the returnLocation session
     * @return string Return location URL
     */
    function getFormReturnLocation()
    {
        $ci =& get_instance();
        //if ($this->mode == self::INSERT_MODE && ! $this->session->flashdata('returnLocation')) {
        if ( ! $ci->session->flashdata('returnLocation')) {
            $ci->session->set_flashdata('returnLocation', $_SERVER['HTTP_REFERER']);
            return $ci->session->flashdata('returnLocation');
        }
        if ($ci->session->flashdata('returnLocation')) {
            return $ci->session->flashdata('returnLocation');
        }
        return $_SERVER['HTTP_REFERER'];
    }
}
