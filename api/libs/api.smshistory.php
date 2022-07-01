<?php

/**
  I ain't happy, I'm feeling glad
  I got sunshine in a bag
  I'm useless, but not for long
  The future is coming on
 */
class SMSHistory {

    const URL_ME = '?module=smshistory';

    protected $smsAdvancedEnabled = false;

    public function __construct() {
        global $ubillingConfig;
        $this->smsAdvancedEnabled = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
    }

    /**
     * Gets sms history data from DB
     *
     * @param string $WHEREString
     *
     * @return array
     */
    public function getSMSHistoryData($WHEREString = '') {
        if (empty($WHEREString)) {
            $WHEREString = "WHERE DATE(`date_send`) = CURDATE()";
        }

        $tQuery = "SELECT * FROM `sms_history` " . $WHEREString . " ;";
        $Result = simple_queryall($tQuery);

        return $Result;
    }

    /**
     * Renders JSON for JQDT
     *
     * @param $QueryData
     */
    public function renderJSON($QueryData) {
        $json = new wf_JqDtHelper();
        if ($this->smsAdvancedEnabled) {
            $smsDirections = new SMSDirections();
        }

        if (!empty($QueryData)) {
            $allAddresses = zb_AddressGetFulladdresslistCached();
            $data = array();

            foreach ($QueryData as $EachRec) {
                foreach ($EachRec as $FieldName => $FieldVal) {
                    switch ($FieldName) {
                        case 'smssrvid':
                            if ($this->smsAdvancedEnabled) {
                                $SMSSrvName = $smsDirections->getDirectionNameById($FieldVal);

                                if (!empty($SMSSrvName)) {
                                    $data[] = (empty($FieldVal)) ? $SMSSrvName . ' (' . __('by default') . ')' : $SMSSrvName;
                                } else {
                                    $data[] = __('ID not found');
                                }
                            }
                            break;

                        case 'login':
                            if (empty($FieldVal)) {
                                $data[] = '';
                            } else {
                                $usrAddress = (empty($allAddresses[$FieldVal])) ? '' : $allAddresses[$FieldVal];
                                $data[] = wf_Link('?module=userprofile&username=' . $FieldVal, web_profile_icon() . ' ' . $FieldVal, false, '', 'style="color:#341e19"')
                                          . wf_delimiter(0) . $usrAddress;
                            }
                            break;

                        case 'delivered':
                        case 'no_statuschk':
                            $data[] = ($FieldVal == 1) ? __('Yes') : __('No');
                            break;

                        default:
                            $data[] = $FieldVal;
                    }
                }

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders JQDT and returns it
     *
     * @return string
     */
    public function renderJQDT($UserLogin = '') {
        $AjaxURLStr = ( empty($UserLogin) ) ? '' . self::URL_ME . '&ajax=true' . '' : '' . self::URL_ME . '&ajax=true&usrlogin=' . $UserLogin . '';
        $columns = array();
        if ($this->smsAdvancedEnabled) {
            $columnTargets = (empty($UserLogin)) ? '[0, 4, 5, 9]' : '[0, 2, 4, 5, 9]';
            $CheckCol1 = '8';
            $CheckCol2 = '9';
        } else {
            $columnTargets = (empty($UserLogin)) ? '[0, 3, 4, 8]' : '[0, 1, 3, 4, 8]';
            $CheckCol1 = '7';
            $CheckCol2 = '8';
        }
        $opts = '"order": [[ 0, "desc" ]], 
                 "columnDefs": [ {"targets": ' . $columnTargets . ', "visible": false},
                                 {"targets": [1], "width": "90px"},
                                 {"targets": [3], "width": "85px"},
                                 {"targets": [6, 7], "width": "100px"},
                                 {"targets": [' . $CheckCol1 . ', ' . $CheckCol2 . '], "className": "dt-center"}
                                ],
                 "rowCallback": function(row, data, index) {                   
                    if ( data[' . $CheckCol1 . '] == "' . __('No') . '" && data[' . $CheckCol2 . '] == "' . __('Yes') . '" ) {
                        $(\'td\', row).css(\'background-color\', \'red\');
                        $(\'td\', row).css(\'color\', \'#FFFF44\');
						$(\'td\', row).css(\'opacity\', \'0.8\');
                    }
                    
                    if ( data[' . $CheckCol1 . '] == "' . __('Yes') . '" && data[' . $CheckCol2 . '] == "' . __('Yes') . '") {
                        $(\'td\', row).css(\'background-color\', \'#228B22\');
                        $(\'td\', row).css(\'color\', \'white\');
						$(\'td\', row).css(\'opacity\', \'0.8\');
                    }
                    
                    if ( data[' . $CheckCol1 . '] == "' . __('No') . '" && data[' . $CheckCol2 . '] == "' . __('No') . '") {
                        $(\'td\', row).css(\'background-color\', \'#fffc5e\');
                        $(\'td\', row).css(\'color\', \'#4800FF\');
						$(\'td\', row).css(\'opacity\', \'0.8\');
                    }
                  }
                ';
        $columns[] = ('ID');
        if ($this->smsAdvancedEnabled) {
            $columns[] = __('SMS service');
        }
        $columns[] = ('Login');
        $columns[] = __('Phone');
        $columns[] = __('Service message ID');
        $columns[] = __('Service packet ID');
        $columns[] = __('Send date');
        $columns[] = __('Status check date');
        $columns[] = __('Delivered');
        $columns[] = __('No status check');
        $columns[] = __('Send status');
        $columns[] = __('Message text');

        return ( wf_JqDtLoader($columns, $AjaxURLStr, false, __('results'), 100, $opts) );
    }

    /**
     * Renders and returns controls for sms history web form
     *
     * @return string
     */
    public function renderControls() {
        $AjaxURLStr = '' . self::URL_ME . '&ajax=true' . '';
        $JQDTID = 'jqdt_' . md5($AjaxURLStr);
        $QickSelID = wf_InputId();
        $DateFromID = wf_InputId();
        $DateToID = wf_InputId();
        $ButtonID = wf_InputId();
        $StatusSelID = wf_InputId();

        $Today = curdate();
        $Yesterday = $this->getDateDiff(curdate(), 'P1D');
        $WeekAgo = $this->getDateDiff(curdate(), 'P1W');
        $MonthAgo = $this->getDateDiff(curdate(), 'P1M');

        /* $DateFromPreset   = ( wf_CheckGet(array('smshistdatefrom')) ) ? $_GET['smshistdatefrom'] : curdate();
          $DateToPreset       = ( wf_CheckGet(array('smshistdateto')) ) ? $_GET['smshistdateto'] : curdate(); */
        $DateFromPreset = $Today;
        $DateToPreset = $Today;
        $QuickFilterPreset = array($Today => __('Today'),
            $Yesterday => __('Yesterday'),
            $WeekAgo => __('Week ago'),
            $MonthAgo => __('Month ago')
        );

        $StatusFilterPreset = array('all' => __('All'),
            'delivered' => __('Delivered'),
            'undelivered' => __('Not delivered'),
            'unknown' => __('Undefined')
        );

        $inputs = wf_tag('h3', false);
        $inputs .= __('Show columns:');
        $inputs .= wf_tag('h3', true);
        $cells = wf_TableCell($inputs);

        $inputs = wf_tag('h3', false);
        $inputs .= __('Filter by:');
        $inputs .= wf_tag('h3', true);
        $cells .= wf_TableCell($inputs, '', '', 'colspan="2"');

        $rows = wf_TableRow($cells);


        $inputs = wf_CheckInput('showdbidclmn', __('Inner DB ID'), true, false, '__showdbidclmn');
        $cells = wf_TableCell($inputs);

        $inputs = wf_tag('font', false, '', '');
        $inputs .= __('Quick filter for:');
        $inputs .= wf_tag('font', true);
        $cells .= wf_TableCell($inputs);

        $inputs = wf_Selector('quickfilter', $QuickFilterPreset, '', $Today, true, false, $QickSelID);
        $cells .= wf_TableCell($inputs);

        $rows .= wf_TableRow($cells);


        $inputs = wf_CheckInput('showselfidclmn', __('Service message ID'), true, false, '__showselfidclmn');
        $cells = wf_TableCell($inputs);

        $inputs = wf_tag('font', false, '', '');
        $inputs .= __('Send date from:');
        $inputs .= wf_tag('font', true);
        $cells .= wf_TableCell($inputs);

        $inputs = wf_DatePickerPreset('smshistdatefrom', $DateFromPreset, false, $DateFromID);
        $cells .= wf_TableCell($inputs);

        $rows .= wf_TableRow($cells);


        $inputs = wf_CheckInput('showpackidclmn', __('Service packet ID'), true, false, '__showpackidclmn');
        $cells = wf_TableCell($inputs);

        $inputs = wf_tag('font', false, '', '');
        $inputs .= __('Send date to:');
        $inputs .= wf_tag('font', true);
        $cells .= wf_TableCell($inputs);

        $inputs = wf_DatePickerPreset('smshistdateto', $DateToPreset, false, $DateToID);
        $cells .= wf_TableCell($inputs);

        $rows .= wf_TableRow($cells);


        $inputs = wf_CheckInput('shownostatuschkclmn', __('No status check'), true, false, '__shownostatuschkclmn');
        $cells = wf_TableCell($inputs);

        $inputs = wf_tag('font', false, '', '');
        $inputs .= __('Message status');
        $inputs .= wf_tag('font', true);
        $cells .= wf_TableCell($inputs);

        $inputs = wf_Selector('statusfilter', $StatusFilterPreset, '', 'all', true, false, $StatusSelID);
        $cells .= wf_TableCell($inputs);

        $rows .= wf_TableRow($cells);


        $cells = wf_TableCell('');
        $inputs = wf_tag('a', false, 'ubButton', 'style="width:100%; cursor:pointer;" id="' . $ButtonID . '"');
        $inputs .= __('Show');
        $inputs .= wf_tag('a', true);
        $cells .= wf_TableCell($inputs, '', '', 'colspan="2" align="center"');

        $rows .= wf_TableRow($cells);
        $table = wf_TableBody($rows, '60%', '0', '', '');

        $inputs = wf_Plate($table, '98%', '170px', 'glamour');
        $inputs .= wf_CleanDiv() . wf_delimiter();
        $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs .= wf_JQDTColumnHideShow('__showdbidclmn', 'change', $JQDTID, 0);
        $inputs .= wf_JQDTColumnHideShow('__showselfidclmn', 'change', $JQDTID, ($this->smsAdvancedEnabled) ? 4 : 3);
        $inputs .= wf_JQDTColumnHideShow('__showpackidclmn', 'change', $JQDTID, ($this->smsAdvancedEnabled) ? 5 : 4);
        $inputs .= wf_JQDTColumnHideShow('__shownostatuschkclmn', 'change', $JQDTID, ($this->smsAdvancedEnabled) ? 9 : 8);
        $inputs .= '$(\'#' . $QickSelID . '\').on("change", function() {
                        $(\'#' . $DateFromID . '\').datepicker("setDate", $(\'#' . $QickSelID . '\').val());
                        
                        if ( $(\'#' . $QickSelID . ' option:selected\').text() == \'' . __('Yesterday') . '\' ) {
                            $(\'#' . $DateToID . '\').datepicker("setDate", $(\'#' . $QickSelID . '\').val());
                        } else {
                            $(\'#' . $DateToID . '\').datepicker("setDate", "' . $Today . '");
                        }
                    });
                    
                    $(\'#' . $ButtonID . '\').on("click", function(evt) {
                        evt.preventDefault();                        
                        var FromDate  = $(\'#' . $DateFromID . '\').val();
                        var ToDate    = $(\'#' . $DateToID . '\').val();
                        var SelStatus = $(\'#' . $StatusSelID . '\').val();
                        $(\'#' . $JQDTID . '\').DataTable().ajax.url(\'' . $AjaxURLStr . '&smshistdatefrom="\'+FromDate+\'"&smshistdateto="\'+ToDate+\'"&msgstatus=\'+SelStatus).load();                        
                    });
                   ';
        $inputs .= wf_tag('script', true);

        return $inputs;
    }

    /**
     * Returns difference between 2 dates
     *
     * Maybe should be placed in api.astral or api.compat or somewhere elsewhere?
     *
     * @param $DateFrom - date to count from in compatible with DateTime object format
     * @param $SubtractVal - subtract value in compatible with DateInterval object format
     *
     * @return string
     */
    protected function getDateDiff($DateFrom, $SubtractVal) {
        $DateObj = new DateTime($DateFrom);
        $DateObj->sub(new DateInterval($SubtractVal));

        return $DateObj->format('Y-m-d');
    }

}

?>