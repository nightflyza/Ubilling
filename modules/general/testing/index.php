<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

class AskoziaMonitor {

    protected $voicePath = '/mnt/askozia/';

    const ICON_PATH = 'skins/calls/';
    const URL_ME = '?module=testing';

    public function __construct() {
        
    }

    public function catchFileDownload() {
        if (wf_CheckGet(array('dlaskcall'))) {
            zb_DownloadFile($this->voicePath . $_GET['dlaskcall'], 'default');
        }
    }

    protected function getCallsDir() {
        $result = array();
        $result = rcms_scandir($this->voicePath);
        return ($result);
    }

    public function renderCallsList() {
        $result = '';
        $allVoiceFiles = $this->getCallsDir();
        if (!empty($allVoiceFiles)) {
            $cells = wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Number'));
            $cells.= wf_TableCell(__('File'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allVoiceFiles as $io => $each) {
                $fileName = $each;
                $explodedFile = explode('_', $fileName);
                $cleanDate = explode('.', $explodedFile[2]);
                $cleanDate = $cleanDate[0];
                $callingNumber = $explodedFile[1];
                $callDirection = ($explodedFile[0] == 'in') ? self::ICON_PATH . 'incoming.png' : self::ICON_PATH . 'outgoing.png';
                //unfinished calls
                if ((!ispos($cleanDate, 'in')) AND ( !ispos($cleanDate, 'out'))) {
                    $newDateString = date_format(date_create_from_format('Y-m-d-H-i-s', $cleanDate), 'Y-m-d H:i:s');
                    $cleanDate = $newDateString;
                    $cells = wf_TableCell(wf_img($callDirection) . ' ' . $cleanDate);
                    $cells.= wf_TableCell($callingNumber);
                    $cells.= wf_TableCell(wf_Link(self::URL_ME . '&dlaskcall=' . $fileName, __('Download')));
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        }
        return ($result);
    }

}

$askMon = new AskoziaMonitor();

$askMon->catchFileDownload();
show_window(__('Test'), $askMon->renderCallsList());
?>
