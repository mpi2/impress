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
 * @param array  $logs
 * @param string $betaorlive
 * @param string $latestReleaseDate
 * @param string $controller
 */

if (empty($logs)):
?>

<p>There is no history available for this item.</p>

<?php
else:
?>

<?php
$datum = null;

$cols = array('action type', 'time', 'user', 'item type', 'affected item', 'description');
$colCount = count($cols);
$tableHeaders = implode('', array_map(function($h){return '<th>' . ucwords($h) . '</th>';}, $cols));
$userIsAdmin = (User::isAdmin() || User::isSuperAdmin());
$first = true;

foreach ($logs as $log) {
    //date heading
    try {
        $date = new DateTime($log['datum']);
        $date = $date->format('d-m-Y');
    }
    catch (Exception $e) {
        $date = substr($log['datum'], 0, strpos($log['datum'], ' ') - 1);
    }
    if ($date != $datum) {
        $datum = $date;
        if ( ! $first)
            echo "</tbody></table>\n";
        echo '<h3>Changes made on ', $date, '</h3>', PHP_EOL,
             '<table class="listitemstable changehistorytables">', PHP_EOL,
             '<thead><tr>', $tableHeaders, '</tr></thead><tbody>', PHP_EOL;
        $first = false;
    }
    
    echo '<tr', (($log[$betaorlive.'_release_date'] == $latestReleaseDate) ? ' class="latestrelease"' : ''), '>', PHP_EOL;
    
    //action
    echo '<td class="tabularactionevent action', strtolower($log['action_type']), '" title="', $log['action_type'] ,'"></td>', PHP_EOL;

    //time
    echo '<td>', (($date instanceof DateTime) ? $date->format('H:i:s') : substr($log['datum'], strpos($log['datum'], ' ') + 1)), '</td>', PHP_EOL;

    //user
    echo '<td>', (($userIsAdmin) ? e($log['username']) : 'User ' . $log['user_id']), '</td>', PHP_EOL;
    
    //item type
    echo '<td>', $log['item_type'], '</td>', PHP_EOL;
    
    //affected item
    echo '<td>';
    if ( ! empty($log['item_key'])) {
        switch ($log['item_type']) {
            case 'Parameter':
            case 'Parameter Relationship':
            case 'Parameter Increment':
            case 'Parameter Option':
            case 'ParamOption Relationship':
            case 'Ontology Group':
            case 'Ontology Option':
                echo anchor('parameters/' . $log['procedure_id'] . '/' . $log['pipeline_id'], $log['item_key']);
                break;
            case 'Protocol':
            case 'Protocol Section':
            case 'section':
                echo anchor('protocol/' . $log['procedure_id'] . '/' . $log['pipeline_id'], $log['item_key']);
                break;
            case 'EQ Ontology':
            case 'MP Ontology':
                echo anchor('parameterontologies/' . $log['parameter_id'] . '/' . $log['procedure_id'], $log['item_key']);
                break;
            case 'Procedure':
            case 'Procedure Relationship':
                echo anchor('procedures/' . $log['pipeline_id'], $log['item_key']);
                break;
            case 'Pipeline':
                echo anchor('pipelines', $log['item_key']);
                break;
            default:
                echo $log['item_key'];
        }
    } else {
        echo $log['item_id'];
    }
    echo "</td>\n";
    
    //description
    echo '<td class="changehistorymessage">', e($log['message']), '</td>', PHP_EOL;
    
    echo "</tr>\n";
}
?>

</tbody></table>

<?php
endif;
