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

?>

<h1>Search Ontologies in IMPReSS</h1>
<div id='ontsearch'>
<p><noscript>You must enable JavaScript in your browser for this page to work</noscript></p>
<form method="get" action="#" id="ontsearchform">
<label for="searchterm">Enter Ontology keyword or Id</label>
<input type="text" name="searchterm" id="ontsearchbox" class="ui-autocomplete-input" maxlength="100" size="60" pattern=".+" placeholder="e.g. MP:0001191 or a keyword like 'strength'">
<input type="hidden" name="searchterm_id" id="searchterm_id">
<input type="submit" name="searchsubmit" id="ontsearchsubmit" value="Search">
</form>
</div>
<p id="ontsearchblurb"></p>
<div id="ontsearchresults"></div>
