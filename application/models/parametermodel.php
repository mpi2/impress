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
 * Parameter model
 */
class ParameterModel extends CI_Model implements IUserIdCheckable, IPathwayCheckable, ISequenceable
{
    const TABLE = 'parameter';
    const PRIMARY_KEY = 'parameter_id';
    const OLDEDITS_TABLE = 'parameter_oldedits';
    const OLDEDITS_PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->get()
                        ->result_array();
    }

    public function getById($id)
    {
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
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
        if( ! User::hasPermission(User::VIEW_DELETED))
            $this->db->where('deleted', 0);
        if($this->config->item('server') != 'internal')
            $this->db->where('internal', 0);
        return $this->db->from(self::TABLE)
                        ->where('parameter_key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _getByKey($key)
    {
        return $this->db->from(self::TABLE)
                        ->where('parameter_key', $key)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    /**
    * @param int $parameterId to delete
    * @param int $procedureId -- ignore this
    * @param array $origin
    * @return bool
    */
    public function delete($parameterId, $procedureId = null, array $origin = array())
    {
        //deprecated parameters should not be deleted, unless the modify_deprecated setting flag is on
        if($this->isDeprecated($parameterId) && $this->config->item('modify_deprecated') === FALSE)
            return FALSE;

        //deleting a parameter from a procedure should mean deleting the link between the procedure and the parameter
        //but if there is only one link to a parameter then the parameter itself needs deleting
        $this->load->model('procedurehasparametersmodel');
        $links = $this->procedurehasparametersmodel->getByParameter($parameterId);
        if (count($links) >= 2) {
            $param = $this->_getById($parameterId);
            $del = (bool) $this->procedurehasparametersmodel->delete($origin['procedure_id'], $parameterId);
            if ($del) {
                    $this->_log($parameterId, array_merge($param, $origin), ChangeLogger::ACTION_DELETE);
            }
            return $del;
        }

        //delete the parameter (either soft or hard delete it)
        if ($this->config->item('delete_mode') == 'hard')
            $ar = (bool) $this->_hardDelete($parameterId, $origin);
        else
            $ar = (bool) $this->_setDeletedFlag($parameterId, TRUE, $origin);

        //delete SOP connected to the Procedure in which this parameter resides
        //This ensures user manually uploads an up-to-date SOP with the current parameters in
        if ($ar && $this->config->item('delete_edited_protocol_pdf')) {
            $this->load->model('sopmodel');
            $this->sopmodel->deletePDFByProcedure($procedureId);
        }

        return $ar;
    }

    /**
     * @param array|int $id Origin-style array or parameter id
     * @param bool $bubble
     * @return bool
     */
    public function isDeprecated($id, $bubble = true)
    {
        if (is_array($id)) {
            $param = $this->_getById($id[self::PRIMARY_KEY]);
            if (true === (bool)@$param['deprecated']) {
                return true;
            } else if ($bubble) {
                return $this->proceduremodel->isDeprecated($id, $origin);
            }
        } else if (is_numeric($id)) {
            $param = $this->_getById($id);
            if (true === (bool)@$param['deprecated']) {
                return true;
            } else if ($bubble) {
                $this->load->model('originalpathwaysmodel');
                $pathways = $this->originalpathwaysmodel->getPathwaysByParameter($id);
                if ( ! empty($pathways))
                    return $this->proceduremodel->isDeprecated(current($pathways), $bubble);
            }
        }
        return false;
    }
    
    /**
     * @param array|int $id Origin-style array or parameter id
     * @param bool $bubble
     * @return bool
     */
    public function isInternal($id, $bubble = true)
    {
        if (is_array($id)) {
            $param = $this->_getById($id[self::PRIMARY_KEY]);
            if (isset($param['internal']) && true === (bool)@$param['internal']) {
                return true;
            } else if ($bubble) {
                return $this->proceduremodel->isInternal($id, $bubble);
            }
        } else if (is_numeric($id)) {
            $param = $this->_getById($id);
            if (true === (bool)@$param['internal']) {
                return true;
            } else if ($bubble) {
                $this->load->model('originalpathwaysmodel');
                $pathways = $this->originalpathwaysmodel->getPathwaysByParameter($id);
                if ( ! empty($pathways)) {
                    return $this->proceduremodel->isInternal(current($pathways), $bubble);
                }
            }
        }
        return false;
    }
	
    public function isDeleted($id)
    {
        $p = $this->_getById($id);
        return (bool)@$p['deleted'];
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
            return $this->notinbetamodel->keyIsInBeta($item['parameter_key']);
        }
        return false;
    }

    /**
    * @param int $id
    * @param bool $deleted TRUE sets the deleted flag to 1, FALSE sets it to 0 and effectively undeletes it
    * @param array $origin
    */
    private function _setDeletedFlag($id, $deleted = TRUE, $origin = array())
    {
        $deleted = ($deleted) ? 1 : 0;
        $arr = array('deleted' => $deleted, 'time_modified' => $this->config->item('timestamp'), 'user_id' => User::getId());
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->update(self::TABLE, $arr);
        $ar = $this->db->affected_rows();
        if ($ar) {
            $param = $this->_getById($id);
            $param = array_merge($param, $origin);
            $this->_log($id, $param, ($deleted) ? ChangeLogger::ACTION_DELETE : ChangeLogger::ACTION_UNDELETE);
        }
        return $ar;
    }

    private function _parameterHasLinks($parameterId, $model, $name = null)
    {
        $this->load->model($model);
        $links = $this->$model->getByParameter($parameterId);
        if ( ! empty($links)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Parameter ' . $parameterId . ' because there are ' . count($links) . ' ' . $name . '(s) linking to it', 'parameter', ImpressLogger::ACTION_DELETE);
            return TRUE;
        }
        return FALSE;
    }

    /**
    * @access private
    * @see ParameterModel::delete()
    */
    private function _hardDelete($id, $origin)
    {
        //check the record exists
        $param = $this->_getById($id);
        if (empty($param)) {
            ImpressLogger::log(ImpressLogger::WARNING, 'Failed to delete Parameter ' . $id . ' because it does not exist! Probably already deleted', 'parameter', ImpressLogger::ACTION_DELETE);
            return 0; //item probably already deleted hence 0 affected rows
        }

        //only hard-delete the parameter if it is not actively associated with other things -
        //has no options/increments/mp/eqterms associated with it or the active_item_deletion settings flag is on
        if ($this->config->item('active_item_deletion') === FALSE                  &&
            ($this->_parameterHasLinks($id, 'parameterhasoptionsmodel', 'Options') ||
             $this->_parameterHasLinks($id, 'paramincrementmodel', 'Increment')    ||
             $this->_parameterHasLinks($id, 'parammptermmodel', 'MP Term')         ||
             $this->_parameterHasLinks($id, 'parameqtermmodel', 'EQ Term'))) {
                return 0;
        }

        //delete parameter children (options/increments/mp/eqterms)
        if ($this->config->item('child_deletion')) {
            //delete options
            //Options have an intermediate parameter_has_options table which means options are not deleted in the same
            //way as the other tables
            $this->load->model('parameterhasoptionsmodel');
            $this->load->model('paramoptionmodel');
            foreach($this->parameterhasoptionsmodel->getByParameter($id, false) AS $link){
                //find out if this is the only parameter linking to this option and if it is then delete the option
                $optLinks = $this->parameterhasoptionsmodel->getByOption($link['param_option_id']);
                if(count($optLinks) === 1){
                    $this->paramoptionmodel->delete($link['param_option_id'], null, $origin);
                }
            }
            //delete increments
            $this->load->model('paramincrementmodel');
            $this->paramincrementmodel->hardDeleteByParameter($id, $origin);
            //delete eq terms
            $this->load->model('parameqtermmodel');
            $this->parameqtermmodel->hardDeleteByParameter($id, $origin);
            //delete mp terms
            $this->load->model('parammptermmodel');
            $this->parammptermmodel->hardDeleteByParameter($id, $origin);
        }

        //save a backup of the parameter
        $this->load->model('parameterdeletedmodel');
        $iid = $this->parameterdeletedmodel->insert($param);

        //delete the parameter
        if ($iid) {
            $this->db->where(self::PRIMARY_KEY, $id)
                     ->delete(self::TABLE);
            $iid = $ar = $this->db->affected_rows();
            //delete original pathway (if the given pathway is original)
            if ($ar) {
                $this->load->model('originalpathwaysmodel');
				$oldParamOrigin = $origin;
				$oldParamOrigin[self::PRIMARY_KEY] = $id;
                $this->originalpathwaysmodel->delete($oldParamOrigin);
            }
            //delete key from in_beta table if present to tie up loose ends
            $this->load->model('notinbetamodel');
            $this->notinbetamodel->deleteByKey($param['parameter_key']);
            //delete relationships if present to tie up loose ends
            $this->load->model('parameterrelationsmodel');
            $this->parameterrelationsmodel->deleteByParameterOrParent($id);
            //log parameter deletion
            if ($ar) {
                $param = array_merge($param, $origin);
                $this->_log($id, $param, ChangeLogger::ACTION_DELETE);
            }
        }

        return $iid;
    }

    /**
    * @param int $id
    * @param array $origin
    * @return bool
    */
    public function undelete($id, array $origin)
    {
        //deprecated parameter should not be undeletable unless the modify_deprecated setting is on
        if($this->isDeprecated($id) && $this->config->item('modify_deprecated') === FALSE)
            return FALSE;

        //check the item hasn't already been soft deleted and if it has then soft-undelete it
        if($this->isDeleted($id))
            return (bool) $this->_setDeletedFlag($id, FALSE, $origin);

        if($this->config->item('delete_mode') == 'hard')
            return false; //(bool) $this->_hardUndelete($id, $origin);
        return (bool) $this->_setDeletedFlag($id, FALSE, $origin);
    }

    /**
     * @param array $arr
     * @return array
     */
    private function _filterFields(array $arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->helper('delete_array_values');
        $keys = delete_array_values($this->getFields(), self::PRIMARY_KEY);
        return keep_array_keys($arr, $keys);
    }

    /**
    * @return array all fields
    */
    public function getFields()
    {
        return array(
            self::PRIMARY_KEY, 'parameter_key', 'type', 'name',
            'active', 'deprecated', 'major_version', 'minor_version',
            'derivation', 'description', 'is_annotation', 'is_derived',
            'is_important', 'is_increment', 'is_option', 'is_required',
            'unit', 'qc_check', 'qc_min', 'qc_max',
            'visible', 'qc_notes', 'value_type', 'graph_type',
            'data_analysis_notes', 'time_modified', 'user_id', 'internal',
            'deleted'
        );
    }

    /**
    * @param array $arr hash of columns
    * @param string $action For logging, ACTION_CREATE will go into log
    * @return int|bool last insert id into parameter table or false on failure
    */
    public function insert($arr, $action = ChangeLogger::ACTION_CREATE)
    {
        $pipelineId = $procedureId = $parameterId = null;
        if(array_key_exists('pipeline_id', $arr))
            $pipelineId = $arr['pipeline_id'];
        if(array_key_exists('procedure_id', $arr))
            $procedureId = $arr['procedure_id'];
        if (empty($pipelineId) || empty($procedureId)) {
            ImpressLogger::log(array(
                'type'    => ImpressLogger::ERROR,
                'message' => 'Missing either pipeline/procedure on insert',
                'item'    => 'parameter',
                'action'  => ImpressLogger::ACTION_CREATE
            ));
            return false;
        }
        
        //prevent duplicates
        if ($action == ChangeLogger::ACTION_CREATE && ! $this->_isUnique($arr)) {
            return false;
        }

        //generate a new procedure key and version or a new key version if it already exists
        if (empty($arr['parameter_key']))
            $arr['parameter_key'] = $this->getNewParameterKeyForProcedure($procedureId);
        $parameter = $this->_getByKey($arr['parameter_key']);
        if ( ! empty($parameter))
            $arr['parameter_key'] = $this->_getNextVersionKey($arr['parameter_key']);
        $arr['major_version'] = KeyUtil::getVersionFromParameterKey($arr['parameter_key']);
        $arr['minor_version'] = 0;
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id']       = User::getId();
        $arr['unit']          = (empty($arr['unit'])) ? 0 : (int)$arr['unit'];

        //insert the new Parameter
        $this->db->insert(self::TABLE, $this->_filterFields($arr));
        $parameterId = $this->db->insert_id();

        //link up the new record
        if($parameterId){
            //and make sure the new Parameter is linked up to its Procedure
            $this->load->model('procedurehasparametersmodel');
            $iid = $this->procedurehasparametersmodel->insert($procedureId, $parameterId, @$arr['weight']);
            //and make sure the new Parameter is inserted into the original_pathways table
            $this->load->model('originalpathwaysmodel');
            $uniquePathway = $this->originalpathwaysmodel->insert(array(
                'pipeline_id'  => $pipelineId,
                'procedure_id' => $procedureId,
                'parameter_id' => $parameterId
            ));
            if ( ! $uniquePathway) {
                ImpressLogger::log(array(
                    'type'    => ImpressLogger::WARNING,
                    'message' => 'Parameter inserted does not have unique pathway - how did that happen?',
                    'item'    => 'parameter',
                    'action'  => ImpressLogger::ACTION_CREATE
                ));
            }
            //insert new key into notinbeta table
            $this->load->model('notinbetamodel');
            $this->notinbetamodel->insert($arr['parameter_key']);
            //log it
            if($iid === false){
                ImpressLogger::log(array(
                    'type'    => ImpressLogger::ERROR,
                    'message' => 'ParameterModel: Procedure_has_parameters insert failed on insert',
                    'item'    => 'parameter',
                    'action'  => ImpressLogger::ACTION_CREATE
                ));
                return false;
            } else {
                //delete SOP connected to the Procedure in which this parameter resides
                //This ensures user manually uploads an up-to-date SOP with the current parameters in
                if ($this->config->item('delete_edited_protocol_pdf')) {
                    $this->load->model('sopmodel');
                    $this->sopmodel->deletePDFByProcedure($procedureId);
                }
                //log
                if ($action != ChangeLogger::ACTION_VERSION) {
                    $arr[self::PRIMARY_KEY] = $parameterId;
                    $this->_log($parameterId, $arr, $action);
                }
            }
            return $parameterId;
        }

        //in case of error
        return false;
    }
    
    /**
     * Identifies if a parameter name has been used before (case-insensitive)
     * @param array $arr
     * @return boolean
     */
    private function _isUnique(array $arr)
    {
        $this->load->model('procedurehasparametersmodel');
        $params = $this->procedurehasparametersmodel->getByProcedure($arr[ProcedureModel::PRIMARY_KEY]);
        foreach ($params as $param) {
            if (strtolower($param['name']) == strtolower($arr['name'])) {
                return false;
            }
        }
        return true;
    }

    /**
    * Move a record up or down in display order
    * @param int $parameterId parameter id
    * @param int $procedureId the id of the procedure in which this parameter resides
    * @param string $direction should be either "up" or "dn"
    * @see procedurehasparametersmodel::move()
    */
    public function move($parameterId, $procedureId, $direction)
    {
        $this->load->model('procedurehasparametersmodel');
        return $this->procedurehasparametersmodel->move($parameterId, $procedureId, $direction);
    }

	/**
	* Resequence the Parameters in the given Procedure
	* @param int $procedureId
	*/
    public function resequence($procedureId = null)
    {
        $this->load->model('procedurehasparametersmodel');
        $this->procedurehasparametersmodel->resequence((int)$procedureId);
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
        $oldparameter = $this->_getById($id);
        if(empty($oldparameter))
            return 0;
        unset($oldparameter['deleted']);
        $this->db->insert(self::OLDEDITS_TABLE, $oldparameter);

        $arr['minor_version'] = $oldparameter['minor_version'] + 1;
        $arr['major_version'] = $oldparameter['major_version'];
        $arr['time_modified'] = $this->config->item('timestamp');
        $arr['user_id'] = User::getId();
        $arr['unit'] = (empty($arr['unit'])) ? 0 : (int)$arr['unit'];

        $ar = $this->_update($id, $arr);
        if ($ar && $action == ChangeLogger::ACTION_UPDATE) {
            $oldparameter['procedure_id'] = $arr['procedure_id'];
            $oldparameter['pipeline_id'] = $arr['pipeline_id'];
            $this->_log($id, $oldparameter, $action);
        }
        return $ar;
    }

    /**
    * @param int row id to update
    * @param array hash of columns
    * @return int rows affected
    */
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
    */
    public function getRevisionsById($id)
    {
        $new = array($this->_getById($id));
        $old = $this->db->from(self::OLDEDITS_TABLE)
                        ->where(self::PRIMARY_KEY, $id)
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
    * @return int affected rows
    */
    public function revert($id, $revId, $origin)
    {
        $current = $this->getById($id);
        $revision = $this->db->from(self::OLDEDITS_TABLE)
                             ->where(self::OLDEDITS_PRIMARY_KEY, $revId)
                             ->limit(1)
                             ->get()
                             ->row_array();
        unset($revision['id']);
        unset($revision['parameter_id']);
        $revision['minor_version'] = $current['minor_version'];
        $ar = $this->update($id, $revision, ChangeLogger::ACTION_REVERT);
        if ($ar) {
            $revision = array_merge($revision, $origin);
            $this->_log($id, $revision, ChangeLogger::ACTION_REVERT);
        }
        return $ar;
    }

    /**
    * @param array $arr
    * @see ParameterModel::insert()
    */
    public function createNewVersion(array $arr)
    {
        $oldPipelineId   = $arr['pipeline_id'];
        $oldProcedureId  = $arr['procedure_id'];
        $oldParameterId  = $arr[self::PRIMARY_KEY];
        $oldParameter    = $this->_getById($oldParameterId);
        $oldParameterKey = $oldParameter['parameter_key'];
        $this->load->model('procedurehasparametersmodel');
        $link = $this->procedurehasparametersmodel->getByProcedureAndParameter($oldProcedureId, $oldParameterId);
        if (empty($link)) {
            return false;
        }

        $newParameterId  = NULL; //to be set below
        $newParameter    = array();
        $newParameterKey = NULL;
        $newPipelineId   = $arr['nvpipeline'];
        $newProcedureId  = $arr['nvprocedure'];
        $newRelation     = $arr['nvrelation'];
        $newRelationDesc = @$arr['nvrelationdescription'];
        $forkProcedure   = (bool)@$arr['nvforkprocedure'];
//        $deleteOldItem   = ($forkProcedure || (bool)@$arr['nvdeleteolditem']);
        $deleteOldItem   = (isset($arr['nvdeleteolditem']) && $arr['nvdeleteolditem']) ? true : false;
        $useOldPipKey    = @$arr['nvuseoldpipelinekey'];

        //create a new version of the procedure to insert the new parameter into?
        if ($forkProcedure) {
            $proc = $this->proceduremodel->getById($newProcedureId);
            $proc['nvpipeline'] = $newPipelineId;
            $proc['nvrelation'] = ERelationType::EQUIVALENT;
            $proc['nvrelationdescription'] = 'A major change to an existing Parameter or addition of a new Parameter promped the creation of this new Procedure version';
            $proc['pipeline_id'] = $oldPipelineId;
            $proc['nvuseoldpipelinekey'] = $useOldPipKey;
            $proc[ProcedureModel::PRIMARY_KEY] = $oldProcedureId;
            $proc['delete_parameter_id'] = (isset($arr['delete_parameter_id'])) ? $arr['delete_parameter_id'] : $oldParameterId;
            $proc['softlinkintopipelines'] = (isset($arr['softlinkintopipelines'])) ? $arr['softlinkintopipelines'] : array();
            $newProcedureId = $this->proceduremodel->createNewVersion($proc);
            if($newProcedureId === false){
                ImpressLogger::log(ImpressLogger::ERROR, 'An error occurred in the process of creating a new Procedure version via the creation of a new Parameter - passed in variables: ' . print_r($arr, TRUE));
                return false;
            }
        }

        //copy submitted values into new parameter hash ready for insertion
        $this->load->helper('delete_array_values');
        $copyableKeys = delete_array_values($this->getFields(), array(self::PRIMARY_KEY, 'parameter_key', 'deprecated', 'time_modified', 'user_id', 'major_version', 'minor_version', 'deleted'));
        foreach ($copyableKeys as $k) {
            if (isset($oldParameter[$k])) { //$arr
                $newParameter[$k] = $oldParameter[$k];
            }
        }
        $newParameter = array_merge($newParameter, $arr);

        //now populate the rest of the array keys and insert the new parameter into the database
        $newParameter['pipeline_id']   = $newPipelineId;
        $newParameter['procedure_id']  = $newProcedureId;
        $newParameter['parameter_key'] = $newParameterKey = $this->_getNextVersionKey( KeyUtil::generateNewParameterKey($newProcedureId, $oldParameterId) );
        $newParameter['major_version'] = KeyUtil::getVersionFromParameterKey($newParameterKey);
        $newParameter['minor_version'] = 0;
        $newParameter['weight']        = $link['weight'];
        $newParameter['time_modified'] = $this->config->item('timestamp');
        $newParameter['user_id']       = User::getId();
        $newParameterId = $this->insert($newParameter, ChangeLogger::ACTION_VERSION);
        $newParameter['parameter_id']  = $newParameterId;
        if ( ! $newParameterId) {
            ImpressLogger::log(ImpressLogger::ERROR, 'An error occurred in the process of creating a new Procedure version via the creation of a new Parameter, vars: ' . print_r($arr, true));
            return false;
        }

        //declare relationship between old and new parameter
        $this->load->model('parameterrelationsmodel');
        $rel = $this->parameterrelationsmodel->insert(
            array(
                'pipeline_id'   => $newPipelineId,
                'procedure_id'  => $newProcedureId,
                'parameter_id'  => $newParameterId,
                'parameter_key' => $newParameterKey,
                'relationship'  => $newRelation,
                'description'   => $newRelationDesc,
                'parent_id'     => $oldParameterId,
                'parent_key'    => $oldParameterKey,
                'connection'    => ERelationConnection::RELATION
            )
        );

        //create an array explaining the new location (origin) of the new parameter for logging
        $newOrigin = array(
            'pipeline_id'  => $newPipelineId,
            'procedure_id' => $newProcedureId,
            'parameter_id' => $newParameterId
        );
        
        //link the new parameter to the options from the old parameter
        // $this->load->model('parameterhasoptionsmodel');
        // $newOptionIds = $this->parameterhasoptionsmodel->copyOptionsToNewParameter($oldParameterId, $newParameterId); //new links created only, no need for origin/logging
        // if($newOptionIds === FALSE) return FALSE;
        //normal options
        //options duplicated so they can be edited without affecting old versions
        $this->load->model('paramoptionmodel');
        $success = $this->paramoptionmodel->cloneByParameter($arr, $destination = $newOrigin, $exceptOption = @$arr['delete_option_id']);
        if ( ! $success) {
            return false;
        }
        // if($deleteOldItem && $oldOptionId)
            // $this->paramoptionmodel->delete($oldOptionId, null, $newOrigin);
		
        //link the new parameter to the Ontology Option Groups from the old parameter
        $this->load->model('parameterhasontologygroupsmodel');
        $newOntologyGroupIds = $this->parameterhasontologygroupsmodel->copyGroupsToNewParameter($oldParameterId, $newParameterId); //new links created only, no need for origin/logging
        if ($newOntologyGroupIds === false) {
            return false;
        }

        //copy the old Parameters' increments to the new one
        $this->load->model('paramincrementmodel');
        $newIncrementIds = $this->paramincrementmodel->copyIncrementsToNewParameter($oldParameterId, $newParameterId, $newOrigin);
        if ($newIncrementIds === false) {
            return false;
        }

        //copy the old Parameters' MP ontologies to the new one
        $this->load->model('parammptermmodel');
        $newMPIds = $this->parammptermmodel->copyMPTermsToNewParameter($oldParameterId, $newParameterId, $newOrigin);
        if ($newMPIds === false) {
            return false;
        }

        //copy the old Parameters' EQ ontologies to the new one
        $this->load->model('parameqtermmodel');
        $newEQIds = $this->parameqtermmodel->copyEQTermsToNewParameter($oldParameterId, $newParameterId, $newOrigin);
        if ($newEQIds === false) {
            return false;
        }
        
        if ($deleteOldItem) {
            
            //@note There was a bug I just couldn't find in this block so it's
            //set to just delete the link between Procedure and Parameter. That
            //potentially leaves an orphaned parameter in the parameter table
            //@note Might want to look at this code again another time
            
            // if ($oldProcedureId == $newProcedureId && $oldPipelineId == $newPipelineId) {
            // $this->delete($oldParameterId, null, $newOrigin);
            // } else {
//            $this->load->model('procedurehasparametersmodel');
            //				$links = $this->procedurehasparametersmodel->getByParameter($oldParameterId);
            //				if (count($links) >= 2) {
//            $this->procedurehasparametersmodel->delete($newProcedureId, $oldParameterId);
            //				} else {
            //					$this->delete($oldParameterId, null, $newOrigin);
            //				}
            // }
            
            // log_message('info', "Deleting Old Item Link param: $oldParameterId, proc: $newProcedureId");
            
            
            //@note - looked at this again and it's fixed now
            
            $this->delete($oldParameterId, $newProcedureId, $newOrigin);
        }
		
        //log new version created
        $arr = $oldParameter;
        $arr['new_parameter_id'] = $newParameterId;
        $arr['procedure_id'] = $newProcedureId;
        $arr['pipeline_id'] = $newPipelineId;
        $this->_log($newParameterId, $arr, ChangeLogger::ACTION_VERSION);

        return $newParameterId;
    }

    /**
    * @param array $source
    * @param array $destination
    * @return bool
    */
    public function cloneParameter(array $source, array $destination)
    {
        //initial checks
        $this->load->helper('array_keys_exist');
        if ( ! array_keys_exist($source, array('pipeline_id', 'procedure_id', 'parameter_id')))
            return false;
        $srcPipelineId  = $source['pipeline_id'];
        $srcProcedureId = $source['procedure_id'];
        $srcParameterId = $source['parameter_id'];
        if ( ! array_keys_exist($destination, array('pipeline_id', 'procedure_id', 'nvrelation')))
            return false;
        $cloneMPs = (bool)@$destination['cloneMPs'];
        $cloneEQs = (bool)@$destination['cloneEQs'];
        $cloneOptions = (bool)@$destination['cloneOptions'];
        $cloneIncrements = (bool)@$destination['cloneIncrements'];

        //clone parameter fields
        $destParameter = $srcParameter = $this->_getById($srcParameterId);
        if(empty($srcParameter))
            return false;
        unset($destParameter[self::PRIMARY_KEY]);
        unset($destParameter['parameter_key']);
        $destParameter['deprecated'] = 0;
        $destParameter['major_version'] = 1;
        $destParameter['minor_version'] = 0;
        $destParameter['is_increment'] = 0;
        $destParameter['is_option'] = 0;
        $destParameter['internal'] = 0;
        $destParameter['deleted'] = 0;
        $destParameter['value_type'] = (empty($srcParameter['value_type'])) ? EParamValueType::TEXT : $srcParameter['value_type'];		
        $destParameter['type'] = (empty($srcParameter['type'])) ? EParamType::SIMPLE : $srcParameter['type'];		
        $destParameter['old_parameter_key'] = null;
        $destParameter['pipeline_id'] = $destination['pipeline_id'];
        $destParameter['procedure_id'] = $destination['procedure_id'];
        //for logging
        $destParameter['srcParameterId'] = $srcParameter[self::PRIMARY_KEY];
        $destParameter['srcParameterKey'] = $srcParameter['parameter_key'];
        $destParameter['srcParameterName'] = $srcParameter['name'];

        //insert clone into new Procedure + clone the Parameter-associated items too
        $destination['parameter_id'] = $iid = $this->insert($destParameter, ChangeLogger::ACTION_CLONE);
        if ($iid && $cloneMPs) {
            $this->load->model('parammptermmodel');
            $success = $this->parammptermmodel->cloneByParameter($source, $destination);
            if ( ! $success) {
                return false;
            }
        }
        if ($iid && $cloneEQs) {
            $this->load->model('parameqtermmodel');
            $success = $this->parameqtermmodel->cloneByParameter($source, $destination);
            if ( ! $success) {
                return false;
            }
        }
        if ($iid && $cloneOptions) {
			//normal options
            $this->load->model('paramoptionmodel');
            $success = $this->paramoptionmodel->cloneByParameter($source, $destination);
            if ( ! $success) {
                return false;
            }
            //ontology options
            $this->load->model('ontologygroupmodel');
            $success = $this->ontologygroupmodel->cloneByParameter($source, $destination);
            if ( ! $success) {
                return false;
            }
        }
        if ($iid && $cloneIncrements) {
            $this->load->model('paramincrementmodel');
            $success = $this->paramincrementmodel->cloneByParameter($source, $destination);
            if ( ! $success) {
                return false;
            }
        }

        //create a relationship between the original and the clone
        if ($iid) {
            $oldParameter = $srcParameter;
            $newParameter = $this->_getById($iid);
            $this->load->model('parameterrelationsmodel');
            $rel = $this->parameterrelationsmodel->insert(
                array(
                    'pipeline_id'   => $destination['pipeline_id'],
                    'procedure_id'  => $destination['procedure_id'],
                    'parameter_id'  => $newParameter[self::PRIMARY_KEY],
                    'parameter_key' => $newParameter['parameter_key'],
                    'relationship'  => $destination['nvrelation'],
                    'description'   => @$destination['nvrelationdescription'],
                    'parent_id'     => $oldParameter[self::PRIMARY_KEY],
                    'parent_key'    => $oldParameter['parameter_key'],
                    'connection'    => ERelationConnection::RELATION
                )
            );
        }

        return $iid;
    }

    /**
    * @param array $source
    * @param array $destination
    * @return bool
    */
    public function cloneByProcedure(array $source, array $destination)
    {
        $this->load->model('procedurehasparametersmodel');
        $params = $this->procedurehasparametersmodel->getByProcedure($source['procedure_id'], false);
        foreach ($params as $param) {
            $source[self::PRIMARY_KEY] = $param[self::PRIMARY_KEY];
            $destination['cloneMPs'] =
            $destination['cloneEQs'] =
            $destination['cloneOptions'] =
            $destination['cloneIncrements'] = true;
            if ( ! empty($destination['nvrelationdescription']))
                $destination['nvrelationdescription'] = 'Parameter cloned as part of Procedure cloning with message: ' . $destination['nvrelationdescription'];
            if ( ! $this->cloneParameter($source, $destination))
                return false;
        }
        return true;
    }

    /**
    * @param int $procId The procedure id
    * @return string new parameter key
    */
    public function getNewParameterKeyForProcedure($procId)
    {
        $newKey = KeyUtil::generateNewParameterKey($procId, $this->_getLastParameter($procId), 1);
        if($newKey === false)
            return false;
        
        $unique = $this->_keyNeverBeenUsed($newKey);
        
        if ( ! $unique) {
            $keyParts = KeyUtil::getParameterKeyParts($newKey);
            do{
                if ((int)$keyParts[KeyUtil::PARAMETER] >= 999)
                    $keyParts[KeyUtil::PARAMETER] = '000';
                
                $keyParts[KeyUtil::PARAMETER] = KeyUtil::getTriple( 1 + (int)$keyParts[KeyUtil::PARAMETER] );
                $newKey = KeyUtil::joinParameterKeyParts($keyParts);
                $unique = $this->_keyNeverBeenUsed($newKey);
            }
            while($unique === false);
        }
        
        return $newKey;
    }
    
    /**
     * You get the id of the last parameter in a procedure based on the parameter
     * number in it's key e.g. 4885 (IMPC_HIS_207_001), from passing 102
     * (histopathology procedure), 7 (IMPC Pipeline) as arguments
     * @param int $procId
     * @return int Parameter id
     */
    private function _getLastParameter($procId)
    {        
        $keyParts = KeyUtil::getProcedureKeyParts($procId);
        $prefix = $keyParts[KeyUtil::PREFIX] . '_' . $keyParts[KeyUtil::PROCKEY] . '_';
        
        $param = $this->db->query("
                    SELECT parameter_id FROM parameter param
                    WHERE parameter_key LIKE '$prefix%'
                    ORDER BY CAST(SUBSTRING(parameter_key, -7, 3) AS SIGNED INTEGER) DESC
                    LIMIT 1
                ")->row_array();
        
        return (empty($param)) ? null : current($param);
    }
    
    /**
     * This method takes a key and removes the version triple from the end then
     * sees if this key has ever been used in the database - if it has then it
     * cannot be reused
     * @param string $key like IMPC_BWT_001_001
     * @return bool true if the key has never been used
     */
    private function _keyNeverBeenUsed($key)
    {
        $key = substr($key, 0, -4);
        $result = $this->db->select('parameter_key')
                           ->from(self::TABLE)
                           ->like('parameter_key', $key, 'after')
                           ->limit(1)
                           ->get()
                           ->row_array();
        return empty($result);
    }

    /**
    * @param string $key
    * @return string $key with a next version in its key
    */
    private function _getNextVersionKey($key)
    {
        $x = $this->_getByKey($key);
        if(empty($x))
            return $key; //already latest

        $keyParts = KeyUtil::getParameterKeyParts($key);
        if($keyParts === FALSE)
            return FALSE;

        $newKey = '';
        $unique = TRUE;
        do{
            $keyParts[KeyUtil::PARAMVERSION] = 1 + (int)$keyParts[KeyUtil::PARAMVERSION];
            $newKey = KeyUtil::joinParameterKeyParts($keyParts);
            $x = $this->_getByKey($newKey);
            $unique = empty($x);
        }
        while($unique === FALSE);

        return $newKey;
    }
    
    /**
    * Returns the last parameter of a certain procedure by looking at it's key and getting the largest value
    * @param int $paramId The id of the parameter
    * @return array|bool The Parameter array of the latest Parameter or FALSE if there's something wrong
    */
    public function getLastestVersionOfParameter($paramId)
    {
        $param = $this->_getById($paramId);
        if(empty($param))
            return false;

        $pre = substr($param['parameter_key'], 0, -3);

        $parameter = $this->db->query(
            "SELECT
                `param`.*
             FROM
                `" . self::TABLE . "` AS param
             WHERE
                `param`.`parameter_key` LIKE '$pre%'
             ORDER BY
                CAST(SUBSTRING(parameter_key, -3) AS SIGNED INTEGER) DESC
             LIMIT 1"
        )->row_array();
        
        return (empty($parameter)) ? false : $parameter;
    }

    /**
    * @param string $searchStr
    * @return array rows
    */
    public function search($searchStr)
    {
        $searchStr = preg_replace('/[^a-zA-Z0-9 ]/', '', $searchStr);
        if(empty($searchStr))
            return array();
        return $this->db->from(self::TABLE)
                        ->like('name', $searchStr)
                        ->group_by('parameter_key')
                        ->get()
                        ->result_array();
    }

    /**
     * Check if this item is the latest version of this parameter and also the
     * latest procedure and pipeline
     * @param array $origin
     * @return bool
     * @see ProcedureModel::isLatestVersion()
     * @see PipelineModel::isLatestVersion()
     */
    public function isLatestVersion(array $origin)
    {
        $latestParam = $this->getLastestVersionOfParameter($origin[self::PRIMARY_KEY]);
        if ($origin[self::PRIMARY_KEY] != $latestParam[self::PRIMARY_KEY])
            return false;
        
        if ( ! $this->proceduremodel->isLatestVersion($origin))
            return false;
        
        return true;
    }
    
    /**
    * @param int $paramId The id of the parameter we are checking
    * @param array $arr The new values we are planning to set the parameter with
    * @return bool
    */
    public function isCreationOfNewVersionRequired($paramId, $arr)
    {
        //if version_triggering is disabled then a new version DOESN'T need to be created
        if( ! $this->config->item('version_triggering'))
            return false;
        
        //get the record how it is currently in the db
        $param = $this->_getById($paramId);
        if(empty($param))
            return false;

        //check if the item is the latest version and if it isn't then a new
        //version CANNOT be triggered
//        $arr[self::PRIMARY_KEY] = $paramId;
//        if ( ! $this->isLatestVersion($arr))
//            return FALSE;
        
        //check if item is in beta
        if( ! $this->isInBeta($paramId))
            return false;

        //Test 1 - has Parameter changed from optional to required
        if(array_key_exists('is_required', $arr)){
            if(false === (bool)$param['is_required'] && true === (bool)$arr['is_required'])
                return true;
        }

        //Test 2 - has the value type changed
        if(array_key_exists('value_type', $arr)){
            if($param['value_type'] != $arr['value_type'])
                return true;
        }

        //Test 3 - has QC Check been switched on
        // if(array_key_exists('qc_check', $arr)){
            // if(false === (bool)$param['qc_check'] && true === (bool)$arr['qc_check'])
                // return true;
        // }

        //Test 4 - has the unit changed
        // if(array_key_exists('unit', $arr)){
            // if($param['unit'] != $arr['unit'])
                // return true;
        // }

        //Test 5 - has the parameter type changed
        if(array_key_exists('type', $arr)){
            if($param['type'] != $arr['type'])
                return true;
        }
        
        //@todo Might want to add check for QC Min/Max values changing in future but
        //for time being we can leave this out while they are deciding on them

        return false;
    }

    /**
	 * Creates a new version of the parent (Procedure or even Pipeline as well) and inserts new
	 * version of the Parameter and replaces the old version of the Parameter with the new one
     * @param array $arr
     * @return array $origin style array
     */
    public function createNewParentVersionAndDeleteOldItem($arr)
    {
        $this->load->model(array('proceduremodel','procedurehasparametersmodel','originalpathwaysmodel'));
        $oldProc = $this->proceduremodel->getById($arr[ProcedureModel::PRIMARY_KEY]);
        $arr['delete_parameter_id'] = $arr[self::PRIMARY_KEY];
        $newProcId = $this->proceduremodel->createNewVersion(array_merge($oldProc, $arr));
        $origin = current($this->originalpathwaysmodel->getPathwaysByProcedure($newProcId));
        return ($origin) ? $origin : false;
    }
	
    /**
    * Just creates a new version of a Parameter without needing to trigger the creation of a
    * Parent item and replaces the old version of the Parameter with the new one
    * @param int $id
    * @param array $arr A hash array with versioning fields and the destination of the new version
    * @return int|bool False if an error occured or the id of the new version
    */
    public function createNewVersionAndDeleteOldItem($id, $arr)
    {
            $param = $this->_getById($id);
            if (empty($param))
                return false;

            //find out where it was originally from
            $this->load->model('originalpathwaysmodel');
            $origin = $this->originalpathwaysmodel->getPathwaysByParameter($id);
            if (empty($origin)) return false;
            $origin = current($origin);
            //copy the origin and versioning fields into the array
            $param = array_merge($param, $origin, $arr);
            return $this->createNewVersion($param);
    }

    /**
    * Create a new copy of an existing parameter and sticks it in a new procedure
    */
    public function copyParameter($paramId, $toProcId)
    {
        $param = $this->_getById($paramId);
        if(empty($param)) return FALSE;
        unset($param['parameter_id']);
        $param['procedure_id'] = (int)$toProcId;
        $newParamId = $this->insert($param);
        return ($newParamId === FALSE) ? FALSE : $newParamId;
    }

    /**
    * @param int $optionId The id of the option that was inserted or deleted and
    * so its corresponding parameter needs its is_option status flag updating
    */
    public function updateParameterOptionFlagForOption($optionId)
    {
        $this->load->model(array('parameterhasoptionsmodel','paramoptionmodel'));
        $parameters = array();
        foreach($this->parameterhasoptionsmodel->getByOption($optionId) AS $pho){
            $parameters[] = $pho[self::PRIMARY_KEY];
        }
        foreach($parameters AS $param){
            $links = $this->parameterhasoptionsmodel->getByParameter($param);
            $numActiveOptions = 0;
            foreach($links AS $link){
                $option = $this->paramoptionmodel->getById($link['param_option_id']);
                if( ! empty($option) && ! $option['deleted']) $numActiveOptions += 1;
            }
            $p = $this->_getById($param);
            if($numActiveOptions == 0){
                if(1 == $p['is_option']){
                    $this->_update($param, array('is_option' => 0));
                }
            }else{
                if(0 == $p['is_option']){
                    $this->_update($param, array('is_option' => 1));
                }
            }
        }
    }

    /**
    * @param int $parameterId
    * @return int|bool FALSE if parameter not found, otherwise number of records updated
    * or true if nothing needs updating
    */
    public function updateOptionFlagForParameter($parameterId)
    {
        $this->load->model('parameterhasoptionsmodel');
        $parameter = $this->_getById($parameterId);
        if ( ! empty($parameter)) {
            $options = $this->parameterhasoptionsmodel->getByParameter($parameterId, false);
            if ( ! empty($options) && $parameter['is_option'] == 0) {
                return $this->_update($parameterId, array('is_option' => 1));
            } else if (empty($options) && $parameter['is_option'] == 1) {
                return $this->_update($parameterId, array('is_option' => 0));
            } else {
                return true;
            }
        }
        return false;
    }

    /**
    * @param int $paramId The id of the parameter with an increment that was just inserted or deleted
    */
    public function updateParameterIncrementFlag($paramId)
    {
        $this->load->model('paramincrementmodel');
        $numActiveIncrements = 0;
        foreach($this->paramincrementmodel->getByParameter($paramId) AS $inc){
            if( ! $inc['deleted'])
                $numActiveIncrements++;
        }
        $param = $this->_getById($paramId);
        if ( ! empty($param)) {
            if($numActiveIncrements == 0){
                if(1 == $param['is_increment']){
                    return (bool) $this->_update($paramId, array('is_increment' => 0));
                }
            }else{
                if(0 == $param['is_increment']){
                    return (bool) $this->_update($paramId, array('is_increment' => 1));
                }
            }
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * @param string|array $id MP Id
     * @return array Result Set
     */
    public function getByMPId($id)
    {
        return $this->db->select('pip.pipeline_id, pip.name AS pipeline_name, pip.pipeline_key, '
                               . 'proc.procedure_id, proc.name AS procedure_name, proc.procedure_key, '
                               . 'param.' . self::PRIMARY_KEY . ', param.name AS parameter_name, param.parameter_key,'
                               . 'CONCAT("[", param_mpterm.mp_id, "]", " ", param_mpterm.mp_term) AS param_mp,'
                               . 'CONCAT("[", po.ontology_id, "]", " ", po.ontology_term) AS option_mp', false)
                         ->from(self::TABLE . ' AS param')
                         ->join('procedure_has_parameters php1', 'php1.' . self::PRIMARY_KEY . ' = param.' . self::PRIMARY_KEY)
                         ->join('procedure proc', 'proc.procedure_id = php1.procedure_id')
                         ->join('pipeline_has_procedures php2', 'php2.procedure_id = proc.procedure_id')
                         ->join('pipeline pip', 'pip.pipeline_id = php2.pipeline_id')
                         ->join('original_pathways op', 'op.pipeline_id = pip.pipeline_id AND op.procedure_id = proc.procedure_id AND op.' . self::PRIMARY_KEY . ' = param.' . self::PRIMARY_KEY)
                         ->join('param_mpterm', 'param.' . self::PRIMARY_KEY . ' = param_mpterm.' . self::PRIMARY_KEY, 'left')
                         ->join('parameter_has_ontologygroups pho', 'pho.' . self::PRIMARY_KEY . ' = param.' . self::PRIMARY_KEY, 'left')
                         ->join('ontology_group og', 'og.ontology_group_id = pho.ontology_group_id', 'left')
                         ->join('param_ontologyoption po', 'po.ontology_group_id = og.ontology_group_id', 'left')
                         ->where_in('po.ontology_id', (array)$id)
                         ->or_where_in('param_mpterm.mp_id', (array)$id)
                         ->group_by('pip.pipeline_id, proc.procedure_id, param.' . self::PRIMARY_KEY)
                         ->order_by('pip.weight, php2.weight, php1.weight')
                         ->get()
                         ->result_array();
    }

    /**
    * @param int $unitId
    * @return int how often the specified unit is used in the parameters table
    */
    public function getUnitUsageFrequency($unitId)
    {
        return (int) $this->db->from(self::TABLE)
                              ->where('unit', $unitId)
                              ->count_all_results();
    }

    private function _log($id, array $arr, $action)
    {
        if($this->config->item('change_logging') === false)
            return true;

        //initialize vars to get info about origin of the parameter being logged
        $procedure = $this->proceduremodel->getById(@$arr[ProcedureModel::PRIMARY_KEY]);
        $pipeline  = $this->pipelinemodel->getById(@$arr[PipelineModel::PRIMARY_KEY]);
        $currentRecord = $this->_getById($id);

        //prepare message
        if ($action == ChangeLogger::ACTION_UPDATE) {
            $message = 'Updated Parameter (' . @$arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY]
                     . ') ' . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name']
                     . ' to version ' . @$currentRecord['major_version'] . '.'
                     . @$currentRecord['minor_version'] . '. ';
            $fields = array(
                'name', 'type', 'visible', 'active', 'deprecated',
                'major_version', 'minor_version', 'derivation', 'description',
                'is_annotation', 'is_derived', 'is_important',
                'is_required', 'unit', 'qc_check', //'ontology_group_id',
                'qc_min', 'qc_max', 'qc_notes', 'value_type',
                'graph_type', 'data_analysis_notes', 'internal'
            );
            foreach ($fields AS $field) {
                if ($arr[$field] != $currentRecord[$field]) {
                    if ($field == 'unit') {
                        $this->load->model('unitmodel');
                        $a = $this->unitmodel->getById($arr[$field]);
                        $b = $this->unitmodel->getById($currentRecord[$field]);
                        $message .= $field . ' changed from ' . @$a['unit'] . ' to ' . @$b['unit'] . '. ';
                    } else {
                        $message .= $field . ' changed from ' . @$arr[$field] . ' to ' . @$currentRecord[$field] . '. ';
                    }
                }
            }
        } else if ($action == ChangeLogger::ACTION_CREATE) {
            $message = 'Created a new Parameter (' . $id . ') ' . @$arr['name'] . ' '
                     . 'in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') '
                     . @$procedure['name'] . ' of Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_DELETE) {
            $message = 'Deleted Parameter (' . $id . ') ' . @$arr['name'] . ' '
                     . 'from Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') '
                     . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_UNDELETE) {
            $message = 'Undeleted Parameter (' . $id . ') ' . @$arr['name'] . ' '
                     . 'in Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') '
                     . @$procedure['name'] . ' in Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_REVERT) {
            $message = 'Reverted current Parameter (' . @$currentRecord[self::PRIMARY_KEY]
                     . ') ' . @$arr['name'] . ' to revision ' . @$currentRecord['major_version']
                     . '.' . @$currentRecord['minor_version'];
        } else if ($action == ChangeLogger::ACTION_IMPORT) {
            $message = 'Imported a new Parameter (' . $id . ') ' . @$arr['name'] . ' '
                     . 'into Procedure (' . @$procedure[ProcedureModel::PRIMARY_KEY] . ') '
                     . @$procedure['name'] . ' of Pipeline ('
                     . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_VERSION) {
            $message = 'Created a new version of Parameter (' . $arr[self::PRIMARY_KEY] . ') '
                     . @$arr['name'] . ' -> ' . @$currentRecord['name'] . ' version '
                     . @$currentRecord['major_version'] . ' into Procedure ('
                     . @$procedure[ProcedureModel::PRIMARY_KEY] . ') ' . @$procedure['name']
                     . ' in Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY] . ') '
                     . @$pipeline['name'];
        } else if ($action == ChangeLogger::ACTION_CLONE) {
            $message = 'Cloned Parameter (' . $arr['srcParameterId'] . ') ' . $arr['srcParameterName']
                     . ' [' . $arr['srcParameterKey'] . '] and associated items to Parameter (' . $id
                     . ') ' . $arr['name'] . ' [' . $arr['parameter_key'] . '] in Procedure ('
                     . @$procedure[ProcedureModel::PRIMARY_KEY] . ') ' . @$procedure['name'] . ' in '
                     . 'Pipeline (' . @$pipeline[PipelineModel::PRIMARY_KEY] . ') ' . @$pipeline['name'];
        } else {
            return true;
        }

        //log it
        return ChangeLogger::log(
            array(
                ChangeLogger::FIELD_ITEM_ID => $id,
                ChangeLogger::FIELD_ITEM_KEY => (empty($currentRecord['parameter_key'])) ? @$arr['parameter_key'] : @$currentRecord['parameter_key'],
                ChangeLogger::FIELD_ITEM_TYPE => 'Parameter',
                ChangeLogger::FIELD_ACTION => $action,
                ChangeLogger::FIELD_PIPELINE => @$arr['pipeline_id'],
                ChangeLogger::FIELD_PROCEDURE => @$arr['procedure_id'],
                ChangeLogger::FIELD_PARAMETER => (isset($arr['new_parameter_id'])) ? $arr['new_parameter_id'] : @$arr['parameter_id'],
                ChangeLogger::FIELD_MESSAGE => $message,
                ChangeLogger::FIELD_INTERNAL => (int)(bool) (@$currentRecord['internal'] || @$arr['internal'] || @$procedure['internal'] || @$pipeline['internal'] || $this->isInternal(@$arr, true))
            )
        );
    }
}
