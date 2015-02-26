<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['WATCHDOG_ENABLED']) {
    if (cfr('WATCHDOG')) {

        class TSMSQueue {

            protected $queue = array();

            const SMS_PATH = 'content/tsms/';

            public function __construct() {
                $this->loadQueue();
            }

            /**
             * Loads data from current SMS queue directory to private prop
             * 
             * @return void
             */
            protected function loadQueue() {
                $all = rcms_scandir(self::SMS_PATH);
                if (!empty($all)) {
                    foreach ($all as $io => $eachsmsfile) {
                        $smsDate = date("Y-m-d H:i:s", filectime(self::SMS_PATH . $eachsmsfile));
                        $smsData = rcms_parse_ini_file(self::SMS_PATH . $eachsmsfile);
                        $this->queue[$io]['filename'] = $eachsmsfile;
                        $this->queue[$io]['date'] = $smsDate;

                        $this->queue[$io]['number'] = $smsData['NUMBER'];
                        $this->queue[$io]['message'] = $smsData['MESSAGE'];
                    }
                }
            }

            /**
             * Renders one sms data human readeble preview
             * 
             * @return string
             */
            protected function smsPreview($data) {
                $result = '';
                if (!empty($data)) {
                    $smsDataCells = wf_TableCell(__('Mobile'), '', 'row2');
                    $smsDataCells.= wf_TableCell($data['number']);
                    $smsDataRows = wf_TableRow($smsDataCells, 'row3');
                    $smsDataCells = wf_TableCell(__('Message'), '', 'row2');
                    $smsDataCells.= wf_TableCell($data['message']);
                    $smsDataRows.= wf_TableRow($smsDataCells, 'row3');
                    $result = wf_TableBody($smsDataRows, '100%', '0', 'glamour');
                }
                return ($result);
            }

            /**
             * Renders list of available SMS in queue with some controls
             * 
             * @return string
             */
            public function render() {
                $result = '';
                if (!empty($this->queue)) {
                    $cells = wf_TableCell(__('Date'));
                    $cells.= wf_TableCell(__('Mobile'));
                    $cells.= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($this->queue as $io => $each) {
                        $cells = wf_TableCell($each['date']);
                        $cells.= wf_TableCell($each['number']);
                        $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->smsPreview($each), '');
                        $actLinks.= wf_JSAlert('?module=tsmsqueue&deletesms=' . $each['filename'], web_delete_icon(), __('Are you serious'));
                        $cells.= wf_TableCell($actLinks);
                        $rows.= wf_TableRow($cells, 'row3');
                    }

                    $result = wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result.=wf_tag('span', false, 'alert_info');
                    $result.= wf_tag('center', false);
                    $result.=__('Nothing found');
                    $result.=wf_tag('center', true);
                    $result.=wf_tag('span', true);
                }
                return ($result);
            }

            /**
             * Deletes SMS from local queue
             * 
             * @param string $filename Existing sms filename
             * 
             * @return int 0 - ok
             */
            public function deleteSms($filename) {
                if (file_exists(self::SMS_PATH . $filename)) {
                    rcms_delete_files(self::SMS_PATH . $filename);
                    $result = 0;
                    if (file_exists(self::SMS_PATH . $filename)) {
                        $result = 1;
                    }
                } else {
                    $result = 2;
                }
                return ($result);
            }

        }

        $tsmsQueue = new TSMSQueue();
        if (wf_CheckGet(array('deletesms'))) {
            $deletionResult = $tsmsQueue->deleteSms($_GET['deletesms']);
            if ($deletionResult == 0) {
                $darkVoid=new DarkVoid();
                $darkVoid->flushCache();
                rcms_redirect('?module=tsmsqueue');
            } else {
                if ($deletionResult == 2) {
                    show_error(__('Not existing item'));
                }

                if ($deletionResult == 1) {
                    show_error(__('Permission denied'));
                }
            }
        }

        show_window(__('SMS in queue'), $tsmsQueue->render());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>