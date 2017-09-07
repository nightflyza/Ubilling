<?php

class MessagesQueue {

    /**
     * Message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * SMS system queue object placeholder
     *
     * @var object
     */
    protected $sms = '';

    /**
     * Email queue object placeholder
     *
     * @var object
     */
    protected $email = '';

    /**
     * Telegram system queue object placeholder
     *
     * @var object
     */
    protected $telegram = '';

    /**
     * Base module url
     */
    const URL_ME = '?module=tsmsqueue';

    public function __construct() {
        $this->initMessages();
        $this->initSystemQueues();
    }

    /**
     * Inits default messages helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Creates protected instances of UbillingSMS, UbillingMail and UbillingTelegram classes for further usage
     * 
     * @return void
     */
    protected function initSystemQueues() {
        $this->sms = new UbillingSMS();
        $this->email = new UbillingMail();
        $this->telegram = new UbillingTelegram();
    }

    /**
     * Renders one sms data into human readeble preview
     * 
     * @param array $data
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
     * Renders one email queue element in human readeble preview
     * 
     * @param array $data
     * 
     * @return string
     */
    protected function emailPreview($data) {
        $result = '';
        if (!empty($data)) {
            $dataCells = wf_TableCell(__('Email'), '', 'row2');
            $dataCells.= wf_TableCell($data['email']);
            $dataRows = wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Subject'), '', 'row2');
            $dataCells.= wf_TableCell($data['subj']);
            $dataRows.= wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Message'), '', 'row2');
            $dataCells.= wf_TableCell($data['message']);
            $dataRows.= wf_TableRow($dataCells, 'row3');
            $result = wf_TableBody($dataRows, '100%', '0', 'glamour');
        }
        return ($result);
    }

    /**
     * Renders one telegram queue element in human readeble preview
     * 
     * @param array $data
     * 
     * @return string
     */
    protected function telegramPreview($data) {
        $result = '';
        if (!empty($data)) {
            $dataCells = wf_TableCell(__('Chat ID'), '', 'row2');
            $dataCells.= wf_TableCell($data['chatid']);
            $dataRows = wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Message'), '', 'row2');
            $dataCells.= wf_TableCell($data['message']);
            $dataRows.= wf_TableRow($dataCells, 'row3');
            $result = wf_TableBody($dataRows, '100%', '0', 'glamour');
        }
        return ($result);
    }

    /**
     * Renders list of available SMS in queue with some controls
     * 
     * @return string
     */
    public function renderSmsQueue() {
        $result = '';
        $smsQueue = $this->sms->getQueueData();
        if (!empty($smsQueue)) {
            $cells = wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Mobile'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($smsQueue as $io => $each) {
                $cells = wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['number']);
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->smsPreview($each), '');
                $actLinks.= wf_JSAlert(self::URL_ME . '&deletesms=' . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders list of available emails in queue with some controls
     * 
     * @return string
     */
    public function renderEmailQueue() {
        $result = '';
        $queue = $this->email->getQueueData();
        if (!empty($queue)) {
            $cells = wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Email'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($queue as $io => $each) {
                $cells = wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['email']);
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->emailPreview($each), '');
                $actLinks.= wf_JSAlert(self::URL_ME . '&showqueue=email&deleteemail=' . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders list of available telegram messages in queue with some controls
     * 
     * @return string
     */
    public function renderTelegramQueue() {
        $result = '';
        $queue = $this->telegram->getQueueData();
        if (!empty($queue)) {
            $cells = wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Chat ID'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($queue as $io => $each) {
                $cells = wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['chatid']);
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->telegramPreview($each), '');
                $actLinks.= wf_JSAlert(self::URL_ME . '&showqueue=telegram&deletetelegram=' . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
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
        $result = $this->sms->deleteSms($filename);
        return ($result);
    }

    /**
     * Deletes email from local queue
     * 
     * @param string $filename Existing email filename
     * 
     * @return int
     */
    public function deleteEmail($filename) {
        $result = $this->email->deleteEmail($filename);
        return ($result);
    }

    /**
     * Deletes existing telegram message from queue
     * 
     * @param string $filename
     * 
     * @return int
     */
    public function deleteTelegram($filename) {
        $result = $this->telegram->deleteMessage($filename);
        return ($result);
    }

    /**
     * Renders module control panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result.= wf_Link(self::URL_ME, wf_img('skins/icon_sms_micro.gif') . ' ' . __('SMS in queue'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&showqueue=email', wf_img('skins/icon_mail.gif') . ' ' . __('Emails in queue'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&showqueue=telegram', wf_img_sized('skins/icon_telegram_small.png', '', '10', '10') . ' ' . __('Telegram messages queue'), false, 'ubButton');
        return ($result);
    }

    /**
     * Returns modal window with SMS creation form
     * 
     * @return string
     */
    public function smsCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newsmsnumber', __('Mobile'), '', true, '20');
        $inputs.= wf_TextArea('newsmsmessage', '', '', true, '30x5');
        $inputs.= wf_CheckInput('newsmstranslit', __('Forced transliteration'), true, true);
        $inputs.= wf_Submit(__('Create'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        $result = wf_modalAuto(wf_img('skins/add_icon.png', __('Create new SMS')), __('Create new SMS'), $form, '');
        return ($result);
    }

    /**
     * Creates new SMS for queue
     * 
     * @param string $number
     * @param string $message
     * 
     * @return string/void
     */
    public function createSMS($number, $message) {
        $result = '';
        $translit = (wf_CheckPost(array('newsmstranslit'))) ? true : false;
        if (ispos($number, '+')) {
            $this->sms->sendSMS($number, $message, $translit, 'TQUEUE');
        } else {
            $result = __('Number must be in international format');
        }
        return ($result);
    }

    /**
     * Returns modal window with email creation form
     * 
     * @return string
     */
    public function emailCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newemailaddress', __('Email'), '', true, '20');
        $inputs.= wf_TextInput('newemailsubj', __('Subject'), '', true, '40');
        $inputs.= wf_TextArea('newemailmessage', '', '', true, '50x10');
        $inputs.= wf_Submit(__('Create'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        $result = wf_modalAuto(wf_img('skins/add_icon.png', __('Create new email')), __('Create new email'), $form, '');
        return ($result);
    }

    /**
     * Creates new email message in queue
     * 
     * 
     * @param string $email
     * @param string $subj
     * @param string $messages
     * 
     * @return string/void
     */
    public function createEmail($email, $subj, $message) {
        $result = '';
        if ((!empty($email)) AND ( !empty($message)) AND ( !empty($subj))) {
            $this->email->sendEmail($email, $subj, $message, 'TQUEUE');
        } else {
            $result = __('Not all of required fields are filled');
        }
        return ($result);
    }

    /**
     * Returns modal window with telegram message creation form
     * 
     * @return string
     */
    public function telegramCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newtelegramchatid', __('Chat ID'), '', true, '20');
        $inputs.= wf_TextArea('newtelegrammessage', '', '', true, '50x10');
        $inputs.= wf_Submit(__('Create'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        $result = wf_modalAuto(wf_img('skins/add_icon.png', __('Create new Telegram message')), __('Create new Telegram message'), $form, '');
        return ($result);
    }

    /**
     * Creates new telegram message in queue
     * 
     * @param string $chatid
     * @param string $message
     * 
     * @return string
     */
    public function createTelegram($chatid, $message) {
        $result = '';
        if ((!empty($chatid)) AND ( !empty($message))) {
            $this->telegram->sendMessage($chatid, $message, false, 'TQUEUE');
        } else {
            $result = __('Not all of required fields are filled');
        }
        return ($result);
    }

}

?>