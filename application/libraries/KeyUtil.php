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
 * Handles all things key to key generation and validation. Static methods throughout.
 * There are different key signatures based on what the item is:
 *     - Pipeline:  ESLIM_001
 *     - Procedure: ESLIM_XRY_001
 *     - Parameter: ESLIM_XRY_001_002
 * The key is a functional key split into triples or parts that have a meaning:
 * PREFIX_{PIPELINE|PROCEDUREKEY}_{PROCVERSION|PARAMETER}_PARAMVERSION
 * The triples help identify the origin and type of an item and also its version.
 */
class KeyUtil
{
    /**
    * The parts of a key used as array keys when a get{Item}KeyParts() method is called
    */
    const PREFIX       = 'prefix';
    const PIPELINE     = 'pipeline';
    const PROCKEY      = 'prockey';
    const PARAMETER    = 'param';
    const PROCVERSION  = 'procversion';
    const PARAMVERSION = 'paramversion';
    /**
    * ProcKey is being used in the current implementation instead of ProcNum.
    * ProcNum is for deprecated-style keys like GMC_908_001
    * @deprecated KeyUtil::PROCNUM is not used for newly generated keys
    */
    const PROCNUM      = 'procnum';


    /**
    * @param string|int $keyorid Supply a string key like ESLIM_XRY_001_001 or a parameter id and it will fetch the key from that
    * @return array|boolean FALSE if id not found or key invalid, or it will return an array with the keys: prefix, proc, procversion, param, paramversion
    */
    public static function getParameterKeyParts($keyorid)
    {
        //if an id is supplied fetch the key
        if (is_numeric($keyorid)) {
            $CI =& get_instance();
            $param = $CI->parametermodel->getById($keyorid);
            if (empty($param))
                return FALSE;
            $keyorid = $param['parameter_key'];
        }

        //check it's valid
        if ( ! self::isValidParameterKey($keyorid))
            return FALSE;
        $split = explode('_', $keyorid);

        return array_combine(array(self::PREFIX, self::PROCKEY, self::PARAMETER, self::PARAMVERSION), $split);
    }

    /**
    * @param array $parts
    * @return bool|string A key string or false if array parts expected were not present
    */
    public static function joinParameterKeyParts($parts)
    {
        if ( ! array_key_exists(self::PREFIX, $parts) ||
             ! array_key_exists(self::PROCKEY, $parts) ||
             ! array_key_exists(self::PARAMVERSION, $parts) ||
             ! array_key_exists(self::PARAMETER, $parts) )
            return FALSE;

        $newKey = $parts[self::PREFIX] . '_' . $parts[self::PROCKEY] . '_' . self::getTriple($parts[self::PARAMETER]) . '_' . self::getTriple($parts[self::PARAMVERSION]);
        return (self::isValidParameterKey($newKey)) ? $newKey : FALSE;
    }

    /**
    * @param string|int $keyorid Supply a string key like ESLIM_XRY_001 or a procedure id and it will fetch the key from that
    * @return array|boolean FALSE if id not found or key invalid, or it will return an array with the keys: prefix, proc, version
    */
    public static function getProcedureKeyParts($keyorid)
    {
        //if an id is supplied then fetch the key
        if (is_numeric($keyorid)) {
            $CI =& get_instance();
            $proc = $CI->proceduremodel->getByid($keyorid);
            if (empty($proc))
                return FALSE;
            $keyorid = $proc['procedure_key'];
        }

        //check it's valid
        if ( ! self::isValidProcedureKey($keyorid))
            return FALSE;
        $split = explode('_', $keyorid);

        return array_combine(array(self::PREFIX, self::PROCKEY, self::PROCVERSION), $split);
    }

    /**
    * @param array $parts
    * @return bool|string A key string or false if array parts expected were not present
    */
    public static function joinProcedureKeyParts($parts)
    {
        if ( ! array_key_exists(self::PREFIX, $parts) ||
             ! array_key_exists(self::PROCKEY, $parts) ||
             ! array_key_exists(self::PROCVERSION, $parts))
            return FALSE;

        $newKey = $parts[self::PREFIX] . '_' . $parts[self::PROCKEY] . '_' . self::getTriple($parts[self::PROCVERSION]);
        return (self::isValidProcedureKey($newKey)) ? $newKey : FALSE;
    }

    /**
    * @param string|int $keyorid Supply a string key like ESLIM_001 or a pipeline id and it will fetch the key from that
    * @return array|boolean FALSE if id not found or key invalid, or it will return an array with the keys: prefix, pipeline
    */
    public static function getPipelineKeyParts($keyorid)
    {
        //if an id is supplied then fetch the key
        if (is_numeric($keyorid)) {
            $CI =& get_instance();
            $pip = $CI->pipelinemodel->getById($keyorid);
            if(empty($pip))
                return FALSE;
            $keyorid = $pip['pipeline_key'];
        }

        //check it's valid
        if ( ! self::isValidPipelineKey($keyorid))
            return FALSE;
        $split = explode('_', $keyorid);

        return array_combine(array(self::PREFIX, self::PIPELINE), $split);
    }

    /**
    * @param array $parts
    * @return bool|string A key string or false if array parts expected were not present
    */
    public static function joinPipelineKeyParts($parts)
    {
        if ( ! array_key_exists(self::PREFIX, $parts) ||
             ! array_key_exists(self::PIPELINE, $parts))
            return FALSE;

        $newKey = $parts[self::PREFIX] . '_' . self::getTriple($parts[self::PIPELINE]);
        return (self::isValidPipelineKey($newKey)) ? $newKey : FALSE;
    }

    /**
    * Make an integer number a triple string - 1 --> 001
    * @param int|null $digit Expects a positive number. if null supplied or negative number it will return 001
    * @return string triple number string
    */
    public static function getTriple($digit = null)
    {
        if (empty($digit) || ! is_numeric($digit) || (int)$digit < 1 || (int)$digit > 999)
            return '001';

        $digit = (int)$digit;

        if (strlen($digit) == 1)
            return "00$digit";
        else if (strlen($digit) == 2)
            return "0$digit";
        else
            return "$digit";
    }
    
    /**
    * @param string $key
    * @return string Prefix
    */
    public static function getPrefix($key)
    {
        $parts = explode('_', $key);
        return $parts[0];
    }
    
    /**
     * @param string $key1
     * @param string $key2
     * @return bool
     */
    public static function prefixesMatch($key1, $key2)
    {
        return (self::getPrefix($key1) == self::getPrefix($key2));
    }

    /**
    * Generates a new parameter key given its procedure. Doesn't check the key already exists in the db though.
    * Calling KeyUtil::generateNewParameterKey(91, null, 13) will currently return ESLIM_XRY_001_013
    * @param int $procId The id of the procedure
    * @param int $paramId The id of the parameter
    * @param int $paramVersion optional parameter version
    * @param bool $changeKeyType If I'm taking an XRY parameter and putting it into an DXA procedure should the key change and take the new type of the DXA procedure?
    * @return string|bool FALSE if procedure not found or returns the new Parameter Key like so: IMPC_{prockey}_{paramversion}_{parameter}
    */
    public static function generateNewParameterKey($procId, $paramId = null, $paramVersion = null, $changeKeyType = FALSE)
    {
        //get the procKey from the procedure (ESLIM_XRY)
        $CI =& get_instance();
        $proc = $CI->proceduremodel->getById($procId);
        if (empty($proc))
            return FALSE;
        $procKeyParts = self::getProcedureKeyParts($proc['procedure_key']);
        $prefix  = $procKeyParts[self::PREFIX];
        $procKey = $procKeyParts[self::PROCKEY];

        //get the parameter number (ESLIM_XRY_{001}_001) and also get the procKey from the parameter key if $changeKeyType==FALSE
        $parameter = null;
        if ($paramId == null) {
            $paramNum = 1;
        } else {
            $parameter = $CI->parametermodel->getById($paramId);
            if(empty($parameter))
                return FALSE;
            $paramKeyParts = self::getParameterKeyParts($parameter['parameter_key']);
            $paramNum = $paramKeyParts[self::PARAMETER];
            if($changeKeyType == FALSE)
                $procKey = $paramKeyParts[self::PROCKEY];
        }
        $paramNum = self::getTriple($paramNum);

        //The Parameter version latched on to the end (ESLIM_XRY_001_{013})
        if (is_numeric($paramVersion)) {
            $paramVersion = self::getTriple((int)$paramVersion);
        } else if ($parameter) {
            $incrementedParamKey = self::incrementParameterKeyVersion($parameter['parameter_key']);
            $paramKeyParts = self::getParameterKeyParts($incrementedParamKey);
            $paramVersion = self::getTriple($paramKeyParts[self::PARAMVERSION]);
        } else {
            $paramVersion = self::getTriple(1);
        }

        $newKey = $prefix . '_' . $procKey . '_' . $paramNum . '_' . $paramVersion;
        return (self::isValidParameterKey($newKey)) ? $newKey : FALSE;
    }

    /**
    * Generates a new procedure key given its pipeline. By default, if you supply
    * the id for pipeline JAX_001 and Procedure Type id for Body Weight (BWT), the
    * resulting key will be JAX_BWT_001, if there are no other Body Weight Procedures
    * already in that Pipeline. If the $oldPipId and $useOldPipKey parameters are
    * set then the Prefix, like IMPC, will be used in generating the key
    *
    * @param int $pipId The pipeline to place the new Procedure in
    * @param int $procTypeId The type of procedure based on the list from the procedure_type table
    * @return string|bool FALSE if pipeline not found or the new Procedure Key like so: IMPC_001(procedure id)_{version}
    */
    public static function generateNewProcedureKey($pipId, $procTypeId)
    {
        //we need the prefix from the pipeline key
        $CI =& get_instance();
        $pip = $CI->pipelinemodel->getById($pipId);
        if (empty($pip))
            return FALSE;
        $pipelineParts = self::getPipelineKeyParts($pip['pipeline_key']);
        $prefix = $pipelineParts[self::PREFIX];

        //we need the TLA key of the Procedure Type
        $CI->load->model('proceduretypemodel');
        $procType = $CI->proceduretypemodel->getById($procTypeId);
        if (empty($procType))
            return FALSE;
        $procTypeKey = $procType['key'];

        //we need the next version of this Procedure Type
        $currentMaxProcedure = $CI->proceduremodel->getLastProcedure($procTypeId, $pipId);
        $currentMaxKey = $currentMaxProcedure['procedure_key'];
        if (empty($currentMaxKey)) {
            $newVersion = 1;
        } else {
            $incrementedVersionKey = self::incrementProcedureKeyVersion($currentMaxKey);
            $newVersion = self::getVersionFromProcedureKey($incrementedVersionKey);
        }

        $newKey = $prefix . '_' . $procTypeKey . '_' . self::getTriple($newVersion);
        return (self::isValidProcedureKey($newKey)) ? $newKey : FALSE;
    }
	
	/**
    * Generates a new pipeline key given a prefix. Doesn't check the key already exists in the db though.
    * @param string $prefix Must be UPPER-CASE
    * @param int $version optional
    * @return string|bool FALSE if invalid prefix or the new Pipeline Key like so: {PREFIX}_{version}
    */
    public static function generateNewPipelineKey($prefix = null, $version = 1)
    {
		$newKey = $prefix . '_' . self::getTriple($version);
        return (self::isValidPipelineKey($newKey)) ? $newKey : FALSE;
    }

    /**
    * Returns a pipeline key with the version incremented e.g. IMPC_001 --> IMPC_002
    * @param string $key
    * @param string|bool $key with the incremented version or false on failure
    */
    public static function incrementPipelineKeyVersion($key)
    {
        $parts = self::getPipelineKeyParts($key);
        if ($parts === FALSE || $parts[self::PIPELINE] == '999')
            return FALSE;

        $parts[self::PIPELINE] = self::getTriple(1 + (int)$parts[self::PIPELINE]);

        return self::joinPipelineKeyParts($parts);
    }

    /**
    * Returns a procedure key with the version incremented e.g. IMPC_XRY_001 --> IMPC_XRY_002
    * @param string $key
    * @return string|bool $key with incremented version or false on failure
    */
    public static function incrementProcedureKeyVersion($key)
    {
        $parts = self::getProcedureKeyParts($key);
        if ($parts === FALSE || $parts[self::PROCVERSION] == '999')
            return FALSE;

        $parts[self::PROCVERSION] = self::getTriple(1 + (int)$parts[self::PROCVERSION]);

        return self::joinProcedureKeyParts($parts);
    }

    /**
    * Returns a parameter key with the version incremented e.g. IMPC_XRY_001_001 --> IMPC_XRY_001_002
    * @param string $key
    * @return string|bool $key with incremented version or false on failure
    */
    public static function incrementParameterKeyVersion($key)
    {
        $parts = self::getParameterKeyParts($key);
        if ($parts === FALSE || $parts[self::PARAMVERSION] == '999')
            return FALSE;

        $parts[self::PARAMVERSION] = self::getTriple(1 + (int)$parts[self::PARAMVERSION]);

        return self::joinParameterKeyParts($parts);
    }

    /**
    * @param string $key
    * @return bool
    */
    public static function parameterKeyExists($key)
    {
        $CI =& get_instance();
        $param = $CI->parametermodel->getByKey($key);
        return ! empty($param);
    }

    /**
    * @param string $key
    * @return bool
    */
    public static function procedureKeyExists($key)
    {
        $CI =& get_instance();
        $proc = $CI->proceduremodel->getByKey($key);
        return ! empty($proc);
    }
	
	/**
	* @param string $key TLA
	* @return bool
	*/
	public static function procedureTypeKeyExists($key)
	{
		$CI =& get_instance();
		$CI->load->model('proceduretypemodel');
		$type = $CI->proceduretypemodel->getByKey($key);
		return ! empty($type);
	}

    /**
    * A valid prefix is an uppercase char string which may contain hyphens and must be 3 - 8 chars long
    * @param string $prefix
    * @return bool
    */
    public static function isValidPrefix($prefix = null)
    {
        return (bool) preg_match('/^[A-Z\-]{3,8}$/', $prefix);
    }

    /**
    * A valid TLA or PROCKEY is an uppercase string three chars long containing only letters
    * @param string $TLA
    * @return bool
    */
    public static function isValidTLA($TLA = null)
    {
        return preg_match('/^[A-Z]{3}$/', $TLA) != 0;
    }

    /**
    * A valid triple is a three digit long string and ranges from 001-999
    * @param string $triple
    * @return bool
    */
    public static function isValidTriple($triple = null)
    {
        return preg_match('/^\d\d\d$/', $triple) != 0;
    }

    /**
    * Checks it has 4 parts - a valid prefix, procedure key and three valid triples
    * @param string $key
    * @return bool
    */
    public static function isValidParameterKey($key = null)
    {
        if (empty($key))
            return FALSE;
        $split = explode('_', $key);
        if ($split === FALSE)
            return FALSE;
        if (count($split) != 4)
            return FALSE;
        if ( ! self::isValidPrefix($split[0]))
            return FALSE;
        if ( ! (self::isValidTLA($split[1]) || self::isValidTriple($split[1])))
            return FALSE;
        if ( ! self::isValidTriple($split[2]) || ! self::isValidTriple($split[3]))
            return FALSE;

        return TRUE;
    }

    /**
    * Checks it has 3 parts - a valid prefix, procedure key and a version triple
    * @param string $key
    * @return bool
    */
    public static function isValidProcedureKey($key = null)
    {
        if (empty($key))
            return FALSE;
        $split = explode('_', $key);
        if ($split === FALSE)
            return FALSE;
        if (count($split) != 3)
            return FALSE;
        if ( ! self::isValidPrefix($split[0]))
            return FALSE;
        if ( ! (self::isValidTLA($split[1]) || self::isValidTriple($split[1])))
            return FALSE;
        if ( ! self::isValidTriple($split[2]))
            return FALSE;

        return TRUE;
    }

    /**
    * Checks it has 2 parts - a valid prefix and a valid version triple
    * @param string $key
    * @return bool
    */
    public static function isValidPipelineKey($key = null)
    {
        if (empty($key))
            return FALSE;
        $split = explode('_', $key);
        if ($split === FALSE)
            return FALSE;
        if (count($split) != 2)
            return FALSE;
        if ( ! self::isValidPrefix($split[0]))
            return FALSE;
        if ( ! self::isValidTriple($split[1]))
            return FALSE;

        return TRUE;
    }

    /**
    * @param string $key
    */
    public static function getVersionFromParameterKey($key)
    {
        $parts = self::getParameterKeyParts($key);
        if ($parts === FALSE) return FALSE;
        return (int)$parts[self::PARAMVERSION];
    }

    /**
    * @param string $key
    */
    public static function getVersionFromProcedureKey($key)
    {
        $parts = self::getProcedureKeyParts($key);
        if ($parts === FALSE) return FALSE;
        return (int)$parts[self::PROCVERSION];
    }

    /**
    * @param string $key
    */
    public static function getVersionFromPipelineKey($key)
    {
        $parts = self::getPipelineKeyParts($key);
        if ($parts === FALSE) return FALSE;
        return (int)$parts[self::PIPELINE];
    }

    /**
    * @param string $key Expects a stubb to convert. If something other than a valid stubb is passed it just returns it unchanged
    * @return string key
    */
    public static function convertPipelineStubbToKey($key)
    {
        if (self::isValidPipelineStubb($key))
            $key = $key . '_001';
        return $key;
    }

    /**
    * FYI the rules for the validity of the stubb are stricter than KeyUtil::isValidPrefix() because I don't want people creating new keys with hyphens
    * in them but older keys like M-G-P still need to validate so that's why that method is kept
    * @param string $stubb A stubb is the "IMPC" part of a Pipeline Key like "IMPC_001"
    * @return bool
    * @see KeyUtil::isValidPrefix()
    */
    public static function isValidPipelineStubb($stubb = '')
    {
        return (bool) preg_match('/^[A-Z]{3,8}$/', $stubb);
    }

    /**
    * @param string $stubb
    * @return bool
    */
    public static function isUniquePipelineStubb($stubb)
    {
        $CI =& get_instance();
        return $CI->pipelinemodel->isUniqueStubb($stubb);
    }

    /**
    * @param string $key
    * @return bool
    */
    public static function isDeprecatedKey($key = '')
    {
        $split = explode('_', $key);
        if (sizeof($split) >= 3) {
            return self::isValidTriple($split[1]);
        } else if (sizeof($split) == 2) {
            $p = new Pipeline($key);
            if($p->exists())
                return $p->isDeprecated();
        }
        return false;
    }
}
