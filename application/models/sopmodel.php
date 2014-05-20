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
 * SOP (Protocol) model
 */
class SOPModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable
{
    const TABLE = 'sop';
    const PRIMARY_KEY = 'sop_id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function getById($id)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    /**
    * @deprecated Was used when there was the potential for multiple SOPs per Procedure
    * @see SOPModel::getByProcedure()
    */
    public function getSOPsByProcedure($pid)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('procedure_id', $pid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function getByProcedure($pid)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->where('procedure_id', $pid)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getProcedureId($sopId)
    {
        $sop = $this->_getById($sopId);
        if(empty($sop))
            return array();
        return $sop['procedure_id'];
    }

    /**
    * A Protocol belongs to its' Procedure which has the deprecated flag so
    * this is checked to identify if the Protocol is deprecated
    * @param int $sopId
    * @return bool
    */
    public function hasDeprecatedParent($sopId)
    {
        $sop = $this->_getById($sopId);
        if ( ! empty($sop)) {
            return $this->hasDeprecatedParentByParentId($sop[ProcedureModel::PRIMARY_KEY]);
        }
        return FALSE;
    }
    
    public function hasDeprecatedParentByParentId($pid)
    {
        return $this->proceduremodel->isDeprecated($pid);
    }

    /**
    * A Protocol belongs to its' Procedure which has the internal flag so
    * this is checked to identify if the Protocol is internal
    * @param int $sopId
    * @return bool
    */
    public function hasInternalParent($sopId)
    {
        $sop = $this->_getById($sopId);
        if ( ! empty($sop)) {
            return $this->hasInternalParentByParentId($sop[ProcedureModel::PRIMARY_KEY]);
        }
        return false;
    }
	
    public function hasInternalParentByParentId($pid)
    {
        return $this->proceduremodel->isInternal($pid);
    }
    
    public function hasParentInBeta($sopId)
    {
        $sop = $this->_getById($sopId);
        if ( ! empty($sop)) {
            return $this->hasParentInBetaByParentId($sop[ProcedureModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasParentInBetaByParentId($pid)
    {
        return $this->proceduremodel->isInBeta($pid);
    }

    public function isDeleted($sopId)
    {
        $sop = $this->_getById($sopId);
        return (bool)@$sop['deleted'];
    }

    /**
    * @param int $id
    * @param int $procedureId Optional... not used currently
    * @param array $origin
    * @return bool
    */
    public function delete($id, $procedureId = null, array $origin = array())
    {
        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardDelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, TRUE, $origin);
    }

    public function undelete($id, array $origin)
    {
        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if($this->isDeleted($id))
            return (bool) $this->_setDeletedFlag($id, FALSE, $origin);

        if($this->config->item('delete_mode') == 'hard')
            return false; //(bool) $this->_hardUndelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, FALSE, $origin);
    }

    private function _setDeletedFlag($id, $deleted = TRUE, array $origin = array())
    {
        $currentRecord = $this->_getById($id);
        $deleted = ($deleted) ? 1 : 0;
        $arr = array(
            'deleted' => $deleted,
            'time_modified' => $this->config->item('timestamp'),
            'user_id' => User::getId()
        );
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $arr);
        $ar = $this->db->affected_rows();
        if ($ar) {
            $currentRecord = array_merge($currentRecord, $origin);
            $this->_log($id, $currentRecord, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }
        return $ar;
    }

    /**
    * When a procedure is soft un/deleted then its sop should also be soft un/deleted
    * @param int $procedureId
    * @param bool $deleted TRUE = mark as deleted, FALSE = mark as active
    * @param array $origin
    * @return int Affected rows
    * @todo get origin
    */
    public function setDeletedFlagByProcedure($procedureId, $deleted, array $origin)
    {
        $sop = $this->getByProcedure($procedureId);
        if ( ! empty($sop)) {
            return $this->_setDeletedFlag($sop[self::PRIMARY_KEY], $deleted, $origin);
        }
        return 1; //not all procedures have a protocol associated with them so return true
    }

    private function _hardDelete($id, $origin)
    {
        $currentRecord = $this->_getById($id);
        if (empty($currentRecord)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete SOP ' . $id . ' because it does not exist! Probably already deleted', 'sop', ImpressLogger::ACTION_DELETE);
            return 0;
        }
        //if the settings say active items shouldn't be deleted then check the sop doesn't have sections
        $this->load->model('sectionmodel');
        $sections = $this->sectionmodel->getSectionsBySOP($id);
        if( ! empty($sections) && $this->config->item('active_item_deletion') === FALSE){
            return 0;
        }
        //delete the sections of this sop if the child_deletion flag is on
        if($this->config->item('child_deletion')){
            foreach($sections AS $section)
                $this->sectionmodel->delete($section['section_id'], null, $origin);
        }

        //store a backup in deleted table
        $this->load->model('sopdeletedmodel');
        $iid = $this->sopdeletedmodel->insert($currentRecord);

        //delete
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $ar = $this->db->affected_rows();
            if ($ar && $this->config->item('delete_edited_protocol_pdf'))
                $this->deletePDF($id);
            if ($ar) {
                $currentRecord = array_merge($currentRecord, $origin);
                $this->_log($id, $currentRecord, ChangeLogger::ACTION_DELETE);
            }
            $iid = $ar;
        }

        return $iid;
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
		$this->load->helper('delete_array_values');
        $keys = delete_array_values($this->getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }

    public function getFields()
    {
        return array(
            self::PRIMARY_KEY, 'procedure_id', 'title', 'centre_id',
            'major_version', 'minor_version', 'time_modified', 'user_id',
            'weight','deleted'
        );
    }

    /**
    * @param array $arr hash of columns
    * @param string $action
    * @return int last sop insert id
    * @see SOPVersionAncestryModel::insert()
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        $arr['user_id'] = User::getId();
        $arr['time_modified'] = $this->config->item('timestamp');
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        $action = ($action == ChangeLogger::ACTION_CLONE) ? ChangeLogger::ACTION_CREATE : $action;
        if ($iid && ($action == ChangeLogger::ACTION_CREATE || $action == ChangeLogger::ACTION_UNDELETE))
            $this->_log($iid, $arr, $action);
        return $iid;
    }

    /**
    * Steps to updating (minor update)
    * - save a copy of the record to the backup table
    * - increment the version number
    * - save changes
    * @param int row id to update
    * @param array hash of columns
    * @param string $action
    * @return int rows affected
    */
    public function update($id, $arr, $action = ChangeLogger::ACTION_UPDATE)
    {
        $currentRecord = $this->_getById($id);
        if(empty($currentRecord))
            return 0;
        unset($currentRecord['deleted']);
        $this->db->insert('sop_oldedits', $currentRecord);

        $arr['minor_version'] = $currentRecord['minor_version'] + 1;
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();

        $ar = $this->_update($id, $arr);
        if ($ar && $this->config->item('delete_edited_protocol_pdf'))
            $this->deletePDF($id);
        if ($ar && $action == ChangeLogger::ACTION_UPDATE) {
            $currentRecord['procedure_id'] = $arr['procedure_id'];
            $currentRecord['pipeline_id'] = $arr['pipeline_id'];
            $this->_log($id, $currentRecord, $action);
        }
        return $ar;
    }

    private function _update($id, $arr)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        return $this->db->affected_rows();
    }

    /**
    * @param int $id Id of the row in the table that we want to look up revisions for
    * @return array All the revisions for the supplied id including the current active
    * version but this row in the array lacks an 'id' field, which is intentional as it
    * allows the differentiation between the current version and the old revisions
    * @todo Renamed this method - confusing
    */
    public function getRevisionsById($id)
    {
        $new = array($this->getById($id));
        $old = $this->db->from('sop_oldedits')
                        ->where('sop_id', $id)
                        ->order_by('minor_version', 'DESC')
                        ->get()
                        ->result_array();
        return array_merge($new, $old);
    }

    /**
    * This method sets an old revision to become the current version
    * @param int $id the id of the row to update
    * @param int $revId the id of the row in the oldedits table that we will
    * be changing the current version's values to
    * @param array $origin
    * @return int affected rows
    * @todo get origin
    */
    public function revert($id, $revId, $origin)
    {
        $current = $this->getById($id);
        $revision = $this->db->from('sop_oldedits')
                             ->where('id', $revId)
                             ->limit(1)
                             ->get()
                             ->row_array();
        unset($revision['id']);
        $revision['minor_version'] = $current['minor_version'];
        $revision['user_id'] = User::getId();
        $revision['time_modified'] = $this->config->item('timestamp');
        $ar = $this->update($id, $revision, ChangeLogger::ACTION_REVERT);
        if ($ar) {
            $revision = array_merge($revision, $origin);
            $this->_log($id, $revision, ChangeLogger::ACTION_REVERT);
        }
        return $ar;
    }

    /**
    * Copies SOPs from the old procedure to the new procedure
    * Note: This method works quite differently to the createNewVersion()
    * methods found in other models - it just made sense to overload the method name
    * @param int $oldProcedureId
    * @param int $newProcedureId
    * @return int|bool FALSE if failed, TRUE if the procedure supplied doesn't have a SOP, or it returns the id of the newly created SOP
    */
    public function createNewVersion($oldProcedureId, $newProcedureId)
    {
        //make a copy from the sop table and copy over sections too
        $this->load->model('sectionmodel');
        $old = $this->getByProcedure($oldProcedureId);
        if(empty($old))
            return TRUE; //if the procedure doesn't have a sop then just return
        $newproc = $this->proceduremodel->getById($newProcedureId);
        if( ! empty($newproc)){
            $new = $old;
            $new['procedure_id']  = $newproc['procedure_id'];
            $new['user_id']       = User::getId();
            $new['time_modified'] = $this->config->item('timestamp');
            $new['major_version'] = 1 + $new['major_version'];
            $new['minor_version'] = 0;
            $newSOPId = $this->insert($new, ChangeLogger::ACTION_VERSION);
            $sections = $this->sectionmodel->getSectionsBySOP($old['sop_id']);
            if($newSOPId !== FALSE && sizeof($sections) > 0){
                foreach($sections AS $oldsec){
                    $newsection = $oldsec;
                    $newsection['sop_id']        = $newSOPId;
                    $newsection['minor_version'] = 0;
                    $newsection['major_version'] = $newsection['major_version'] + 1;
                    $newsection['user_id']       = User::getId();
                    $newsection['time_modified'] = $this->config->item('timestamp');
                    $this->sectionmodel->insert($newsection, ChangeLogger::ACTION_VERSION);
                }
            }
            return $newSOPId;
        }
        return FALSE;
    }

    /**
    * @see ProcedureModel::cloneProcedure()
    * @param array $source has procedure_id field
    * @param array $destination has procedure_id field
    * @return bool
    */
    public function cloneByProcedure(array $source, array $destination)
    {
        $origsop = $this->getByProcedure($source['procedure_id']);
        //if the Procedure has no Protocol then just go back
        if (empty($origsop))
            return true;
        $newsop = $origsop;
        $source[self::PRIMARY_KEY] = $origsop[self::PRIMARY_KEY];
        unset($newsop[self::PRIMARY_KEY]);
        $newsop['major_version'] = 1;
        $newsop['minor_version'] = 0;
        $newsop['time_modified'] = $this->config->item('timestamp');
        $newsop['user_id'] = User::getId();
        $newsop['deleted'] = 0;
        $newsop['centre_id'] = null;
        $newsop['pipeline_id'] = $destination['pipeline_id'];
        $newsop['procedure_id'] = $destination['procedure_id'];
        $newSopId = $this->insert($newsop, ChangeLogger::ACTION_CLONE);
        if ($newSopId) {
            $destination[self::PRIMARY_KEY] = $newSopId;
            $this->load->model('sectionmodel');
            $success = $this->sectionmodel->cloneByProcedure($source, $destination);
            if ( ! $success) {
                ImpressLogger::log(ImpressLogger::ERROR, 'Failed in section copying', 'sop', ImpressLogger::ACTION_CLONE);
                return false;
            }
        }
        return $newSopId;
    }

    /**
    * @deprecated
    * @alias
    * @see SOPModel::createNewVersion()
    */
    public function insertNewVersion($oldProcedureId, $newProcedureId)
    {
        return $this->createNewVersion($oldProcedureId, $newProcedureId);
    }

    /**
    * @param int $sopId
    * @return bool success Returns TRUE if it deletes the PDF or if file doesn't exist
    * Returns FALSE if the SOP or Procedure is missing or it was unable to delete the PDF file
    */
    public function deletePDF($sopId)
    {
        $sop = $this->_getById((int)$sopId);
        if(empty($sop))
            return FALSE;

        $proc = $this->proceduremodel->getById($sop['procedure_id']);
        if(empty($proc))
            return FALSE;

        $file = $this->config->item('pdfpath') . $proc['procedure_key'] . '.pdf';
        if(file_exists($file)){
            //save a copy of the SOP to be deleted
            $savedCopy = $this->config->item('deletedpdfpath') . $proc['procedure_key'] . '_' . uniqid() . '.pdf';
            @copy($file, $savedCopy);
            //delete the file
            if(@unlink($file) === FALSE){
                ImpressLogger::log(ImpressLogger::ERROR, 'Could not delete PDF ' . basename($file), 'Protocol PDF', ImpressLogger::ACTION_DELETE);
                return FALSE;
            }else{
                ImpressLogger::log(ImpressLogger::INFO, 'Successfully deleted ' . basename($file) . '; saved a copy as ' . basename($savedCopy), 'Protocol PDF', ImpressLogger::ACTION_DELETE);
                return TRUE;
            }
        }

        return TRUE;
    }

    /**
    * When a parameter is added or deleted then it will not match what is in the PDF document
    * so it needs to be deleted and the user needs to upload a current PDF
    * @param int $procedureId
    * @return bool
    */
    public function deletePDFByProcedure($procedureId)
    {
        $sop = $this->getByProcedure($procedureId);
        if ( ! empty($sop)) {
            return $this->deletePDF($sop[self::PRIMARY_KEY]);
        }
        return TRUE;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about origin of sop being logged
        $procedure = $this->proceduremodel->getById(@$arr[ProcedureModel::PRIMARY_KEY]);
        $pipeline = $this->pipelinemodel->getById(@$arr[PipelineModel::PRIMARY_KEY]);
        $currentRecord = $this->_getById($id);

        //preprare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Protocol (' . $id . ') ' . @$currentRecord['title']
                     . ' for Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') '
                     . @$pipeline['name'] . '. ';
            foreach (array('title','centre_id') AS $field) {
                if ($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . $arr[$field] . ' to ' . $currentRecord[$field] . '. ';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Protocol (' . $id . ') ' . @$arr['title']
                     . ' for Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') '. @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Protocol (' . $id . ') ' . @$arr['title'] . ' of '
                     . 'Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Protocol (' . $id . ') ' . @$arr['title'] . ' of '
                     . 'Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_REVERT) {
            $message = 'Reverted current Protocol (' . $id . ') ' . $arr['title']
                     . ' to revision ' . @$currentRecord['major_version'] . '.'
                     . @$currentRecord['minor_version'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['procedure_key'])) ? @$procedure['procedure_key'] : @$currentRecord['procedure_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Protocol',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => null,
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$pipeline['internal'] || @$procedure['internal'] || $this->hasInternalParentByParentId(@$procedure[ProcedureModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$arr[ProcedureModel::PRIMARY_KEY]))
            )
        );
    }
}
