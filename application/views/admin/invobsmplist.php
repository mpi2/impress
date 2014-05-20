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
 * @param array $items Result set
 * @param array $terms
 * @param bool $displayTermsList
 * @param array $mps
 */

if (empty($mps))
{
    echo '<p>IMPReSS MP ontologies are up-to-date.</p>';
}
else
{
    if ($displayTermsList) {
        echo '<p>There are issues concerning these MP Ids. The correct form should be: </p><ul>';
        foreach ($terms as $term)
            echo '<li>[' . $term['id'] . '] ' . $term['term'] . '</li>' . PHP_EOL;
        echo '</ul>' . PHP_EOL;
    } else {
        echo '<p>There are issues concerning these MP Ids: ' . implode(', ', $mps) . '.</p>';
    }
    
    echo '<p>Affected Parameters:</p>';
    echo '<table class="listitemstable"><thead><tr>';
    echo '<th>Pipeline</th><th>Procedure</th><th>Parameter</th><th>Parameter Ontologies</th><th>Parameter Ontology Options</th>';
    echo '</tr></thead><tbody>' . PHP_EOL;
    foreach ($items as $item) {
        echo '<tr>' . PHP_EOL;
        echo '<td>' . e($item['pipeline_name'])  . ' <span class="pipelinekey">' . $item['pipeline_key']  . '</span></td>' . PHP_EOL;
        echo '<td>' . e($item['procedure_name']) . ' <span class="procedurekey">' . $item['procedure_key'] . '</span></td>' . PHP_EOL;
        echo '<td>' . e($item['parameter_name']) . ' <span class="parameterkey">' . $item['parameter_key'] . '</span></td>' . PHP_EOL;
        echo '<td>'; 
        if ( ! empty($item['param_mp']))
            echo anchor($controller . '/ontology/' . $item['parameter_id'] . '/' . $item['procedure_id'] . '/' . $item['pipeline_id'], $item['param_mp']);
        else
            echo '-';
        echo '</td>' . PHP_EOL;
        echo '<td>';
        if ( ! empty($item['option_mp']))
            echo anchor($controller . '/ontologygroup/' . $item['parameter_id'] . '/' . $item['procedure_id'] . '/' . $item['pipeline_id'], $item['option_mp']);
        else
            echo '-';
        echo '</td>' . PHP_EOL;
        echo '</tr>' . PHP_EOL;
    }
    echo '</tbody></table>';
}
