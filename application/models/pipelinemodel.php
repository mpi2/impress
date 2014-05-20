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
 * Pipeline model
 */
class PipelineModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable, ISequenceable
{
    const TABLE = 'pipeline';
    const PRIMARY_KEY = 'pipeline_id';
    const OLDEDITS_TABLE = 'pipeline_oldedits';
    const OLDEDITS_PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('deleted', 0);
        }
//        if($this->config->item('server') != 'internal')
//            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->order_by('deprecated')
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }
	
    private function _fetchAll()
    {
        return $this->db->from(self::TABLE)
                        ->order_by('deprecated')
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function getById($id)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('deleted', 0);
        }
//        if($this->config->item('server') != 'internal')
//            $this->db->where('internal', 0);
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

    public function getByKey($key)
    {
        if ( ! User::hasPermission(User::VIEW_DELETED)) {
            $this->db->where('deleted', 0);
        }
//        if($this->config->item('server') != 'internal')
//            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->where('pipeline_key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _getByKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('pipeline_key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    /**
    * @param int $id to delete
    * @param bool $harddelete is now deprecated and will be ignored - no more hard delete functionality
    * @return bool
    */
    public function delete($id, $harddelete = false)
    {
        //deprecated pipelines are not allowed to be deleted unless the modify_deprecated settings flag is on
        if($this->isDeprecated($id) && $this->config->item('modify_deprecated') === FALSE)
            return FALSE;

        if($this->config->item('delete_mode') == 'hard')
            return (bool) $this->_hardDelete($id);
        return (bool) $this->_setDeletedFlag($id, TRUE);
    }

    public function isDeprecated($id)
    {
        $p = $this->_getById($id);
        return (bool)@$p['deprecated'];
    }
	
	public function isDeleted($id)
    {
        $p = $this->_getById($id);
        return (bool)@$p['deleted'];
    }
	
	public function isInternal($id)
    {
        $p = $this->_getById($id);
        return (bool)@$p['internal'];
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isInBeta($id)
    {
        $item = $this->_getById($id);
        if ( ! empty($item)) {
            $this->load->model('notinbetamodel');
            return $this->notinbetamodel->keyIsInBeta($item['pipeline_key']);
        }
        return false;
    }
    
    /**
    * @param int $id
    * @param mixed $deleted TRUE sets the deleted flag to 1, FALSE sets it to 0 and effectively undeletes it
    */
    private function _setDeletedFlag($id, $deleted = true)
    {
        $currentRecord = $this->_getById($id);
        $deleted = ($deleted) ? 1 : 0;
        $arr = array('deleted' => $deleted, 'time_modified' => $this->config->item('timestamp'), 'user_id' => User::getId());
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $arr);
        $ar = $this->db->affected_rows();
        if ($ar)
            $this->_log($id, $currentRecord, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        return $ar;
    }

    /**
    * @param int $id
    * @return int rows affected
    * @access private
    * @see PipelineModel::delete()
    */
    private function _hardDelete($id)
    {
        //check item already exists and if not it's probably already deleted so return 0 affected rows
        $pip = $this->_getById($id);
        if (empty($pip)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Pipeline ' . $id . ' because it does not exist! Probably already deleted', 'pipeline', ImpressLogger::ACTION_DELETE);
            return 0;
        }
        //if the item is in use and is being linked to by other items then don't delete it unless the active_item_deletion settings flag is on
        $this->load->model('pipelinehasproceduresmodel');
        $links = $this->pipelinehasproceduresmodel->getByPipeline($id);
        if ( !empty($links) && !$this->config->item('active_item_deletion')) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Pipeline ' . $id . ' because there are ' . count($links) . ' items linking to it', 'pipeline', ImpressLogger::ACTION_DELETE);
            return 0;
        }
        //delete the procedures belonging to this pipeline if the settings say so
        if ($this->config->item('child_deletion')) {
            foreach ($links as $link) {
                $this->proceduremodel->delete(
                    //$link['procedure_id'],
                    //$link['pipeline_id'], 
                    array(
                        self::PRIMARY_KEY => $link[self::PRIMARY_KEY],
                        ProcedureModel::PRIMARY_KEY => $link[ProcedureModel::PRIMARY_KEY]
                    )
                );
            }
        }
		
        //save a backup of the pipeline record
        $this->load->model('pipelinedeletedmodel');
        $iid = $this->pipelinedeletedmodel->insert($pip);
		
        //delete the pipeline
        if ($iid) {
                $this->db->where(self::PRIMARY_KEY, $id)
                         ->delete(self::TABLE);
                $ar = $this->db->affected_rows();
                //tie up loose ends
                $this->load->model('notinbetamodel');
                $this->notinbetamodel->deleteByKey($pip['pipeline_key']);
                //log it
                if ($ar)
                        $this->_log($id, $pip, ChangeLogger::ACTION_DELETE);
                $iid = $ar;
        }

        return $iid;
    }

    /**
    * @param int $id
	* @param array $origin In this case the $origin will be ignored because a
	* Pipeline is at the top of the chain of origination
    * @return bool
    */
    public function undelete($id, array $origin = array())
    {
        //deprecated pipelines are not allowed to be undeleted unless the modify_deprecated settings flag is on
        if($this->isDeprecated($id) && $this->config->item('modify_deprecated') === FALSE)
            return FALSE;
			
        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if($this->isDeleted($id))
                return (bool) $this->_setDeletedFlag($id, FALSE);

        if($this->config->item('delete_mode') == 'hard')
            return false;
        return (bool) $this->_setDeletedFlag($id, FALSE);
    }

    /**
    * @todo check if pipeline_id should be in the list below or not,
    * it was before now 'pipeline_id'
    */
    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }
    
    private function _filterOldeditsFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->_getOldeditsFields(), self::OLDEDITS_PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }

    public function getFields()
    {
        return array(
            self::PRIMARY_KEY, 'pipeline_key', 'name', 'weight',
            'visible', 'active', 'deprecated', 'major_version',
            'minor_version', 'description', 'time_modified', 'user_id',
            'internal', 'deleted', 'centre_name', 'impc'
        );
    }
    
    private function _getOldeditsFields()
    {
        $fields = $this->getFields();
        $fields[] = self::OLDEDITS_PRIMARY_KEY;
        $this->load->helper('delete_array_values');
        return delete_array_values($fields, 'deleted');
    }

    /**
    * A stubb is the first part of the pipeline key, "IMPC" from IMPC_001
    * @param string $stubb
    * @return bool
    */
    public function isUniqueStubb($stubb = null)
    {
        return ! (bool) $this->db->from(self::TABLE)
                                 ->like('pipeline_key', $stubb . '_', 'after')
                                 ->count_all_results();
    }

    /**
    * @param array hash of columns and values
    * @param string $action
    * @return int last insert id
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        //convert the key stubb into a proper key if it isn't already
        $arr['pipeline_key'] = KeyUtil::convertPipelineStubbToKey($arr['pipeline_key']);
		
        //check the key isn't already taken and increment it if it is
        $pipeline = $this->_getByKey($arr['pipeline_key']);
        if ( ! empty($pipeline))
                $arr['pipeline_key'] = $this->_getNextVersionKey($arr['pipeline_key']);

        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $iid = $this->db->insert_id();
		//add the key to the NotInBeta table
		if ($iid) {
			$this->load->model('notinbetamodel');
			$this->notinbetamodel->insert($arr['pipeline_key']);
		}
		//log it
        if($iid && ($action == ChangeLogger::ACTION_CREATE || $action == ChangeLogger::ACTION_UNDELETE))
            $this->_log($iid, $arr, $action);
        return $iid;
    }

    /**
    * Steps to updating (minor update)
    * - save a copy of the old record to the backup table
    * - increment the version number
    * - save changes
    * @param int row id to update
    * @param array hash of columns
    * @param string $action
    * @return int rows affected
    */
    public function update($id, $arr, $action = ChangeLogger::ACTION_UPDATE)
    {
        $oldpipeline = $this->_getById($id);
        if(empty($oldpipeline))
            return 0;
        unset($oldpipeline['deleted']);
        $this->db->insert('pipeline_oldedits', $this->_filterOldeditsFields($oldpipeline));

        $arr['minor_version'] = 1 + $oldpipeline['minor_version'];
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();

        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $this->_filterFields($arr));
        $ar = $this->db->affected_rows();
        if($ar && $action == ChangeLogger::ACTION_UPDATE)
            $this->_log($id, $oldpipeline, $action);
        return $ar;
    }

    /**
    * @param int $id Id of the row in the table that we want to look up revisions for
    * @return array All the revisions for the supplied id including the current active
    * version but this row in the array lacks an 'id' field, which is intentional as it
    * allows the differentiation between the current version and the old revisions
    */
    public function getRevisionsById($id)
    {
        $new = array($this->_getById($id));
        $old = $this->db->from('pipeline_oldedits')
                        ->where('pipeline_id', $id)
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
    * @param array
    * @return int affected rows
    */
    public function revert($id, $revId)
    {
        $current = $this->getById($id);
        $revision = $this->db->from('pipeline_oldedits')
                             ->where('id', $revId)
                             ->limit(1)
                             ->get()
                             ->row_array();
        unset($revision['id']);
        unset($revision['pipeline_id']);
        $revision['minor_version'] = $current['minor_version'];
        $revision['user_id'] = User::getId();
        $revision['time_modified'] = $this->config->item('timestamp');
        $ar = $this->update($id, $revision, ChangeLogger::ACTION_REVERT);
        if($ar)
            $this->_log($id, $current, ChangeLogger::ACTION_REVERT);
        return $ar;
    }

    /**
    * Move a record up or down in display order
    * @param int $pipelineId
    * @param string $direction should be either "up" or "dn"
    * @return bool success
    */
    public function move($pipelineId, $direction)
    {
        if($direction != 'dn') $direction = 'up';

        $pips = $this->_fetchAll();

        $current = $other = null;

        if($direction == 'dn')
        {
            $next = null;
            for ($i = 0; $i < count($pips); $i++) {
                if ($pips[$i][self::PRIMARY_KEY] == $pipelineId) {
                    $current = $pips[$i];
                    if(isset($pips[$i + 1]))
                        $next = $pips[$i + 1];
                    else
                        return false;
                    break;
                }
            }
            if ($current['weight'] != $next['weight']) {
                $temp = $next['weight'];
                $next['weight'] = $current['weight'];
                $current['weight'] = $temp;
            } else {
                $current['weight'] = $next['weight'] + 1;
            }
            $other = $next;
        }
        else if($direction == 'up')
        {
            $prev = null;
            for ($i = 0; $i < count($pips); $i++) {
                if ($pips[$i][self::PRIMARY_KEY] == $pipelineId) {
                    $current = $pips[$i];
                    if(isset($pips[$i - 1]))
                        $prev = $pips[$i - 1];
                    else
                        return false;
                    break;
                }
            }
            if ($current['weight'] != $prev['weight']) {
                $temp = $prev['weight'];
                $prev['weight'] = $current['weight'];
                $current['weight'] = $temp;
            } else {
                $prev['weight'] = $current['weight'] + 1;
            }
            $other = $prev;
        }

        if ($current) {
            $this->db->where(self::PRIMARY_KEY, $current[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $current['weight']));
            $this->db->where(self::PRIMARY_KEY, $other[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $other['weight']));
            return (bool) $this->db->affected_rows();
        }
        return false;
    }
	
    /**
    * Resequence weights
    */
    public function resequence()
    {
        $pips = $this->_fetchAll();
        $counter = 0;
        foreach ($pips as $pip) {
            $this->db->where(self::PRIMARY_KEY, $pip[self::PRIMARY_KEY])
                     ->update(self::TABLE, array('weight' => $counter));
            $counter++;
        }
    }

    public function createNewVersion($arr)
    {
        //generate new version record
        $oldPipId = $arr['pipeline_id'];
        $oldPip = $this->_getById($oldPipId); //$oldPip   = $this->getLastPipelineVersion($oldPipId);
        $newPip['pipeline_key']  = $this->_getNextVersionKey($oldPip['pipeline_key']);
        $newPip['major_version'] = KeyUtil::getVersionFromPipelineKey($newPip['pipeline_key']);
        $newPip['minor_version'] = 0;
        $newPip = array_merge($oldPip, $newPip);
        $newPipId = $this->insert($newPip, ChangeLogger::ACTION_VERSION);

        //tie procedures to the new pipeline
        $this->load->model('pipelinehasproceduresmodel');
        $this->pipelinehasproceduresmodel->copyPipelineProceduresToNewPipeline($oldPipId, $newPipId, $exceptProcedure = @$arr['delete_procedure_id']);

        //log it
        if($newPipId)
            $this->_log($newPipId, $oldPip, ChangeLogger::ACTION_VERSION);

        return $newPipId;
    }

    private function _getNextVersionKey($key)
    {
        $unique = TRUE;
        $newKey = $key;
        do {
            $newKey = KeyUtil::incrementPipelineKeyVersion($newKey);
            $x = $this->_getByKey($newKey);
            $unique = empty($x);
        }
        while($unique === FALSE);
        return $newKey;
    }
    
    /**
     * Checks to see the Pipeline is the latest version... this method is called
     * by its' children via the ProcedureModel::isLatestVersion() method
     * @param array $origin
     * @return bool
     * @see ProcedureModel::isLatestVersion()
     */
    public function isLatestVersion(array $origin)
    {
        $latestPip = $this->getLastPipelineVersion($origin[self::PRIMARY_KEY]);
        return ($origin[self::PRIMARY_KEY] == $latestPip[self::PRIMARY_KEY]);
    }
    
    /**
     * Looks at the pipeline key and determines it is the last version of that
     * Pipeline
     * 
     * @param int $pipId
     * @return array|bool Pipeline hash or false if nothing there
     */
    public function getLastPipelineVersion($pipId)
    {
        $keyParts = KeyUtil::getPipelineKeyParts($pipId);
        if (empty($keyParts))
            return false;
        $prefix = $keyParts[KeyUtil::PREFIX];
        $pip = $this->db->query(
            "SELECT
                * 
             FROM "
                . self::TABLE .
             " WHERE
                pipeline_key LIKE '$prefix%'
             ORDER BY
                CAST(SUBSTRING(pipeline_key, -3) AS SIGNED INTEGER) DESC
             LIMIT 1"
        )->row_array();
        return (empty($pip)) ? false : $pip;
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //get current record
        $currentRecord = $this->_getById($id);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Pipeline (' . $id . ') ' . @$arr['name'] . '. ';
            foreach (array('name','visible','active','deprecated','description','internal') as $field) {
                if($arr[$field] != $currentRecord[$field])
                    $message .= $field . ' changed from ' . @$arr[$field] . ' to ' . @$currentRecord[$field] . '. ';
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Pipeline (' . $id . ') ' . @$arr['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Delete Pipeline (' . $id . ') ' . @$arr['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Pipeline (' . $id . ') ' . @$arr['name'];
        } else if ($action == ChangeLogger::ACTION_REVERT) {
            $message = 'Reverted current Pipeline (' . $id . ') ' . @$arr['name']
                     . ' to revision ' . @$currentRecord['major_version'] 
                     . '.' . @$currentRecord['minor_version'];
        } else if ($action == ChangeLogger::ACTION_VERSION) {
            $message = 'Created a new version of Pipeline (' . $arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' -> ' . @$currentRecord['name'] . ' version '
                     . @$currentRecord['major_version'];
        } else {
                return true;
        }

        //log it
        return ChangeLogger::log(array(
            ChangeLogger::FIELD_ITEM_ID => $id,
            ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['pipeline_key'])) ? @$arr['pipeline_key'] : @$currentRecord['pipeline_key'],
            ChangeLogger::FIELD_ITEM_TYPE => 'Pipeline',
            ChangeLogger::FIELD_ACTION => $action,
            ChangeLogger::FIELD_PIPELINE => (empty($currentRecord['pipeline_id'])) ? @$arr['pipeline_id'] : @$currentRecord['pipeline_id'],
            ChangeLogger::FIELD_PROCEDURE => null,
            ChangeLogger::FIELD_PARAMETER => null,
            ChangeLogger::FIELD_MESSAGE => $message,
            ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$currentRecord['internal'] || @$arr['internal'])
        ));
    }
}
