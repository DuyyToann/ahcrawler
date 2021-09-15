<?php

require_once 'crawler-base.class.php';

/**
 * ____________________________________________________________________________
 *          __    ______                    __             
 *   ____ _/ /_  / ____/________ __      __/ /__  _____    
 *  / __ `/ __ \/ /   / ___/ __ `/ | /| / / / _ \/ ___/    
 * / /_/ / / / / /___/ /  / /_/ /| |/ |/ / /  __/ /        
 * \__,_/_/ /_/\____/_/   \__,_/ |__/|__/_/\___/_/         
 * ____________________________________________________________________________ 
 * Free software and OpenSource * GNU GPL 3
 * DOCS https://www.axel-hahn.de/docs/ahcrawler/index.htm
 * 
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * 
 * ----------------------------------------------------------------------------
 * */
class counter extends crawler_base {

    /**
     * array with urls to crawl
     * @var array
     */
    private $_db_table = 'counters';
    private $_db_reltable = 'counteritems';
    
    /**
     * use only last counter set on a day
     * @var boolean
     */
    private $_bOneValuePerDay = true;
    
    /**
     * remove older counter values if older than N days
     * @var type 
     */
    private $_iKeepDays = 90;

    // ----------------------------------------------------------------------

    /**
     * new crawler
     * @param integer  $iSiteId  site-id of search index
      public function __construct() {
      return true;
      }
     */
    // ----------------------------------------------------------------------
    // private function
    // ----------------------------------------------------------------------

    /**
     * get id of a counter item as integer. If it does not exist and 
     * bAutoCreate is true then it will be generated.
     * Otherwise it returns false.
     * 
     * @param type $sCounterItem
     * @param type $bAutoCreate
     * @return boolean
     */
    protected function _getCounterId($sCounterItem, $bAutoCreate = false) {
        $aResult = $this->oDB->select(
                $this->_db_reltable,
                array('id', 'label'),
                array('label' => $sCounterItem)
        );
        if ($aResult && isset($aResult[0]['id']) && (int)$aResult[0]['id']) {
            return $aResult[0]['id'];
        }
        if ($bAutoCreate) {
            $aInsert = $this->oDB->insert(
                    $this->_db_reltable,
                    array('label' => $sCounterItem)
            );
            return $this->oDB->id();
        } else {
            return false;
        }
    }

    // ----------------------------------------------------------------------
    // SETTER
    // ----------------------------------------------------------------------

    /**
     * add a counter value
     * @param int    $iSiteId       id of website
     * @param string $sCounterItem  Name of the counter
     * @param string $sValue        Value of the counter as int/ astring/ json/ ...
     */
    public function add($iSiteId, $sCounterItem, $sValue) {
        $iCouterId = $this->_getCounterId($sCounterItem, true);
        if ($iCouterId) {
            
            // optional: delete all values of the current day
            if($this->_bOneValuePerDay){
                $aResult = $this->oDB->delete(
                    $this->_db_table,
                    array(
                        'siteid' => (int)$iSiteId,
                        'counterid' => $iCouterId,
                        'ts[>]' => date("Y-m-d"),
                    )
                );
            }
            $aResult = $this->oDB->insert(
                $this->_db_table,
                array(
                    'siteid' => (int)$iSiteId,
                    'counterid' => $iCouterId,
                    'value' => $sValue,
                    'ts' => date("Y-m-d H:i:s"),
                )
            );
            return $aResult;
        }
        return false;
    }

    // ----------------------------------------------------------------------
    // GETTER
    // ----------------------------------------------------------------------

    /**
     * cleanup older counter values of the current siteid
     * @return boolean
     */
    public function cleanup($iSiteId){
        $sDeleteBefore=date("Y-m-d", date("U") - $this->_iKeepDays*24*60*60);
        $aResult = $this->oDB->delete(
            $this->_db_table,
            array(
                'siteid' => (int)$iSiteId,
                // 'counterid' => $iCouterId,
                'ts[<]' => $sDeleteBefore,
            )
        );
        return true;
    }
    
    public function dump() {
        echo '<pre>'
        . print_r($this->oDB->select(
                    $this->_db_table,
                    array('[>]' . $this->_db_reltable => array('counterid' => 'id')),
                    array(
                        $this->_db_table.'.siteid',
                        $this->_db_table.'.counterid',
                        $this->_db_reltable.'.label',
                        $this->_db_table.'.value',
                        $this->_db_table.'.ts',
                    )
                )
                , 1)
        . 'SQL: ' . $this->oDB->last().'<br>'
        . '</pre>'
        ;
    }
    
    /**
     * get ids of existing counters
     */
    public function getCounterItems() {
        return $this->oDB->select($this->_db_reltable, 'label');
    }

    /**
     * get last set value of given counters
     * @param array  $aItems  list of wanted counters; default: all
     */
    public function getCountersLastitem($aItems = array()) {
        
    }

    /**
     * get last set values of given counter ids
     * @param string  $sCounter  name of counter
     */
    public function getCountersHistory($iSiteId, $sCounter = '') {
        $aReturn=$this->oDB->select(
            $this->_db_table,
            array('[>]' . $this->_db_reltable => array('counterid' => 'id')),
            array(
                // $this->_db_table.'.siteid',
                // $this->_db_table.'.counterid',
                // $this->_db_reltable.'.label',
                $this->_db_table.'.value',
                $this->_db_table.'.ts',
            ),
            array(
                $this->_db_table.'.siteid'=>$iSiteId,
                $this->_db_reltable.'.label'=>$sCounter,
            )
        );
        // echo 'SQL: ' . $this->oDB->last().'<br>';
        return $aReturn;
    }

}
