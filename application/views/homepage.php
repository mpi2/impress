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

<h1>Welcome to IMPReSS</h1>
<p style="text-align:justify">
IMPReSS (International Mouse Phenotyping Resource of Standardised Screens), the successor of <a href="http://empress.har.mrc.ac.uk/" target="_blank">EMPReSS</a>, 
contains standardized phenotyping protocols which are essential for the characterization of mouse phenotypes. IMPReSS contains definitions of the phenotyping 
Pipelines and mandatory and optional Procedures and Parameters carried out and data collected by international mouse clinics following the protocols defined. 
This allows data to be comparable and shareable and ontological annotations permit interspecies comparison which may help in the identification of phenotypic 
mouse-models of human diseases.</p>

<h2>The Adult and Embryonic Phenotype Pipeline</h2>
<p style="text-align:justify">
The IMPC (<a href="http://www.mousephenotype.org/" target="_blank">International Mouse Phenotyping Consortium</a>) core pipeline describes the phenotype pipeline that has 
been agreed by the research institutions. The pipeline is currently in development. The protocols in the core IMPC Pipeline are currently being developed by the IMPC phenotyping 
working groups and the current versions on this site are still <b><i>under final review</i></b>. The phenotyping working groups are working closely with the data wranglers to complete 
an agreed first version. Updates on the progress of this will be available through IMPReSS.
</p>

<p align="center">
<img src="<?php echo site_url(); ?>images/pipeline_horizontal_vE8.gif" width="1020" height="814" style="margin:10px 0;" border="0" usemap="#meowmap" alt="The IMPC Pipeline" />
<map name="meowmap" id="meowmap">
<!-- #$-:Image map file created by GIMP Image Map plug-in -->
<!-- #$-:GIMP Image Map plug-in by Maurits Rijk -->
<!-- #$-:Please do not edit lines starting with "#$" -->
<!-- #$VERSION:2.3 -->
<!-- Fertility & Viability -->
<area shape="rect" coords="075,065,210,090" href="<?php echo site_url(); ?>protocol/105/7" alt="Fertility" />
<area shape="rect" coords="220,065,360,090" href="<?php echo site_url(); ?>protocol/154/7" alt="Viability" />
<!-- E9.5 -->
<area shape="rect" coords="640,125,825,150" href="<?php echo site_url(); ?>protocol/189/7" alt="Gross Morphology Embryo" />
<area shape="rect" coords="830,125,1010,150" href="<?php echo site_url(); ?>protocol/190/7" alt="Gross Morphology Placenta" />
<area shape="rect" coords="830,155,905,180" href="<?php echo site_url(); ?>protocol/177/7" alt="Viability" />
<!-- E12.5 -->
<area shape="rect" coords="055,280,230,300" href="<?php echo site_url(); ?>protocol/172/7" alt="Embryo LacZ" />
<area shape="rect" coords="335,280,425,300" href="<?php echo site_url(); ?>protocol/172/7" alt="Embryo LacZ" />
<area shape="rect" coords="335,220,515,245" href="<?php echo site_url(); ?>protocol/191/7" alt="Gross Morphology Embryo" />
<area shape="rect" coords="335,250,515,270" href="<?php echo site_url(); ?>protocol/194/7" alt="Gross Morphology Placenta" />
<area shape="rect" coords="435,280,515,300" href="<?php echo site_url(); ?>protocol/178/7" alt="Viability" />
<!-- E14.5-E15.5 -->
<area shape="rect" coords="640,250,825,275" href="<?php echo site_url(); ?>protocol/192/7" alt="Gross Morphology Embryo" />
<area shape="rect" coords="830,250,1010,275" href="<?php echo site_url(); ?>protocol/195/7" alt="Gross Morphology Placenta" />
<area shape="rect" coords="830,280,905,300" href="<?php echo site_url(); ?>protocol/179/7" alt="Viability" />
<!-- E18.5 -->
<area shape="rect" coords="640,345,825,365" href="<?php echo site_url(); ?>protocol/193/7" alt="Gross Morphology Embryo" />
<area shape="rect" coords="830,345,1010,365" href="<?php echo site_url(); ?>protocol/196/7" alt="Gross Morphology Placenta" />
<area shape="rect" coords="830,375,905,395" href="<?php echo site_url(); ?>protocol/180/7" alt="Viability" />
<!-- Weight Curve -->
<area shape="rect" coords="055,480,1013,500" href="<?php echo site_url(); ?>protocol/103/7" alt="Body Weight" />
<!-- Week 9 -->
<area shape="rect" coords="060,545,145,570" href="<?php echo site_url(); ?>protocol/81/7" alt="Open Field" />
<area shape="rect" coords="060,575,105,595" href="<?php echo site_url(); ?>protocol/155/7" alt="CSD" />
<area shape="rect" coords="060,605,155,625" href="<?php echo site_url(); ?>protocol/83/7" alt="Grip Strength" />
<!-- Week 10 -->
<area shape="rect" coords="181,605,318,625" href="<?php echo site_url(); ?>protocol/176/7" alt="Acoustic Startle" />
<!-- Week 11 -->
<area shape="rect" coords="330,605,414,625" href="<?php echo site_url(); ?>protocol/153/7" alt="Calorimetry" />
<!-- Week 12 -->
<area shape="rect" coords="426,575,497,595" href="<?php echo site_url(); ?>protocol/109/7" alt="Echo" />
<area shape="rect" coords="426,605,497,625" href="<?php echo site_url(); ?>protocol/108/7" alt="ECG" />
<!-- Week 13 -->
<area shape="rect" coords="511,540,666,580" href="<?php echo site_url(); ?>protocol/88/7" alt="Challenge" />
<area shape="rect" coords="511,590,666,625" href="<?php echo site_url(); ?>protocol/87/7" alt="IPGTT" />
<!-- Week 14 -->
<area shape="rect" coords="683,545,734,570" href="<?php echo site_url(); ?>protocol/91/7" alt="X-ray" />
<area shape="rect" coords="684,575,888,595" href="<?php echo site_url(); ?>protocol/149/7" alt="ABR" />
<area shape="rect" coords="685,605,875,625" href="<?php echo site_url(); ?>protocol/90/7" alt="Body Composition" />
<!-- Week 15 -->
<area shape="rect" coords="899,585,1012,625" href="<?php echo site_url(); ?>protocol/94/7" alt="Eye Morphology" />
<!-- Week 16 -->
<area shape="rect" coords="055,710,150,730" href="<?php echo site_url(); ?>protocol/150/7" alt="Hematology" />
<area shape="rect" coords="055,740,150,760" href="<?php echo site_url(); ?>protocol/107/7" alt="Adult LacZ" />
<area shape="rect" coords="162,710,260,750" href="<?php echo site_url(); ?>protocol/182/7" alt="Clinical Chemistry" />
<area shape="rect" coords="270,710,367,750" href="<?php echo site_url(); ?>protocol/183/7" alt="Insulin Blood Level" />
<area shape="rect" coords="380,710,490,750" href="<?php echo site_url(); ?>protocol/174/7" alt="Immunophenotyping" />
<area shape="rect" coords="505,710,600,740" href="<?php echo site_url(); ?>protocol/100/7" alt="Heart Weight" />
<area shape="rect" coords="612,710,762,750" href="<?php echo site_url(); ?>protocol/181/7" alt="Gross Pathology" />
<area shape="rect" coords="775,710,877,770" href="<?php echo site_url(); ?>protocol/101/7" alt="Tissue Embedding" />
<area shape="rect" coords="885,710,1010,770" href="<?php echo site_url(); ?>protocol/102/7" alt="Histopathology" />
</map>
</p>
