<?php

class TurboSMS extends SMSSrvAPI {

    public function __construct($SMSSrvID, $SMSPack = array()) {
        parent::__construct($SMSSrvID, $SMSPack);
    }

    public function getBalance() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function getSMSQueue() {
        $result = '';
        $smsArray = array();
        $total = 0;
        $tsms_db = 'users';

        $TsmsDB = new DbConnect($this->SrvGatewayAddr, $this->SrvLogin, $this->SrvPassword, $tsms_db, $error_reporting = true, $persistent = false);
        $TsmsDB->open() or die($TsmsDB->error());
        $TsmsDB->query('SET NAMES utf8;');

        if (wf_CheckPost(array('showdate'))) {
            $date = mysql_real_escape_string($_POST['showdate']);
        } else {
            $date = '';
        }

        if (!empty($date)) {
            $where = " WHERE `send_time` LIKE '" . $date . "%' ORDER BY `id` DESC;";
        } else {
            $where = '  ORDER BY `id` DESC LIMIT 50;';
        }

        $query = "SELECT * from `" . $this->SrvLogin . "`" . $where;
        $TsmsDB->query($query);

        while ($row = $TsmsDB->fetchassoc()) {
            $smsArray[] = $row;
        }

        //close old datalink
        $TsmsDB->close();

        //rendering result
        $inputs = wf_DatePickerPreset('showdate', curdate());
        $inputs.= wf_Submit(__('Show'));
        $dateform = wf_Form("", 'POST', $inputs, 'glamour');


        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Msg ID'));
        $cells.= wf_TableCell(__('Mobile'));
        $cells.= wf_TableCell(__('Sign'));
        $cells.= wf_TableCell(__('Message'));
        $cells.= wf_TableCell(__('Balance'));
        $cells.= wf_TableCell(__('Cost'));
        $cells.= wf_TableCell(__('Send time'));
        $cells.= wf_TableCell(__('Sended'));
        $cells.= wf_TableCell(__('Status'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($smsArray)) {
            foreach ($smsArray as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['msg_id']);
                $cells.= wf_TableCell($each['number']);
                $cells.= wf_TableCell($each['sign']);
                $msg = wf_modal(__('Show'), __('SMS'), $each['message'], '', '300', '200');
                $cells.= wf_TableCell($msg);
                $cells.= wf_TableCell($each['balance']);
                $cells.= wf_TableCell($each['cost']);
                $cells.= wf_TableCell($each['send_time']);
                $cells.= wf_TableCell($each['sended']);
                $cells.= wf_TableCell($each['status']);
                $rows.=wf_TableRow($cells, 'row5');
                $total++;
            }
        }

        //$result.= wf_BackLink(self::URL_ME, '', true);
        $result.= $dateform;
        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= __('Total') . ': ' . $total;
        //return ($result);
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['ModalWID'], '', true, 'false', '888'));
    }

    public function pushMessages() {
        $sign = $this->SendDog->safeEscapeString($this->SrvAlphaName);
        $date = date("Y-m-d H:i:s");

        //$allSmsQueue = $this->smsQueue->getQueueData();
        $allSmsQueue = $this->SMSMsgPack;

        if (!empty($allSmsQueue)) {
            //open new database connection
            $TsmsDB = new DbConnect($this->SrvGatewayAddr, $this->SrvLogin, $this->SrvPassword, 'users', $error_reporting = true, $persistent = false);
            $TsmsDB->open() or die($TsmsDB->error());
            $TsmsDB->query('SET NAMES utf8;');

            foreach ($allSmsQueue as $eachsms) {
                if ((isset($eachsms['number'])) AND ( isset($eachsms['message']))) {
                    $query = "INSERT INTO `" . $this->SrvLogin . "` ( `number`, `sign`, `message`, `wappush`,  `send_time`) 
                                    VALUES ('" . $eachsms['number'] . "', '" . $sign . "', '" . $eachsms['message'] . "', '', '" . $date . "');
                             ";
                    //push new sms to database
                    $TsmsDB->query($query);
                }
                //remove old sent message
                $this->SendDog->getSMSQueueInstance()->deleteSms($eachsms['filename']);
            }
            //close old datalink
            $TsmsDB->close();
        }
    }

    public  function checkMessagesStatuses() {
        log_register('Checking statuses for [' . get_class($this) . '] SMS service is not implemented');
    }
}
?>