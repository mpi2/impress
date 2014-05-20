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
 * @param array $invalidParams Result Set
 * @param array $invalidMPs
 * @param array $obsoleteParams Result Set
 * @param array $obsoleteMPs
 * @param array $updatedParams Result Set
 * @param array $updatedTerms
 * @param array $updatedMPs
 * @param string $controller
 */
?>

<fieldset><legend>IMPReSS MP Ontology Database Check</legend>

<h2>IMPReSS MP Ontology Database Check</h2>

<h3>Obsolete MPs</h3>

<?php $this->load->view('admin/invobsmplist', array(
    'items' => $obsoleteParams,
    'mps' => $obsoleteMPs,
    'displayTermsList' => false,
    'controller' => $controller
)); ?>

<h3>Invalid MPs</h3>

<?php $this->load->view('admin/invobsmplist', array(
    'items' => $invalidParams,
    'mps' => $invalidMPs,
    'displayTermsList' => false,
    'controller' => $controller
)); ?>

<h3>Updated Terms</h3>

<?php $this->load->view('admin/invobsmplist', array(
    'items' => $updatedParams,
    'terms' => $updatedTerms,
    'mps' => $updatedMPs,    
    'displayTermsList' => true,
    'controller' => $controller
)); ?>

</fieldset>
