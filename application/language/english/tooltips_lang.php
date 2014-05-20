<?php

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

$lang['pipeline_stub'] = 'Please enter a suitable stub for this Pipeline. Enter 3 - 8 Uppercase Letters, e.g. IMPC';
$lang['pipeline_name'] = 'Please choose a suitable name for this Pipeline, e.g. JAX Pipeline';
$lang['description'] = 'Entering a description is entirely optional and you do not need to do it';
$lang['is_impc'] = 'Is this Pipeline part of the IMPC Project?';
$lang['ilar_code'] = 'Every animal research centre has been assigned a unique ILAR code. Enter this centre\'s code here. See http://ilarlabcode.nas.edu/search_lc_nodep.php';
$lang['deprecated'] = 'An item that is marked as deprecated cannot/should-not be modified or removed and should remain ununchanged. IMPReSS will not allow you to modify a deprecated item when the "modify_deprecated" setting is set to "off" and it is not permitted to modify items with deprecated keys regardless of the settings';
$lang['visible'] = 'If this field is unchecked then this item will only be visible to logged-in users, or any user accessing the database using webservices';
$lang['internal'] = 'An item marked as internal will only display on the internal server';
$lang['3P_active'] = 'This flag has no functional purpose save to indicate an item as being actively used';
$lang['procedure_name'] = 'Please choose a suitable name for this Procedure, e.g. Hole Board';
$lang['procedure_type'] = 'There are different types of Procedures so select the category that your Procedure falls into. Please note that once you select a Type it cannot be changed. If your Procedure is not in the list or the category is not entirely suitable then you need to go back and create a new one';
$lang['mandatory'] = 'If this field is checked then it means this Procedure must be submitted when data for this Pipeline is sent to the DCC';
$lang['week'] = 'You can choose the week this Procedure is carried out in. If the Procedure is done over several weeks then select "Unrestricted". If the week of your Procedure is not in the list then you need to go back and create a new one';
$lang['min_females'] = 'Minimum number of female animals required for a test to be valid - or if sex doesn\'t matter then leave it blank';
$lang['min_males'] = 'Minimum number of male animals required for a test to be valid - or if sex doesn\'t matter then leave it blank';
$lang['min_animals'] = 'Either fill this field in or fill in the female/male fields, but not both. This field is for tests that require a minimum number of animals of which the sex is irrelevant. Otherwise leave it blank';
$lang['level'] = 'Procedures are usually used at the experiment level but there are exceptions such as the Housing and Husbandry Procedure which is at the housing level and Fertility and Viability which are at the line level';
$lang['relationship_relationship'] = "'Equivalent' means it is effectvely the same. 'Convertible' means some simple extra steps need to be taken to make the data comparable. 'Similar' indicates that a similar thing is being measured but the results are different. 'Different' means it is very different. 'Converse' means it is measuring the opposite or converse effect";
$lang['relationship_description'] = 'Please explain what relationship the item on the left has with the item on the right';
$lang['nv_relation'] = 'Please determine how this new item will be related to the old one. ' . $lang['relationship_relationship'];
$lang['nv_relationdescription'] = 'Please explain how this new item will be related to the old one. Although this field is optional, it is recommended to explain your intentions';
$lang['nv_pipeline'] = 'Please select where you would like the new version to go';
$lang['nv_procedure'] = $lang['nv_pipeline'];
$lang['nvuseoldpipelinekey'] = 'If you are creating a new version of a Procedure, e.g. IMPC_FER_001, and you want to place this into the JAX_001 Pipeline, if you keep this field checked then the new version will be put into the JAX Pipeline as IMPC_FER_002. Unticked it will insert it as JAX_FER_001, but the Parameters will still be IMPC Parameters';
$lang['parameter_type'] = 'Please select the appropriate Parameter Type. In most cases the Parameter is a Simple Parameter or Procedure Metadata but there are exceptions';
$lang['parameter_type_for_increment'] = 'Please select the appropriate Type for the Incremental Parameter. In most cases it will be ' . EParamType::SERIES;
$lang['parameter_name'] = 'Please choose a suitable name for this Parameter of what is being measured, e.g. Body Weight';
$lang['required'] = 'If this field is checked then data must be submitted for this Parameter when the Procedure is uploaded to the DCC';
$lang['requirednewprocedure'] = 'You need to go back and create a new version of the Procedure in order to create a new required Parameter';
$lang['mandatorynewpipeline'] = 'You need to go back and create a new version of the Pipeline in order to create a new mandatory Procedure';
$lang['important'] = 'If this field is checked then it indicates that data will be split by this Parameter\'s value. For example, if the Parameter name was \'Mouse fasted\' and the Options of \'Yes\' and \'No\' are given, then the data of this Procedure would be split by these two conditions';
$lang['meta'] = 'A Metadata Parameter is not for collecting experimental values but for collecting information about the conditions the experiment was done under or about the equipment used. Equipment Manufacturer, Experimenter ID and Period Of Fasting are examples of Metadata Parameters. Check this field if the Parameter is a Metadata Parameter';
$lang['derived'] = 'This field should be checked if this Parameter\'s value is calculated using a formula involving one or more other Parameters. Please also fill in the Derivation field below showing the formula';
$lang['media'] = 'If an image or video clip is expected to be uploaded for this Parameter then this field should be checked';
$lang['annotation'] = 'If this Parameter has Ontologies associated with it and it is intended that these be used for high-throughput phenotype ontology annotation then this field should be checked';
$lang['derivation'] = 'If this is a derived Parameter then please write down the formula of it\'s derivation, e.g. BMI = Body Weight / Height^2';
$lang['unit'] = 'The unit this Parameter is measured in. Data can be submitted to the DCC without a unit but if it is supplied it should match what is chosen here. If the unit of your Parameter is not in the list then you need to go back and create a new one';
$lang['qc_check'] = 'Check this field if you want the upper and/or lower bounds of this Parameter to be checked and validated against when data is being sent to the DCC';
$lang['qc_min'] = 'This is the minimum bounds of a realistic value for the Parameter. Enter a real number or leave it blank';
$lang['qc_max'] = 'This is the maximum bounds of a realistic value for the Parameter. Enter a real number or leave it blank';
$lang['qc_notes'] = 'If an explanation explanation of what QC checks need to be undertaken by a data scientist beyond checking the bounds, then please explain them here';
$lang['value_type'] = 'Parameter data should be submitted in one of these data types. If in doubt, TEXT is usually the correct one';
$lang['graph_type'] = 'This field determines how the Parameter data should be displayed on the DCC Website. Please choose the appropriate Graph Type or leave it blank';
$lang['data_analysis_notes'] = 'Should there be any special instructions about how a data scientist should analyze the data for this Parameter, then please explain it here';
$lang['proctype_name'] = 'The general name of the Type of Procedure, e.g. Hole-Board';
$lang['proctype_key'] = 'The Key needs to be 3 Uppercase Letters. Note that once set, the Key cannot be altered. Preferably the Key should be representative of the Procedure name, e.g. XRY for the X-Ray Procedure';
$lang['week_label'] = 'E.g. Week 23';
$lang['week_number'] = 'E.g. 23 or 13.5. Note that once set, this cannot be changed';
$lang['pdf'] = 'Upload a PDF file if you want to have it display instead of one being generated automatically from the Protocol text';
$lang['protocol_title'] = 'Please choose a suitable title to display as the heading for the Protocol';
$lang['section_title'] = 'Please select the correct Section Title. Please note the Section Title cannot be changed once a section is created';
$lang['weight'] = 'Enter a number here to determine the order items are displayed in or you can just leave it blank. Smaller numbers make a list item go to the top and larger numbers to the bottom';
$lang['increment_string'] = 'For increments where an index can be defined, enter a suitable title for the index of the increment, e.g. 0 minutes. Otherwise, leave it blank. Take extra care with defined increment names as changing them may cause submissions to the DCC to become invalid and a new version of the Parameter/Procedure will need to be created';
$lang['increment_min'] = 'For Repeat-type increments or DateTime increments, enter an integer number indicating the minimum number of repeat readings that are needed for this Parameter in order there be a sufficient number collected. Or leave the field blank if there is no minimum';
$lang['increment_active'] = 'Inactive items will not display on the IMPReSS website but can still be read via Webservices';
$lang['increment_unit'] = 'You can select a value or leave it blank. The unit is the title of the x axis when this data is graphed';
$lang['increment_type'] = 'Specify the Increment Type';
$lang['option_name'] = 'Enter a suitable title for this option. Take extra care with Option names as changing them may cause submissions to the DCC to become invalid and a new version of the Parameter/Procedure will need to be created';
$lang['option_parent'] = 'Leave this field blank or give the ID of another Parameter Option to have it present that Option in the Webservices. E.g. If I have given this Option a name/title like \'blotchy\' but would like to additionally present it as \'not as expected\' then I just put the id of a related Parameter Option here and it presents its Name';
$lang['option_default'] = 'The default Option is the Option that usually gets picked from a list, e.g. As Expected';
$lang['option_active'] = $lang['increment_active'];
$lang['ont_option'] = 'In most cases you should leave this blank, but if a particular phenotype occurs associated with one of the Options of this Parameter then select it';
$lang['ont_increment'] = 'In most cases you should leave this blank, but if a particular phenotype occurs associated with one of the Increments of this Parameter then select it';
$lang['ont_sex'] = 'In most cases phenotypes can be associated with both genders but if a particular phenotype can only be used to describe a male or female seperately, then you should select the sex';
$lang['ont_selection_outcome'] = 'Indicates what type of experimental outcome is associated with the phenotype you are defining. e.g. An INCREASED selection outcome for body weight should get the Increased Body Weight phenotype';
$lang['mp_term'] = 'E.g. Increased Grip Strength';
$lang['mp_id'] = 'E.g. MP:0010052. EMAP and MA terms are also allowed';
$lang['entity_term'] = 'E.g. Heart / Glucose / Adult Mouse';
$lang['entity_id'] = 'E.g. MA:0000072 / CHEBI:17234 / MA:0002405';
$lang['quality_term'] = 'E.g. Color / Increased Size / Decreased Concentration';
$lang['quality_id'] = 'Only PATO-ontology IDs should be used E.g. PATO:0000020 / PATO:0000586 / PATO:0001163';
$lang['protocol_section_title'] = 'Enter a unique section title, e.g. References';
$lang['ontology_group_name'] = 'Enter a globally unique name for this Ontology group';
$lang['ontology_term'] = 'E.g. Decreased size';
$lang['ontology_id'] = 'E.g. PATO:0000587';
