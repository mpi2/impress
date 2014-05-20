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

//the pdf folder path and the deletedpdfpath where all the SOP documents are stored
$config['pdfpath'] = APPPATH . 'views/pdfs/';
$config['deletedpdfpath'] = APPPATH . 'views/deletedpdfs/';

//the folder path to the XML files that can be imported into IMPReSS
$config['importxmlpath'] = FCPATH . 'assets/xmlfiles/';

//the URL where the latest MP OBO file is downloaded from and what it will be called when saved in the cache directory
$config['mpurl'] = 'http://www.berkeleybop.org/ontologies/mp.obo'; //'https://phenotype-ontologies.googlecode.com/svn/trunk/src/ontology/mp.obo';
$config['mpfile'] = 'mp.obo';
$config['mpfilerefreshinterval'] = 60*60*24*7; //download new file at least every 7 days

//with lists of ontology options we have an expand/collapse toggle link but if the
//list is huge, bigger than ontologyoptionlistlimit, we display a link to a page
$config['ontologyoptionlistlimit'] = 50;

//identify what server impress is running on and specify server profile label
if (false !== strpos(@$_SERVER['HTTP_HOST'], 'localhost')) {
    $config['server'] = 'internal';
} else if (false !== strpos(@$_SERVER['HTTP_HOST'], 'publicdevelopment')) {
    $config['server'] = 'beta';
} else if (false !== strpos(@$_SERVER['HTTP_HOST'], 'beta')) {
    $config['server'] = 'beta';
} else if (false !== strpos(@$_SERVER['HTTP_HOST'], 'internal')) {
    $config['server'] = 'internal';
} else if (false !== strpos(@$_SERVER['HTTP_HOST'], 'impress')) {
    $config['server'] = 'live';
} else {
    $config['server'] = 'internal';
}

//debug
//$config['server'] = 'beta';

//enable analytics
$config['analytics'] = TRUE;

//analytics code inserted into <head>
$config['analyticshead'] = <<<XYZ

<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-23433997-1']);
  //_gaq.push(['_setDomainName', '.mousephenotype.org']); //subdomain tracking
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>

XYZ;

//timestamp
date_default_timezone_set('Europe/London');
$config['timestamp'] = date('Y-m-d H:i:s');
