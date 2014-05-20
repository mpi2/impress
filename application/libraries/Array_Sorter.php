<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
// $Id: array_sorter.inc.php 82 2005-11-17 17:14:39Z aleczapka $
//

/**
* Handles multidimentional array sorting by a key (not recursive).
*
* @author Oliwier Ptak <aleczapka@gmail.com>
*
* @see http://uk.php.net/manual/en/function.uksort.php#71152
*
* @version 20120723 Edited by Ahmad Retha, line 53, added empty($this->sarry) check to fix bug
*
*/
class Array_Sorter
{
    var $skey = false;
    var $sarray = false;
    var $sasc = true;
    var $sas_object = false;

    /**
    * Constructor
    *
    * @access public
    * @param mixed $array array to sort
    * @param string $key array key to sort by
    * @param boolean $asc sort order (ascending or descending)
    */
    function array_sorter(&$array, $key, $asc=true, $as_object=false)
    {
        $this->sarray = $array;
        $this->skey = $key;
        $this->sasc = $asc;
        $this->sas_object = $as_object;
    }

    function debug()
    {
        echo "skey: ".$this->skey."<br>";
        echo "sasc: ".$this->sasc."<br>";
    }

    /**
    * Sort method
    *
    * @access public
    * @param boolean $remap if true reindex the array to reset indexes
    */
    function sortit($remap=true)
    {
        if (!is_array($this->sarray) || empty($this->sarray) || !array_key_exists($this->skey, @$this->sarray[0]))
            return $this->sarray;

        //$this->debug();

        uksort($this->sarray, array($this, "_as_cmp"));
        if ($remap)
        {
            $tmp = array();
            while (list($id, $data) = each($this->sarray))
                $tmp[] = $data;
            return $tmp;
        }
        return $this->sarray;
    }

    /**
    * Custom sort function
    *
    * @access private
    * @param mixed $a an array entry
    * @param mixed $b an array entry
    */
    function _as_cmp($a, $b)
    {
        //since uksort will pass here only indexes get real values from our array
        if (!is_array($a) && !is_array($b))
        {
            //sort objects
            if ($this->sas_object)
            {
                $obj_a = $this->sarray[$a];
                $obj_b = $this->sarray[$b];

                $str = "\$a = \$obj_a->$this->skey;";
                $str .= "\$b = \$obj_b->$this->skey;";
                eval($str);
            }
            else
            {
                $a = $this->sarray[$a][$this->skey];
                $b = $this->sarray[$b][$this->skey];
            }
        }

        //if string - use string comparision
        if (!ctype_digit($a) && !ctype_digit($b) &&
            !is_int($a) && !is_int($b))
        {
            if ($this->sasc)
                return strcasecmp($a, $b);
            else
                return strcasecmp($b, $a);
        }
        else
        {
            if ($a == $b)
                return 0;

            if ($this->sasc)
                return ($a > $b) ? 1 : -1;
            else
                return ($a > $b) ? -1 : 1;
        }
    }

}
