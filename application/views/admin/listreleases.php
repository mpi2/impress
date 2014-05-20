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
 * @param array $releases array of rows from the change_log table
 * @param string $controller
 */
?>

<br>
<fieldset><legend>Previous Releases</legend>
<?php
if (empty($releases)):

echo '<p>There are currently no releases in the database.</p>';

else:

foreach ($releases as $release) {
    echo anchor($controller . '/deleterelease/' . $release['id'], ' &nbsp;', array('class'=>'admindelete', 'title'=>'delete', 'style' => 'text-decoration:none'));
    echo ' Released on ' . $release['datum'] . ' by ' . e($release['username']);
    if ( ! empty($release['message']))
            echo ': ' . dexss($release['message']);
    echo '<br>' . PHP_EOL;
}

endif;
?>
</fieldset>
