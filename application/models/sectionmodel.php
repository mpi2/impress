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
 * SOP Section model
 */
class SectionModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable
{
    const TABLE = 'section';
    const PRIMARY_KEY = 'section_id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('section_title_id')
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

    public function _getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    /**
    * @param int $sopId
    * @return array
    */
    public function getSectionsBySOP($sopId)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        return $this->db->from(self::TABLE)
                        ->join('section_title', self::TABLE . '.section_title_id = section_title.id')
                        ->where('sop_id', $sopId)
                        ->order_by('section_title.weight')
                        ->order_by(self::TABLE . '.weight')
                        ->get()
                        ->result_array();
    }

    /**
    * @alias SectionModel::getSectionsBySOP
    */
    public function getBySOP($sopId)
    {
        return $this->getSectionsBySOP($sopId);
    }

    /**
    * @param int $tid Title id
    * @return int
    */
    public function getNumSectionsWithTitle($tid)
    {
        return (int) $this->db->from(self::TABLE)
                              ->where('section_title_id', $tid)
                              ->count_all_results();
    }

    /**
    * A Protocol belongs to its' Procedure which has the deprecated flag...
    * A Section belongs to a Protocol. In order to check if a section is
    * deprecated then it's Protocol (SOP) is asked if its' Procedure is
    * deprecated
    * @param int $sectionId
    * @return bool
    */
    public function hasDeprecatedParent($sectionId)
    {
        $section = $this->_getById($sectionId);
        if ( ! empty($section)) {
            $this->load->model('sopmodel');
            return $this->hasDeprecatedParentByParentId($section[SOPModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasDeprecatedParentByParentId($sopId)
    {
        $this->load->model('sopmodel');
        return $this->sopmodel->hasDeprecatedParent($sopId);
    }

    /**
    * A Protocol belongs to its' Procedure which has the internal flag...
    * A Section belongs to a Protocol. In order to check if a section is
    * internal then it's Protocol (SOP) is asked if its' Procedure is
    * internal
    * @param int $sectionId
    * @return bool
    */
    public function hasInternalParent($sectionId)
    {
        $section = $this->_getById($sectionId);
        if ( ! empty($section)) {
            $this->load->model('sopmodel');
            return $this->hasInternalParentByParentId($section[SOPModel::PRIMARY_KEY]);
        }
        return false;
    }
	
    public function hasInternalParentByParentId($pid)
    {
        $this->load->model('sopmodel');
        return $this->sopmodel->hasInternalParent($pid);
    }
    
    public function hasParentInBeta($sectionId)
    {
        $section = $this->_getById($sectionId);
        if ( ! empty($section)) {
            $this->load->model('sopmodel');
            return $this->hasParentInBetaByParentId($section[SOPModel::PRIMARY_KEY]);
        }
        return false;
    }
    
    public function hasParentInBetaByParentId($sopId)
    {
        $this->load->model('sopmodel');
        return $this->sopmodel->hasParentInBeta($sopId);
    }

    public function isDeleted($sectionId)
    {
        $section = $this->_getById($sectionId);
        return (bool)@$section['deleted'];
    }

    /**
    * @param int $id
    * @param bool $harddelete This argument is deprecated and will be completely ignored
    * @param array $origin
    */
    public function delete($id, $harddelete = false, array $origin = array())
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
        $deleted = ($deleted) ? 1 : 0;
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, array('deleted' => $deleted));
        $ar = $this->db->affected_rows();
        if ($ar) {
            $record = array_merge($this->_getById($id), $origin);
            $this->_log($id, $record, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
            $this->_deletePDF($record['sop_id']);
        }
        return $ar;
    }

    private function _hardDelete($id, array $origin)
    {
        $currentRecord = $this->_getById($id);
        if (empty($currentRecord)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Section ' . $id . ' because it does not exist! Probably already deleted', 'section', ImpressLogger::ACTION_DELETE);
            return 0;
        }

        //store a backup in deleted table
        $this->load->model('sectiondeletedmodel');
        $iid = $this->sectiondeletedmodel->insert($currentRecord);

        // delete
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $ar = $this->db->affected_rows();
            if ($ar) {
                $currentRecord = array_merge($currentRecord, $origin);
                $this->_log($id, $currentRecord, ChangeLogger::ACTION_DELETE);
                $this->_deletePDF($currentRecord['sop_id']);
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
            self::PRIMARY_KEY, 'sop_id', 'section_title_id', 'section_text',
            'weight', 'level', 'level_text', 'major_version',
            'minor_version', 'user_id', 'time_modified'
        );
    }

    /**
    * @param array $arr hash of columns
    * @param string $action
    * @return int last insert id
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        $arr['user_id'] = User::getId();
        $arr['time_modified'] = $this->config->item('timestamp');
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
        if ($iid) {
            $this->_deletePDF(@$arr['sop_id']);
            $action = ($action == ChangeLogger::ACTION_CLONE) ? ChangeLogger::ACTION_CREATE : $action;
            if($action != ChangeLogger::ACTION_VERSION)
                $this->_log($iid, $arr, $action);
        }
        return $iid;
    }

    /**
    * Steps to updating (minor update)
    * - save a copy of the record to the backup table
    * - increment the version number
    * - save changes
    * @param int $id row id to update
    * @param array $arr hash of columns
    * @param string $action the default way this method is called is a straight
    * -forward update but it can be called other ways like during a reversion,
    * so this parameter can prevent the default updating logging call being put
    * @return int last updated id
    */
    public function update($id, $arr, $action = ChangeLogger::ACTION_UPDATE)
    {
        $oldrecord = $this->getById($id);
        if(array_key_exists('deleted', $oldrecord))
            unset($oldrecord['deleted']);
        $this->db->insert('section_oldedits', $oldrecord);

        $arr['minor_version'] = $oldrecord['minor_version'] + 1;
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();

        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
        if ($ar) {
            $this->_deletePDF($oldrecord['sop_id']);
            if($action == ChangeLogger::ACTION_UPDATE)
                $this->_log($id, $arr, $action);
        }
        return $ar;
    }

    /**
    * @param array $source contains sop_id field with sop id to clone from
    * @param array $destination contains sop_id field with sop to clone to
    * @return bool
    */
    public function cloneByProcedure(array $source, array $destination)
    {
        $origSections = $this->getSectionsBySOP($source['sop_id']);
        //if Protocol has no sections then just go back
        if(empty($origSections))
            return true;
        $success = true;
        foreach ($origSections as $section) {
            unset($section[self::PRIMARY_KEY]);
            $section['sop_id'] = $destination['sop_id'];
            $section['user_id'] = User::getId();
            $section['time_modified'] = $this->config->item('timestamp');
            $section['deleted'] = 0;
            $section['major_version'] = 1;
            $section['minor_version'] = 0;
            $section['pipeline_id'] = $destination['pipeline_id'];
            $section['procedure_id'] = $destination['procedure_id'];
            $iid = $this->insert($section, ChangeLogger::ACTION_CLONE);
            if( ! $iid)
                $success = false;
        }
        return $success;
    }

    /**
    * @param int $id The id of the record being logged about
    * @param array $arr The fields being inserted into the db
    * @param string $action One of the actions in the ChangeLogger class
    * @return bool
    */
    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about the object being logged
        $this->load->model('sopmodel');
        $this->load->model('sectiontitlemodel');
        $sop = $this->sopmodel->getById(@$arr['sop_id']);
        $proc = $this->proceduremodel->getById(@$arr['procedure_id']);
        $stitle = $this->sectiontitlemodel->getById(@$arr['section_title_id']);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated ' . @$stitle['title'] . ' section (' . $id
                     . ') in Protocol "' . @$sop['title'] . '" of Procedure '
                     . @$proc['name'] . ' [' . @$proc['procedure_key'] . ']';
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new ' . @$stitle['title'] . ' section in '
                     . 'Protocol "' . @$sop['title'] . '" of Procedure '
                     . @$proc['name'] . ' [' . @$proc['procedure_key'] . ']';
        } else if ($action == ChangeLogger::ACTION_REVERT) {
            $message = 'Reverted current ' . @$stitle['title'] . ' section ('
                     . $id . ') to revision ' . @$arr['major_version'] . '.'
                     . @$arr['minor_version'] . '\'s copy';
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted ' . @$stitle['title'] . ' section (' . $id
                     . ') in Protocol "' . @$sop['title'] . '" of Procedure '
                     . @$proc['name'] . ' [' . @$proc['procedure_key'] . ']';
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted ' . @$stitle['title'] . ' section (' . $id
                     . ') in Protocol "' . @$sop['title'] . '" of Procedure '
                     . @$proc['name'] . ' [' . @$proc['procedure_key'] . ']';
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => @$proc['procedure_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Protocol Section',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$proc['internal'] || $this->hasInternalParentByParentId(@$sop[SOPModel::PRIMARY_KEY]) || $this->hasInternalParentByParentId(@$arr[SOPModel::PRIMARY_KEY]))
            )
        );
    }

    private function _deletePDF($sopId)
    {
        if ($this->config->item('delete_edited_protocol_pdf')) {
            $this->load->model('sopmodel');
            return $this->sopmodel->deletePDF($sopId);
        }
        return true;
    }

    /**
    * @param int $sectionId Id of the row from the section table that we want to look up revisions for
    * @return array All the revisions for the supplied id including the current active
    * version but this row in the array lacks an 'id' field, which is intentional as it
    * allows the differentiation between the current version and the old revisions
    */
    public function getRevisionsById($sectionId)
    {
        $new = array($this->getById($sectionId));
        $old = $this->db->from('section_oldedits')
                        ->where('section_id', $sectionId)
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
    * @param array $origin An array containing the pipeline, procedure and
    * parameter from where this item is being requested for reversion, for log
    * @return int rows affected
    */
    public function revert($id, $revId, array $origin)
    {
        $current = $this->getById($id);
        $revision = $this->db->from('section_oldedits')
                             ->where('id', $revId)
                             ->limit(1)
                             ->get()
                             ->row_array();
        if(empty($revision))
            return false;
        unset($revision['id']);
        unset($revision['section_id']);
        $revision['minor_version'] = $current['minor_version'];
        $revision['user_id'] = User::getId();
        $revision['time_modified'] = $this->config->item('timestamp');
        $revision = array_merge($revision, $origin);
        $ar = $this->update($id, $revision, ChangeLogger::ACTION_REVERT);
        if ($ar) {
            $this->_log($id, $revision, ChangeLogger::ACTION_REVERT);
        }
        return $ar;
    }

    /**
    * @param string $searchStr
    * @return array rows
    */
    public function search($searchStr)
    {
        $searchStr = preg_replace('/[^a-zA-Z ]/', '', $searchStr);
        if(empty($searchStr))
            return array();
        return $this->db->from(self::TABLE)
                        ->like('section_text', $searchStr)
                        ->get()
                        ->result_array();
    }
}
