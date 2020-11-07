<?php

/**
 * Allows to put some switches into groups
 */
class SwitchGroups {
    /**
     * Contains default interface module URL
     */
    const URL_ME = '?module=switchgroups';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = null;

    /**
     * Placeholder for DEVICES_LISTS_SORT_BY_MODELNAME alter.ini option
     *
     * @var bool
     */
    protected $sortByModelName = false;


    public function __construct() {
        global $ubillingConfig;
        $this->initMessages();

        $this->sortByModelName = $ubillingConfig->getAlterParam('DEVICES_LISTS_SORT_BY_MODELNAME');
    }

    /**
     * Inits message helper object for further usage
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Returns reference to UbillingMessageHelper object
     *
     * @return object
     */
    public function getUbMsgHelperInstance() {
        return $this->messages;
    }

    /**
     * Gets switches groups data from DB
     *
     * @param string $whereString - WHERE clause for SQL query
     * @param bool $addSwCountColumn
     *
     * @return array
     */
    public function getSwitchGroupsData($whereString = '', $addSwCountColumn = false) {
        if ($addSwCountColumn) {
            $query = "SELECT `switch_groups`.`id`, `switch_groups`.`groupname`, `switch_groups`.`groupdescr`, `tGrpRel`.`sw_cnt`  
                        FROM `switch_groups`  
                          LEFT JOIN (SELECT DISTINCT `switch_groups_relations`.`sw_group_id`, count(`switch_groups_relations`.`sw_group_id`) AS `sw_cnt` 
                                        FROM `switch_groups_relations` 
                                          GROUP BY `switch_groups_relations`.`sw_group_id` 
                                          ORDER BY `switch_groups_relations`.`sw_group_id`) AS tGrpRel 
                            ON `tGrpRel`.`sw_group_id` =  `switch_groups`.`id`" .
                        $whereString;
        } else {
            $query = "SELECT * FROM `switch_groups` " . $whereString;
        }
        $result = simple_queryall($query);

        return ($result);
    }

    /**
     * Finds group Id by it's name
     *
     * @param $groupName
     *
     * @return int
     */
    public function getSwitchGroupIdByName($groupName) {
        $result = '';

        if (!empty($groupName)) {
            $query = "SELECT `id` FROM `switch_groups` WHERE `groupname`='" . $groupName . "'";
            $result = simple_queryall($query);
        }

        return ( empty($result) ) ? 0 : $result[0]['id'];
    }

    /**
     * Finds group name by it's Id
     *
     * @param $groupId
     *
     * @return int
     */
    public function getSwitchGroupNameById($groupId) {
        $result = '';

        if (!empty($groupId)) {
            $query = "SELECT `groupname` FROM `switch_groups` WHERE `id`= " . $groupId;
            $result = simple_queryall($query);
        }

        return ( empty($result) ) ? '' : $result[0]['groupname'];
    }

    /**
     * Returns array with all of the switches IDs and their group data side by side
     *
     * @return array
     */
    public function getSwitchesIdsWithGroupsData() {
        $result = array();

        $query = "SELECT `switch_groups_relations`.`switch_id`, `switch_groups`.`id` AS `groupid`, `switch_groups`.`groupname`, `switch_groups`.`groupdescr`
                    FROM `switch_groups_relations`
                      LEFT JOIN `switch_groups` ON `switch_groups_relations`.`sw_group_id` = `switch_groups`.`id`";

        $queryResult = simple_queryall($query);

        if (!empty($queryResult)) {
            foreach ($queryResult as $eachRec) {
                $result[$eachRec['switch_id']] = array( 'groupid' => $eachRec['groupid'],
                                                        'groupname' => $eachRec['groupname'],
                                                        'groupdescr' => $eachRec['groupdescr'] );
            }
        }

        return ($result);
    }

    /**
     * Returns switch group data by switch ID
     *
     * @param $switchId
     * @param string $returnVal - can be: 'all', 'name', 'id' - depending on what value you want to get
     *
     * @return array|string|int
     */
    public function getSwitchGroupBySwitchId($switchId, $returnVal = 'id') {
        $result = '';
        $query = "SELECT `switch_groups`.`id`, `switch_groups`.`groupname`, `switch_groups`.`groupdescr`
                    FROM `switch_groups_relations`
                      LEFT JOIN `switch_groups` ON `switch_groups_relations`.`sw_group_id` = `switch_groups`.`id`
                    WHERE `switch_groups_relations`.`switch_id` = " . $switchId;

        $queryResult = simple_queryall($query);

        if (!empty($queryResult)) {
            switch ($returnVal) {
                case 'all':
                    $result = $queryResult;
                    break;

                case 'name':
                    $result = $queryResult[0]['groupname'];
                    break;

                default:
                    $result = $queryResult[0]['id'];
            }
        }

        return ($result);
    }

    /**
     * Returns how many switches are there in a group
     *
     * @param $groupId
     *
     * @return int
     */
    public function countSwitchesInGroup($groupId) {
        $result = '';

        if (!empty($groupId)) {
            $query = "SELECT count(*) AS `sw_cnt` FROM `switch_groups_relations` WHERE `sw_group_id`=" . $groupId;
            $result = simple_queryall($query);
        }

        return ( empty($result) ) ? 0 : $result[0]['sw_cnt'];
    }

    /**
     * Returns array of switches in a group
     *
     * @param $swGroupId
     *
     * @return array
     */
    public function getSwithcesInGroup($swGroupId) {
        $queryOrderBy = ($this->sortByModelName) ? ' ORDER BY `switchmodels`.`modelname` ' : '';

        $query = "SELECT `swgrp`.`id`, `swgrp`.`ip`, `swgrp`.`location`, `swgrp`.`groupname`, `switchmodels`.`modelname` 
                    FROM ( SELECT `switches`.`id`, `switches`.`modelid`, `switches`.`ip`, `switches`.`location`, `switch_groups`.`groupname`  
                          FROM `switch_groups_relations`
                            LEFT JOIN `switches` ON `switch_groups_relations`.`switch_id` = `switches`.`id` 
                            LEFT JOIN `switch_groups` ON `switch_groups_relations`.`sw_group_id` = `switch_groups`.`id` 
                          WHERE `switch_groups_relations`.`sw_group_id` = " . $swGroupId . " ) AS `swgrp`                         
                        LEFT JOIN `switchmodels` ON `swgrp`.`modelid` = `switchmodels`.`id`" . $queryOrderBy;

        $result = simple_queryall($query);

        return ($result);
    }

    /**
     * Returns HTML table with switches list in a group
     *
     * @param $swGroupId
     *
     * @return string
     */
    public function renderSwitchesInGroupTable($swGroupId) {
        $tableBody = '';
        $totalCount = 0;
        $swGroupName = $this->getSwitchGroupNameById($swGroupId);

        $tableCells = wf_TableCell(__('ID'));
        $tableCells.= wf_TableCell(__('IP'));
        $tableCells.= wf_TableCell(__('Model'));
        $tableCells.= wf_TableCell(__('Location'));
        $tableCells.= wf_TableCell(__('Actions'));
        $tableRows = wf_TableRow($tableCells, 'row1');

        $switches = $this->getSwithcesInGroup($swGroupId);

        if (!empty($switches)) {
            foreach ($switches as $eachSwitch) {
                $tableCells = wf_TableCell($eachSwitch['id']);
                $tableCells.= wf_TableCell($eachSwitch['ip']);
                $tableCells.= wf_TableCell($eachSwitch['modelname']);
                $tableCells.= wf_TableCell($eachSwitch['location']);
                $tableCells.= wf_TableCell(wf_Link('?module=switches&edit=' . $eachSwitch['id'], web_edit_icon()) .
                                                 wf_Link('http://' . $eachSwitch['ip'], wf_img('skins/ymaps/network.png'),
                                                         false, '', 'target="_blank" title="' . __('Go to the web interface') . '"') );
                $tableRows.= wf_TableRow($tableCells, 'row3');

                $totalCount++;
            }
        }

        $tableBody = wf_tag('h3') . __('Switches of the group') . ':' . wf_nbsp(2) . $swGroupName . wf_tag('h3', true);
        $tableBody.= wf_delimiter(0);
        $tableBody.= wf_TableBody($tableRows, '100%', '0', 'sortable');
        $tableBody.= wf_tag('h4') . __('Total') . wf_nbsp(2) . $totalCount . wf_tag('h4', true);
        return ($tableBody);
    }


    public function renderSwitchGroupsSelector($selectorName = 'swgroup', $switchId = 0) {
        $result = '';
        $swGroupsArray = array('0' => '-');
        $swGroups = $this->getSwitchGroupsData();
        $swGroupId = $this->getSwitchGroupBySwitchId($switchId);

        if (!empty($swGroups)) {
            foreach ($swGroups as $eachRec => $eachGroup) {
                $swGroupsArray[$eachGroup['id']] =  $eachGroup['groupname'];
            }

            $result = wf_Selector($selectorName, $swGroupsArray, __('Switch group'), $swGroupId, false, true);
        }

        return ($result);
    }

    /**
     * Renders JSON for JQDT
     *
     * @param $queryData
     */
    public function renderJSON($queryData) {
        $json = new wf_JqDtHelper();

        if (!empty($queryData)) {
            $data = array();
            $groupId = 0;

            foreach ($queryData as $eachRec) {
                foreach ($eachRec as $fieldName => $fieldVal) {
                    if ($fieldName == 'id') {
                        $groupId = $fieldVal;
                    }

                    $data[] = $fieldVal;
                }

                $linkId1 = wf_InputId();
                $linkId2 = wf_InputId();
                $actions = wf_JSAlert(  '#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteSWGroup(' . $eachRec['id'] . ', \'' . self::URL_ME . '\', \'delSWGroup\', \'' . wf_InputId() . '\')') . wf_nbsp();
                $actions.= wf_Link('#', web_edit_icon(), false, '', 'id="' . $linkId1 . '"') . wf_nbsp();
                $actions.= wf_Link('#', wf_img_sized('skins/ymaps/switchdir.png', __('Show switches in this group'), '16', '16'), false, '', 'id="' . $linkId2 . '"');

                $actions.= wf_tag('script', false, '', 'type="text/javascript"');
                $actions.= wf_JSAjaxModalOpener(self::URL_ME, array('swgroupedit' => 'true', 'swgroupid' => $groupId), $linkId1, false, 'POST');
                $actions.= wf_JSAjaxModalOpener(self::URL_ME, array('showswingroup' => 'true', 'swgroupid' => $groupId), $linkId2, false, 'POST');
                $actions.= wf_tag('script', true);

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns JQDT control and some JS bindings for dynamic forms
     *
     * @return string
     */
    public function renderJQDT() {
        $ajaxUrlStr = '' . self::URL_ME . '&ajax=true' . '';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);
        $errorModalWindowId = wf_InputId();
        $columns = array();
        $opts = '"order": [[ 0, "asc" ]]';

        $columns[] = __('ID');
        $columns[] = __('Name');
        $columns[] = __('Description');
        $columns[] = __('Switch count');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxUrlStr, false, __('results'), 100, $opts);

        $result.= wf_tag('script', false, '', 'type="text/javascript"');
        $result.= wf_JSEmptyFunc();
        $result.= wf_JSElemInsertedCatcherFunc();
        $result.= ' function chekEmptyVal(ctrlCalssName) {
                        $(document).on("focus keydown", ctrlCalssName, function(evt) {
                            if ( $(ctrlCalssName).css("border-color") == "rgb(255, 0, 0)" ) {
                                $(ctrlCalssName).val("");
                                $(ctrlCalssName).css("border-color", "");
                                $(ctrlCalssName).css("color", "");
                            }
                        });
                    }
                     
                    onElementInserted(\'body\', \'.__SWGroupEmptyCheck\', function(element) {
                        chekEmptyVal(\'.__SWGroupEmptyCheck\');
                    });
                            
                    $(document).on("submit", ".__SWGroupForm", function(evt) {
                        var FrmAction        = $(".__SWGroupForm").attr("action");
                        var FrmData          = $(".__SWGroupForm").serialize() + \'&errfrmid=' . $errorModalWindowId . '\';
                        //var modalWindowId    = $(".__SWGroupForm").closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                    
                        var emptyCheckClass = \'.__SWGroupEmptyCheck\';
                    
                        if ( empty( $(emptyCheckClass).val() ) || $(emptyCheckClass).css("border-color") == "rgb(255, 0, 0)" ) {                            
                            $(emptyCheckClass).css("border-color", "red");
                            $(emptyCheckClass).css("color", "grey");
                            $(emptyCheckClass).val("' . __('Mandatory field') . '");                            
                        } else {
                            $.ajax({
                            type: "POST",
                            url: FrmAction,
                            data: FrmData,
                            success: function(result) {
                                        if ( !empty(result) ) {                                            
                                            $(document.body).append(result);                                                
                                            $( \'#' . $errorModalWindowId . '\' ).dialog("open");                                                
                                        } else {
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                            $("[name=swgroupname]").val("");
                                            
                                            if ( $(".__CloseFrmOnSubmitChk").is(\':checked\') ) {
                                                $( \'#\'+$(".__SWGroupFormModalWindowId").val() ).dialog("close");
                                            }
                                        }
                                    }                        
                            });
                        }
                    });
    
                    function deleteSWGroup(swGroupId, ajaxURL, actionName, errFrmId) {
                        var ajaxData = \'&\'+ actionName +\'=true&swgroupid=\' + swGroupId + \'&errfrmid=\' + errFrmId                    
                    
                        $.ajax({
                                type: "POST",
                                url: ajaxURL,
                                data: ajaxData,
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+errFrmId).dialog("open");
                                            }
                                            
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }                  
                  ';
        $result.= wf_tag('script', true);

        return ($result);
    }

    /**
     * Returns switch group addition form
     *
     * @return string
     */
    public function renderAddForm($modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $inputs = wf_TextInput('swgroupname', __('Name'), '', true, '', '', '__SWGroupEmptyCheck');
        $inputs.= wf_TextInput('swgroupdescr', __('Description'), '', true);
        $inputs .= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__SWGroupFormModalWindowId');
        $inputs .= wf_HiddenInput('swgroupcreate', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SWGroupForm', '', $formId);

        return ($form);
    }

    /**
     * Returns switch group editing form
     *
     * @return string
     */
    public function renderEditForm($swGroupId, $modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $swGroupData = $this->getSwitchGroupsData(" WHERE `id` = " . $swGroupId);

        $swGroupName = $swGroupData[0]['groupname'];
        $swGroupDescription = $swGroupData[0]['groupdescr'];

        $inputs = wf_TextInput('swgroupname', __('Name'), $swGroupName, true, '', '', '__SWGroupEmptyCheck');
        $inputs.= wf_TextInput('swgroupdescr', __('Description'), $swGroupDescription, true);
        $inputs .= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__SWGroupFormModalWindowId');
        $inputs .= wf_HiddenInput('swgroupedit', 'true');
        $inputs .= wf_HiddenInput('swgroupid', $swGroupId);
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Edit'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SWGroupForm', '', $formId);

        return ($form);
    }

    /**
     * Adds switch group to DB
     *
     * @param $swGroupName
     * @param $swGroupDescr
     */
    public function addSwitchGroup($swGroupName, $swGroupDescr) {
        $query = "INSERT INTO `switch_groups` (`id`, `groupname`, `groupdescr`) VALUES (NULL, '" . $swGroupName . "', '" . $swGroupDescr . "')";
        nr_query($query);
        log_register('CREATE switch group [' . $swGroupName . ']');
    }

    /**
     * Edits switch group
     *
     * @param $swGroupId
     * @param $swGroupName
     * @param $swGroupDescr
     */
    public function editSwitchGroup($swGroupId, $swGroupName, $swGroupDescr) {
        $query = "UPDATE `switch_groups` 
                        SET `groupname` = '" . $swGroupName . "', 
                            `groupdescr` = '" . $swGroupDescr . "'
                    WHERE `id`= '" . $swGroupId . "'";

        nr_query($query);
        log_register('CHANGE switch group [' . $swGroupId . '] `' . $swGroupName);
    }

    /**
     * Deletes switch group
     *
     * @param $swGroupId
     * @param string $swGroupName
     * @param string $smsServiceAlphaName
     */
    public function deleteSwitchGroup($swGroupId, $swGroupName = '') {
        $query = "DELETE FROM `switch_groups` WHERE `id` = '" . $swGroupId . "';";
        nr_query($query);
        log_register('DELETE switch group [' . $swGroupId . '] ` ' . $swGroupName);
    }

    /**
     * Check if switch group is protected from deletion
     *
     * @param $swGroupId
     *
     * @return bool
     */
    public function checkSwitchGroupProtected($swGroupId) {
        $query = "SELECT `id` FROM `switch_groups_relations` WHERE `sw_group_id` = " . $swGroupId . ";";
        $result = simple_queryall($query);

        return (!empty($result));
    }

    /**
     * Returns true if switch group with such name already exists
     *
     * @param $swGroupName
     * @param int $excludeEditedGroupId
     *
     * @return string
     */
    public function checkSwitchGroupNameExists($swGroupName, $excludeEditedGroupId = 0) {
        $swGroupName = trim($swGroupName);

        if (empty($excludeEditedGroupId)) {
            $query = "SELECT `id` FROM `switch_groups` WHERE `groupname` = '" . $swGroupName . "';";
        } else {
            $query = "SELECT `id` FROM `switch_groups` WHERE `groupname` = '" . $swGroupName . "' AND `id` != '" . $excludeEditedGroupId . "';";
        }

        $result = simple_queryall($query);

        return ( empty($result) ) ? '' : $result[0]['id'];
    }

    public function removeSwitchFromGroup($switchId) {
        $query = "DELETE from `switch_groups_relations` WHERE `switch_id`='" . $switchId . "'";
        nr_query($query);
    }
}

?>