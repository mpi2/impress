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
 * Options: soft|hard
 *
 * There are two delete modes in IMPReSS - "soft" and "hard".
 * If an item has a column in the table called 'deleted' then it is possible to mark the record as deleted by setting
 * it to 1. It remains in the table unchanged save for the deleted flag being set to 1.
 * With hard delete, the record is physically removed from the table - if the table has a corresponding {name}_deleted
 * table then the deleted record is saved in there before being deleted from the original table. If there is no
 * {name}_deleted table then the record is permanently deleted.
 * One good use for a hard delete it to remove a record so as to allow a newly inserted record to replace it, like
 * when re-importing a batch of parameters we want to generate keys that start from 001 rather than continue on from
 * the last soft deleted parameter key number
 */
$inicomments['delete_mode'] = 'delete_mode can be "soft" or "hard"';
$inidefaults['delete_mode'] = 'soft';

/**
 * Options: on | off
 *
 * Version Triggering is the automatic behaviour of creating new versions of procedures and parameters and triggering
 * the creation of a new version of the said item or its parent or/and its grandparent. For example, if I delete a
 * required Parameter then by default it will trigger the creation of a new Procedure which, if the Procedure is
 * mandatory, will trigger the creation of a new Pipeline version.
 * This behaviour is there to make sure that changes to an item will not cause future data submissions to be invalidated
 * but it is not ideal when everyone has agreed to the modification and wants the item to remain as version 1 and have
 * the changes applied to it. Then it is a good idea to switch off version triggering
 */
$inicomments['version_triggering'] = 'version_triggering can be "on" or "off"';
$inidefaults['version_triggering'] = 'on';

/**
 * Options: off | on
 *
 * Active items are items that are still in use in the database. By default an item such as a section title should not
 * be deletable if it is used in any sections but sometimes there is a need to delete the item regardless of whether
 * anyone is using it or not. After deleting such an item some raw SQL code needs to be written to resolve the issues
 * caused by deleting an active item
 */
$inicomments['active_item_deletion'] = 'active_item_deletion can be "off" or "on"';
$inidefaults['active_item_deletion'] = 'off';

/**
 * Options: on | off
 *
 * This setting only affects deletion when delete_mode=hard.
 * If a parent item has children then the default behavior is to try to just delete the parent item and not delete its
 * children. For example, if I delete a Parameter I may not want to delete its option... But usually I do. If I want
 * the children to be deleted (they are backed-up in a (name)_deleted table usually) before the parent item is deleted
 * then I would set this flag to "on". There is a risk of losing data if this setting is turned off when delete_mode is
 * set to hard.
 * If you change the delete mode to hard then you should also enable active_item_deletion and child_deletion
 */
$inicomments['child_deletion'] = 'child_deletion can be "on" or "off"';
$inidefaults['child_deletion'] = 'on';

/**
 * Options: off | on
 *
 * Deprecated items should not be modified but if there is a need to edit a deprecated item then change this setting
 */
$inicomments['modify_deprecated'] = 'modify_deprecated can be "off" or "on"';
$inidefaults['modify_deprecated'] = 'off';

/**
 * Options: on | off
 *
 * If a protocol has a static PDF file uploaded for it and then later on the text of the protocol is modified, then by
 * default this setting being "on" ensures the PDF gets deleted (moved to the $config['deletedpdfpath'] directory) but
 * you can switch this behavior off if it doesn't suit you. Other things that can cause the deletion of a PDF include
 * the adding or deleting of parameters
 */
$inicomments['delete_edited_protocol_pdf'] = 'delete_edited_protocol_pdf can be "on" or "off"';
$inidefaults['delete_edited_protocol_pdf'] = 'on';

/**
 * Options: on | off
 *
 * If you want to stop change logs being written to the change_logs table then set change_logging to off
 */
$inicomments['change_logging'] = 'change_logging can be "on" or "off"';
$inidefaults['change_logging'] = 'on';


/**
 * The external INI file placed in the root (www or public_html) directory OVERRIDES the default values above, but if an
 * invalid key is given in the INI file or a key is missing then the default values above take over
 */
$keys = array_keys($inidefaults);
sort($keys);

if ( ! file_exists('./impress.ini')) {
    $fh = @fopen('./impress.ini', 'w+');
    if ($fh) {
        foreach ($keys as $key) {
            fwrite($fh, '; ' . @$inicomments[$key] . PHP_EOL);
            fwrite($fh, $key . '=' . $inidefaults[$key] . PHP_EOL);
        }
        fclose($fh);
    }
}

/**
 * Function interprets different ini values as TRUE/FALSE boolean or a string
 * @param mixed $val
 * @return mixed $val interpreted
 */
function getIniValue($val)
{
    $mval = strtolower(trim($val, '\'" '));
    switch($mval){
        case '1':
        case 'on':
        case 'true':
        case 't':
        case 'yes':
        case 'y':
            return true;
            break;
        case '':
        case '0':
        case 'no':
        case 'n':
        case 'false':
        case 'f':
        case 'off':
        case 'none':
            return false;
            break;
        case 'hard':
            return 'hard';
            break;
        case 'soft':
            return 'soft';
            break;
        default:
            return $val;
    }
}

$inifile = @parse_ini_file('impress.ini', null, INI_SCANNER_RAW);
$ini = array_merge($inidefaults, (array)$inifile);

/**
 * Process the values given in the ini file or defaults and convert them to
 * TRUE/FALSE values or standard strings soft/hard
 */
foreach ($keys as $key) {
    $ini[$key] = getIniValue($ini[$key]);
    if ($key == 'delete_mode' && ($ini[$key] == 'soft' || $ini[$key] == 'hard')) {
        $config[$key] = $ini[$key];
    } else if (is_bool($ini[$key])) {
        $config[$key] = $ini[$key];
    } else {
        $config[$key] = getIniValue($inidefaults[$key]);
    }
    // log_message('info', $key . ' ' . $config[$key]);
}


/**
 * If there is an override setting in the database then use that instead of the
 * default setting or the one set in the ini file...
 * Because the db settings have not been run by this stage this bit of code needs
 * to be run at a later stage and is only relevant to admin, so moved to some of
 * IMPReSS's controller __construct() methods and a specific ajax method
 */
//$ci =& get_instance();
//$ci->load->model('overridesettingsmodel', true);
//$ci->overridesettingsmodel->updateRunningConfig();
