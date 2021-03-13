<?php

if (cfr('TICKETING')) {

    class ReportUsersTicketing {

        /**
         * Contains all previous tickets data by some user
         *
         * @var array
         */
        protected $alltickets = array();

        public function __construct($login) {
            $this->alltickets = $this->loadTickets($login);
        }

        /*
         * load all previous user tickets from database
         * 
         * @param $login existing user login
         * 
         * @return array
         */

        protected function loadTickets($login) {
            $login = mysql_real_escape_string($login);
            $query = "SELECT `id`,`date`,`status`,`text` from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL AND `from`='" . $login . "' ORDER BY `date` DESC";
            $result = simple_queryall($query);
            return ($result);
        }

        /*
         * Renders report as normal grid
         * 
         * @return string
         */

        public function render() {
            $result = '';
            if (!empty($this->alltickets)) {
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('Date'));
                $cells .= wf_TableCell(__('Text'));
                $cells .= wf_TableCell(__('Processed'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->alltickets as $io => $each) {
                    $cells = wf_TableCell($each['id']);
                    $cells .= wf_TableCell($each['date']);
                    if (strlen($each['text']) > 140) {
                        $textPreview = mb_substr(strip_tags($each['text']), 0, 140, 'utf-8') . '...';
                    } else {
                        $textPreview = strip_tags($each['text']);
                    }
                    $cells .= wf_TableCell($textPreview);
                    $cells .= wf_TableCell(web_bool_led($each['status']));
                    $cells .= wf_TableCell(wf_Link('?module=ticketing&showticket=' . $each['id'], wf_img_sized('skins/icon_search_small.gif', '', '12') . ' ' . __('Show'), false, 'ubButton'));
                    $rows .= wf_TableRow($cells, 'row3');
                }
                $result = wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $messages = new UbillingMessageHelper();
                $result = $messages->getStyledMessage(__('Nothing found'), 'info');
            }

            return ($result);
        }

        /*
         * Renders report as fullcalendar widget
         * 
         * @return string
         */

        public function renderCalendar() {
            $calendarData = '';
            if (!empty($this->alltickets)) {
                foreach ($this->alltickets as $io => $each) {
                    $timestamp = strtotime($each['date']);
                    $date = date("Y, n-1, j", $timestamp);
                    $rawTime = date("H:i:s", $timestamp);
                    $calendarData .= "
                      {
                        title: '" . $rawTime . "',
                        url: '?module=ticketing&showticket=" . $each['id'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                   },
                    ";
                }
            }
            $result = wf_FullCalendar($calendarData);
            return ($result);
        }

    }

    if (wf_CheckGet(array('username'))) {

        $login = $_GET['username'];
        $reportTicketing = new ReportUsersTicketing($login);

        //controls
        $actionLinks = wf_Link('?module=pl_ticketing&username=' . $login, wf_img('skins/icon_table.png') . ' ' . __('Grid view'), false, 'ubButton');
        $actionLinks .= wf_Link('?module=pl_ticketing&username=' . $login . '&calendarview=true', wf_img('skins/icon_calendar.gif') . ' ' . __('As calendar'), false, 'ubButton');
        if (cfr('PLSENDMESSAGE')) {
            $actionLinks .= wf_Link('?module=pl_sendmessage&username=' . $login, wf_img('skins/icon_chat_small.png') . ' ' . __('Send message'), false, 'ubButton');
        }
        show_window('', $actionLinks);

        //display results
        if (!wf_CheckGet(array('calendarview'))) {
            show_window(__('Previous user tickets'), $reportTicketing->render());
        } else {
            show_window(__('Previous user tickets'), $reportTicketing->renderCalendar());
        }

        show_window('', web_UserControls($login));
    } else {
        show_error(__('User not exist'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>