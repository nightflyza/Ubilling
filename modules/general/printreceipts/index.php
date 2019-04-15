<?php
define('TEMPLATE_PATH', DATA_PATH . 'documents/receipt_template/');

if (cfr('PRINTRECEIPTS')) {
    if ($ubillingConfig->getAlterParam('PRINT_RECEIPTS_MODULE_ENABLED')) {
        if (wf_CheckPost(array('printthemall'))) {
            $cashWHEREClause = '';
            $addrWHEREClause = '';
            $whereClause = '';
            $receiptServiceName = $_POST['receiptsrvtxt'];
            $receiptDirection = $_POST['receiptsubscrstatus'];
            $receiptPayTillDate = $_POST['receiptpaytill'];
            $receiptStreet = (wf_CheckPost(array('receiptstreets')) and $_POST['receiptstreets'] != '-') ? $_POST['receiptstreets'] : '';
            $receiptBuild = (wf_CheckPost(array('receiptbuilds')) and $_POST['receiptbuilds'] != '-') ? $_POST['receiptbuilds'] : '';
            $receiptMonthsCnt = (wf_CheckPost(array('receiptmonthscnt'))) ? (vf($_POST['receiptmonthscnt'], 3)) : 1;

            switch ($receiptDirection) {
                case 'debt':
                    $debtCash = (wf_CheckPost(array('receiptdebtcash'))) ? ('-' . vf($_POST['receiptdebtcash'], 3)) : 0;
                    $whereClause = ' WHERE `cash` < ' . $debtCash . ' ';
                    break;

                case 'undebt':
                    $debtCash = (wf_CheckPost(array('receiptdebtcash'))) ? (vf($_POST['receiptdebtcash'], 3)) : 0;
                    $whereClause = ' WHERE `cash` > ' . $debtCash . ' ';
                    break;
            }

            if (!empty($receiptStreet)) {
                if (empty($whereClause)) {
                    $whereClause = ' WHERE ';
                } else {
                    $whereClause .= ' AND ';
                }

                $whereClause .= " `street` = '" . $receiptStreet . "' ";

                if (!empty($receiptBuild)) {
                    $whereClause .= " AND `build` = '" . str_ireplace($receiptStreet, '', $receiptBuild) . "' ";
                }
            }

            if ($_POST['receiptsrv'] == 'inetsrv') {
                $query = "SELECT * FROM
                          (SELECT `users`.`login`, `users`.`cash`, `realname`.`realname`, `tariffs`.`name` AS `tariffname`, `tariffs`.`fee` AS `tariffprice`, 
                                  `contracts`.`contract`, `phones`.`phone`, `phones`.`mobile`,  
                                  `tmp_addr`.`cityname` AS `city`, `tmp_addr`.`streetname` AS `street`, `tmp_addr`.`buildnum` AS `build`, `tmp_addr`.`apt`
                              FROM `users` 
                                  LEFT JOIN `tariffs` ON `users`.`tariff` = `tariffs`.`name`
                                  LEFT JOIN `contracts` USING(`login`)
                                  LEFT JOIN `realname` USING(`login`) 
                                  LEFT JOIN `phones` USING(`login`) 
                                  LEFT JOIN (SELECT `address`.`login`,`city`.`id`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` 
                                                FROM `address` 
                                                    INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
                                                    INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
                                                    INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
                                                    INNER JOIN `city` ON `street`.`cityid`=`city`.`id`
                                            ) AS `tmp_addr` USING(`login`) ) AS tmpQ " .
                    $whereClause . " ORDER BY `street` ASC, `build` ASC";

            } else {
                $query = "SELECT `ukv_users`.*, `ukv_tariffs`.`tariffname`, `ukv_tariffs`.`price` AS `tariffprice` 
                          FROM `ukv_users` 
                              LEFT JOIN `ukv_tariffs` ON `ukv_users`.`tariffid` = `ukv_tariffs`.`id` " . $whereClause . " ORDER BY `street` ASC, `build` ASC";
            }

            $usersToPrint = simple_queryall($query);

            if (!empty($usersToPrint)) {
                $rawTemplate = file_get_contents(TEMPLATE_PATH . "payment_receipt.tpl");
                $rawTemplateHeader = file_get_contents(TEMPLATE_PATH . "payment_receipt_head.tpl");
                $rawTemplateFooter = file_get_contents(TEMPLATE_PATH . "payment_receipt_footer.tpl");
                $printableTemplate = '';
                $qrCodeExtInfo = '';
                $formatDates = 'd.m.Y';
                $formatMonthYear = 'm.Y';
                $i = 0;

                preg_match('/{QR_EXT_START}(.*?){QR_EXT_END}/ms', $rawTemplateHeader, $matchResult);
                if (isset($matchResult[1])) {
                    $qrCodeExtInfo = trim(str_ireplace('"', "'", $matchResult[1]));
                }

                preg_match('/{DATES_FORMAT_START}(.*?){DATES_FORMAT_END}/ms', $rawTemplateHeader, $matchResult);
                if (isset($matchResult[1])) {
                    $tmpStr = trim($matchResult[1]);
                    $formatDates = (!empty($tmpStr)) ? $tmpStr : $formatDates;
                }

                preg_match('/{MONTHYEAR_FORMAT_START}(.*?){MONTHYEAR_FORMAT_END}/ms', $rawTemplateHeader, $matchResult);
                if (isset($matchResult[1])) {
                    $tmpStr = trim($matchResult[1]);
                    $formatMonthYear = (!empty($tmpStr)) ? $tmpStr : $formatMonthYear;
                }

                $tmpDate = new DateTime($receiptPayTillDate);
                $receiptPayTillDate = $tmpDate->format($formatDates);

                foreach ($usersToPrint as $item => $eachUser) {
                    // replacing macro values for qr-code info in template
                    $tmpQRCode = str_ireplace('{CURDATE}', date($formatDates), $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{PAYTILLMONTHYEAR}', date($formatMonthYear, strtotime("+1 month")), $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{PAYTILLDATE}', $receiptPayTillDate, $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{SERVICENAME}', $receiptServiceName, $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{CONTRACT}', $eachUser['contract'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{REALNAME}', $eachUser['realname'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{STREET}', $eachUser['street'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{BUILD}', $eachUser['build'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{APT}', (!empty($eachUser['apt'])) ? '/' . $eachUser['apt'] : '', $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{PHONE}', $eachUser['phone'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{MOBILE}', $eachUser['mobile'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{TARIFF}', $eachUser['tariffname'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{TARIFFPRICE}', $eachUser['tariffprice'], $qrCodeExtInfo);
                    $tmpQRCode = str_ireplace('{SUMM}', $eachUser['tariffprice'] * $receiptMonthsCnt, $qrCodeExtInfo);

                    // replacing macro values in template
                    $rowtemplate = $rawTemplate;
                    $rowtemplate = str_ireplace('{QR_INDEX}', ++$i, $rowtemplate);
                    $rowtemplate = str_ireplace('{QR_CODE_CONTENT}', $tmpQRCode, $rowtemplate);
                    $rowtemplate = str_ireplace('{CURDATE}', date($formatDates), $rowtemplate);
                    $rowtemplate = str_ireplace('{PAYTILLMONTHYEAR}', date($formatMonthYear, strtotime("+1 month")), $rowtemplate);
                    $rowtemplate = str_ireplace('{PAYTILLDATE}', $receiptPayTillDate, $rowtemplate);
                    $rowtemplate = str_ireplace('{SERVICENAME}', $receiptServiceName, $rowtemplate);
                    $rowtemplate = str_ireplace('{CONTRACT}', $eachUser['contract'], $rowtemplate);
                    $rowtemplate = str_ireplace('{REALNAME}', $eachUser['realname'], $rowtemplate);
                    $rowtemplate = str_ireplace('{STREET}', $eachUser['street'], $rowtemplate);
                    $rowtemplate = str_ireplace('{BUILD}', $eachUser['build'], $rowtemplate);
                    $rowtemplate = str_ireplace('{APT}', (!empty($eachUser['apt'])) ? '/' . $eachUser['apt'] : '', $rowtemplate);
                    $rowtemplate = str_ireplace('{PHONE}', $eachUser['phone'], $rowtemplate);
                    $rowtemplate = str_ireplace('{MOBILE}', $eachUser['mobile'], $rowtemplate);
                    $rowtemplate = str_ireplace('{TARIFF}', $eachUser['tariffname'], $rowtemplate);
                    $rowtemplate = str_ireplace('{TARIFFPRICE}', $eachUser['tariffprice'], $rowtemplate);
                    $rowtemplate = str_ireplace('{SUMM}', $eachUser['tariffprice'] * $receiptMonthsCnt, $rowtemplate);

                    $printableTemplate .= $rowtemplate;
                }

                //$tmpReceiptFileName = 'receipt_' . date('Ymd') . time() . '.html';
                $rawTemplateHeader = str_ireplace('{QR_CODES_CNT}', $i, $rawTemplateHeader);

                die($rawTemplateHeader . $printableTemplate . $rawTemplateFooter);
            } else{
                show_warning(__('Query returned empty result'));
            }
        } else {
            $receiptDirections = array('debt' => __('Debtors'),
                'undebt' => __('AntiDebtors'),
                'all' => __('All')
            );
            $receiptStreets = array('' => '-');
            //$receiptBuilds = array('' => '-');
            $receiptBuilds = array();

            $query = "SELECT DISTINCT `streetname` from `street` ORDER BY `streetname` ASC;";
            $allStreets = simple_queryall($query);
            if (!empty($allStreets)) {
                foreach ($allStreets as $io => $each) {
                    $receiptStreets[trim($each['streetname'])] = trim($each['streetname']);
                }
            }

            $query = "SELECT `street`.`streetname`, `build`.`buildnum` FROM `street` RIGHT JOIN `build` ON `build`.`streetid` = `street`.`id` ORDER BY `buildnum`;";
            $allBuilds = simple_queryall($query);
            if (!empty($allBuilds)) {
                foreach ($allBuilds as $io => $each) {
                    $receiptBuilds[trim($each['streetname']) . trim($each['buildnum'])] = trim($each['buildnum']);
                }
            }

            $inputs = wf_tag('div', false, '', 'style="line-height: 0.8em"');
            $inputs .= wf_RadioInput('receiptsrv', __('Internet'), 'inetsrv', false, true, 'ReceiptSrvInet');
            $inputs .= wf_RadioInput('receiptsrv', __('UKV'), 'ktvsrv', true, false, 'ReceiptSrvCTV');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_TextInput('receiptsrvtxt', __('Service'), __('Internet'), true, '', '', '', 'ReceiptSrvName');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Selector('receiptsubscrstatus', $receiptDirections, __('Subscriber\'s account status'), '', true, false, 'ReceiptDirSel');
            $inputs .= wf_TextInput('receiptdebtcash', __('The threshold at which the money considered user debtor'), '0', true, 4, '', '', 'ReceiptDebtSumm');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Selector('receiptstreets', $receiptStreets, __('Street'), '', true, true, 'ReceiptStreets');
            $inputs .= wf_Selector('receiptbuilds', array('' => '-'), __('Build'), '', true, true, 'ReceiptBuilds');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_TextInput('receiptmonthscnt', __('Amount of months to be payed(will be multiplied on tariff cost)'), '1', true, 4, '', '', 'ReceiptMonthsCnt');
            $inputs .= wf_delimiter(0);
            $inputs.= wf_tag('span', false);
            $inputs.= wf_DatePickerPreset('receiptpaytill', date("Y-m-d", strtotime("+5 days")), true);
            $inputs.= wf_nbsp(2) . __('Pay till date');
            $inputs.= wf_tag('span', true);
            $inputs.= wf_delimiter(1);
            $inputs .= wf_HiddenInput('printthemall', base64_encode(json_encode($receiptBuilds)), 'TmpBuildsAll');
            $inputs .= wf_Submit(__('Print'));
            $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
            $inputs .= '$(document).ready(function() {
                        $("[name=receiptsrv]").change(function(evt) {
                            var tmpStr;
                            
                            if ($(this).val() == \'inetsrv\') {
                                tmpStr = \'' . __('Internet') . '\';
                            } else {
                                tmpStr = \'' . __('Cable television') . '\';
                            }
                            
                           $(\'#ReceiptSrvName\').val(tmpStr);
                        });
                        
                        $(\'#ReceiptDirSel\').change(function(evt) {
                            if ($(this).val() !== \'all\') {
                                $(\'#ReceiptDebtSumm\').val(\'0\');
                                $(\'#ReceiptDebtSumm\').show();
                                $("label[for=\'ReceiptDebtSumm\']").text(\'' . __('The threshold at which the money considered user debtor') . '\');
                            } else {
                                $(\'#ReceiptDebtSumm\').val(\'\');
                                $(\'#ReceiptDebtSumm\').hide();
                                $("label[for=\'ReceiptDebtSumm\']").text(\'\');
                            }   
                        });
                        
                        $(\'#ReceiptStreets\').change(function(evt) {
                            var keyword = $(this).val();                            
                            var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                            filterBuildsSelect(keyword, source);
                        });
                        
                        function filterBuildsSelect(search_keyword, search_array) {
                            var newselect = $("<select id=\"ReceiptBuilds\" name=\"receiptbuilds\" />");
                            
                            $("<option />", {value: \'\', text: \'-\'}).appendTo(newselect);
                            
                            if (search_keyword.length > 0 && search_keyword.trim() !== "-") {
                                for (var key in search_array) {
                                    if ( key.toLowerCase() == search_keyword.toLowerCase() + search_array[key] && key.trim() !== "" ) {                                       
                                        $("<option />", {value: key, text: search_array[key]}).appendTo(newselect);
                                    }  
                                }
                            }
                            
                            $(\'#ReceiptBuilds\').replaceWith(newselect);
                        }
                        
                        var keyword = $(\'#ReceiptStreets\').val();
                        var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                        filterBuildsSelect(keyword, source);
                   });
                  ';
            $inputs .= wf_tag('script', true);
            $inputs .= wf_tag('div', true);

            $form = wf_Form('?module=printreceipts', 'POST', $inputs, 'glamour', '', 'ReceiptPrintForm');
            show_window(__('Print receipts'), $form);
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>