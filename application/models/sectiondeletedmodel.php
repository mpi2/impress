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
 * Section Deleted model
 */
class SectionDeletedModel extends CI_Model implements IUserIdCheckable, IRecyclable
{
    const TABLE = 'section_deleted';
    const PRIMARY_KEY = 'id';

    public function fetchAll()
    {
        $this->load->model('sectiontitlemodel');
        return $this->db->select('del.*, user.name AS username, sectiontitle.title AS title')
                        ->from(self::TABLE . ' AS del')
                        ->join($this->config->item('mousephenotypedb') . '.users user', 'user.uid = del.user_id', 'left')
                        ->join(SectionTitleModel::TABLE . ' sectiontitle', 'sectiontitle.' . SectionTitleModel::PRIMARY_KEY . ' = del.section_title_id', 'left')
                        ->order_by('del.time_modified', 'desc')
                        ->order_by('del.user_id')
                        ->get()
                        ->result_array();
    }

    public function getById($id)
    {
        return $this->db->from(self::TABLE)
                        ->where(self::PRIMARY_KEY, $id)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    private function _filterFields($arr)
    {
        $this->load->helper('keep_array_keys');
        $this->load->model('sectionmodel');
        $keys = $this->sectionmodel->getFields();
        if(($k = array_search('deleted', $keys)) !== false)
            unset($keys[$k]);
        return keep_array_keys($arr, $keys);
    }

    public function getBySectionId($sid)
    {
        return $this->db->from(self::TABLE)
                        ->where('section_id', $sid)
                        ->limit(1)
                        ->get()
                        ->row_array();
    }

    public function getBySOPId($sid)
    {
        return $this->db->from(self::TABLE)
                        ->where('sop_id', $sid)
                        ->order_by('weight')
                        ->get()
                        ->result_array();
    }

    public function purgeRecord($id)
    {
        $this->db->where(self::PRIMARY_KEY, $id)
                 ->delete(self::TABLE);
        return $this->db->affected_rows();
    }

    /**
    * @alias
    * @see SectionDeletedModel::purgeRecord()
    */
    public function permanentlyDelete($id)
    {
        return $this->purgeRecord($id);
    }

    /**
    * @param int|array $record either the id of a record in the section table
    * or an array of the record fields
    * @return int|bool insert id or false on failure
    */
    public function insert($record)
    {
        if (is_numeric($record)) {
            $this->load->model('sectionmodel');
            $record = $this->sectionmodel->getById($record);
        }

        if(empty($record))
            return false;

        $record['time_modified'] = $this->config->item('timestamp');
        $record['user_id'] = User::getId();
        $this->db->insert(self::TABLE, $this->_filterFields($record));
        return $this->db->insert_id();
    }

    /**
    * Restores the deleted record and reassociates it with the given parameter
	* @override
    * @param int $deletedRecordId
    * @param array $origin
    * @return int|bool insert id or false on failure
    */
    public function restore($deletedRecordId, array $origin)
    {
        //fetch the deleted record
        $deletedRecord = $this->getById($deletedRecordId);
        if(empty($deletedRecord) || empty($origin))
            return false;

        //prepare the record for restoration
        unset($deletedRecord['id']);
        $pipelineId = (int)@$origin['pipeline_id'];
        $procedureId = (int)@$origin['procedure_id'];
        $deletedRecord['user_id'] = User::getId();
        $deletedRecord['time_modified'] = $this->config->item('timestamp');
        $deletedRecord['pipeline_id'] = $pipelineId;
        $deletedRecord['procedure_id'] = $procedureId;

        //check the parents exist
        $this->load->model('pipelinehasproceduresmodel');
        $link = $this->pipelinehasproceduresmodel->getByPipelineAndProcedure($pipelineId, $procedureId);
        if(empty($link))
            return false;
        unset($link);
		
        //get the sop_id
        $this->load->model('sopmodel');
        $sop = $this->sopmodel->getByProcedure($procedureId);
        if(empty($sop))
                return false;
        $deletedRecord[SOPModel::PRIMARY_KEY] = $sop[SOPModel::PRIMARY_KEY];
        unset($sop);

        //check the destination id has not been reoccupied,
        //otherwise insert into a new destination
        $this->load->model('sectionmodel');
        // $rec = $this->sectionmodel->getById($deletedRecord['section_id']);
        // if( ! empty($rec))
            // $deletedRecord['section_id'] = null;
        $iid = $this->sectionmodel->insert($deletedRecord, ChangeLogger::ACTION_UNDELETE);

        //purge the record from the deleted table as it has now been restored
        if($iid)
            $this->purgeRecord($deletedRecordId);

        return $iid;
    }

    public function restoreSectionsForSOP($sopId, $origin)
    {
        $deletedSections = $this->getBySOPId($sopId);
        foreach ($deletedSections AS $section)
            $this->_restore($section[self::PRIMARY_KEY], $origin);
    }
	
    /**
    * @override
    * @see IRecyclable::getRecyclableFields()
    */
    public static function getRecyclableFields()
    {
        return array(
            static::R_ID => self::PRIMARY_KEY,
            static::R_NAME => 'title',
            static::R_DATE => static::R_DATE,
            static::R_USER => static::R_USER,
            static::R_FIELDS => array()
        );
    }
}
