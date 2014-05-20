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

$url = 'https://www.mousephenotype.org/my-impc/newsletters';
$email = 'mouse-helpdesk@ebi.ac.uk'; //helpdesk@mousephenotype.org
?>

<p>You can contact our helpdesk by emailing: <?php echo safe_mailto($email); ?>.</p>

<p>You can also sign up to our mailing list on
<?php echo anchor($url, 'mousephenotype.org', array('target'=>'_blank')); ?>.
</p>

<p>Sign up to the mailing list if you want to receive updates and be informed
about any changes that are going to be made to IMPReSS including any
adding/removing/modification of Procedures, Parameters or associated Options,
Increments and Ontologies.
<?php echo anchor($url, 'Click here', array('target'=>'_blank')); ?>
 to be taken to the registration form where you can sign up.</p>
