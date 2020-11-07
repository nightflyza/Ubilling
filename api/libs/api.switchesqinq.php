<?php

/**
 * Switches QinQ management
 */
class SwitchesQinQ {

    /**
     * Contains all available QinQ data as switchid=>data
     *
     * @var array
     */
    protected $allQinQ = array();

    /**
     * Contains default table to store switches QinQ mapping data
     */
    const QINQ_TABLE = 'switches_qinq';

    /**
     * Creates new SwitchesQinQ instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadQinQ();
    }

    /**
     * Loads existing QinQ data into protected prop
     * 
     * @return void
     */
    protected function loadQinQ() {
        $query = "SELECT * from `" . self::QINQ_TABLE . "`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allQinQ[$each['switchid']] = $each;
            }
        }
    }

    /**
     * Public getter for 
     * 
     * @return array
     */
    public function getAllQinQ() {
        return ($this->allQinQ);
    }

    /**
     * Renders QinQ data edit form
     * 
     * @param int $switchId
     * 
     * @return string
     */
    public function renderEditForm($switchId) {
        $result = '';
        if (!empty($switchId)) {
            @$currentData = $this->allQinQ[$switchId];
            $qinqinputs = wf_HiddenInput('qinqswitchid', $switchId);
            $qinqinputs .= wf_TextInput('qinqsvlan', __('SVLAN'), @$currentData['svlan'], false, 4, 'digits') . ' ';
            $qinqinputs .= wf_TextInput('qinqcvlan', __('CVLAN'), @$currentData['cvlan'], false, 4, 'digits') . ' ';
            $qinqinputs .= wf_Submit(__('Apply'));
            $result .= wf_Form('', 'POST', $qinqinputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Checks for validity of svlan+cvlan pair
     * 
     * @param int $svlan
     * @param int $cvlan
     * @param int $switchId
     * 
     * @return bool

      protected function isValid($svlan, $cvlan, $switchId) {
      $result = true;
      if ((!empty($svlan)) AND ( !empty($cvlan))) {
      if (!empty($this->allQinQ)) {
      foreach ($this->allQinQ as $io => $each) {
      if (($each['cvlan'] == $cvlan) AND ( $each['svlan'] == $svlan)) {
      if ($io != $switchId) {
      $result = false;
      }
      }
      }
      }
      } else {
      $result = true;
      }
      return ($result);
      }
     * 
     */

    /**
     * Catches qinq data and saves it if required
     * 
     * @return void/string on error
     */
    public function saveQinQ() {
        $result = '';
        if (wf_CheckGet(array('qinqswitchid', 'svlan_id', 'cvlan_num'))) {
            $switchId = vf($_GET['qinqswitchid'], 3);
            $svlan = vf($_GET['svlan_id'], 3);
            $cvlan = vf($_GET['cvlan_num'], 3);
            //check is pair unique and not empty?
            //if ($this->isValid($svlan, $cvlan, $switchId)) {
            if (!isset($this->allQinQ[$switchId])) {
                //creating new QinQ record
                $query = "INSERT INTO `" . self::QINQ_TABLE . "` (`switchid`,`svlan_id`,`cvlan`) "
                        . "VALUES ('" . $switchId . "','" . $svlan . "','" . $cvlan . "');";
                nr_query($query);
                log_register('SWITCH CHANGE [' . $switchId . '] QINQ CREATE SVLAN ID `' . $svlan . '` CVLAN `' . $cvlan . '`');
            } else {
                //update mapping data if required
                $currentData = $this->allQinQ[$switchId];
                $where = "WHERE `switchid`='" . $switchId . "'";
                if ($currentData['svlan'] != $svlan) {
                    simple_update_field(self::QINQ_TABLE, 'svlan_id', $svlan, $where);
                    log_register('SWITCH CHANGE [' . $switchId . '] QINQ SET SVLAN `' . $svlan . '`');
                }

                if ($currentData['cvlan'] != $cvlan) {
                    simple_update_field(self::QINQ_TABLE, 'cvlan', $cvlan, $where);
                    log_register('SWITCH CHANGE [' . $switchId . '] QINQ SET CVLAN `' . $cvlan . '`');
                }
            }
            /*
              } else {
              $result .= __('SVLAN + CVLAN pair is not valid');
              }
             * 
             */
        }
        return ($result);
    }

}
