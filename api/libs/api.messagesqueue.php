<?php

/**
 * System-wide outcoming messages queue for SMS/Telegram/Emails etc..
 */
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
     * PHPMail queue object placeholder
     *
     * @var object
     */
    protected $phpMail = '';

    /**
     * Telegram system queue object placeholder
     *
     * @var object
     */
    protected $telegram = '';

    /**
     * System json helper object placeholder
     *
     * @var object
     */
    protected $json = '';

    /**
     * Base module url
     */
    const URL_ME = '?module=tsmsqueue';

    public function __construct() {
        $this->initMessages();
        $this->initJson();
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
        $this->phpMail = new UbillingPHPMail();
        $this->telegram = new UbillingTelegram();
    }

    /**
     * Inits json datatables helper object
     * 
     * @return void
     */
    protected function initJson() {
        $this->json = new wf_JqDtHelper();
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
            $messageText = nl2br($data['message']);
            $dataCells = wf_TableCell(__('Chat ID'), '', 'row2');
            $dataCells.= wf_TableCell($data['chatid']);
            $dataRows = wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Message'), '', 'row2','valign="top"');
            $dataCells.= wf_TableCell($messageText);
            $dataRows.= wf_TableRow($dataCells, 'row3');
            $result = wf_TableBody($dataRows, '100%', '0', 'glamour');
        }
        return ($result);
    }

    /**
     * Renders list of available SMS in queue container
     * 
     * @return string
     */
    public function renderSmsQueue() {
        $result = '';
        $smsQueueCount = $this->sms->getQueueCount();
        if ($smsQueueCount > 0) {
            if ($this->sms->smsRoutingFlag) {
                $columns = array('Date', 'Mobile', __('SMS service'), 'Actions');
            } else {
                $columns = array('Date', 'Mobile', 'Actions');
            }
            $result.=wf_JqDtLoader($columns, self::URL_ME . '&ajaxsms=true', false, __('SMS'), 100, '"order": [[ 0, "desc" ]]');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders JSON list of available SMS in queue with some controls
     * 
     * @return void
     */
    public function renderSMSAjaxQueue() {
        $smsQueue = $this->sms->getQueueData();
        if (!empty($smsQueue)) {
            /**
             * dakara ima ichibyou goto ni sekaisen wo koete
             * kimi no sono egao  mamoritai no sa
             * soshite mata kanashimi no nai jikan no RUUPU e to
             * nomikomarete yuku  kodoku no kansokusha
             */
            foreach ($smsQueue as $io => $each) {
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->smsPreview($each), '');
                $actLinks.= wf_JSAlert(self::URL_ME . '&deletesms=' . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $each['date'];
                $data[] = $each['number'];

                if ($this->sms->smsRoutingFlag) {
                    $data[] = $this->sms->smsDirections->getDirectionNameById($each['smssrvid']);
                }

                $data[] = $actLinks;
                $this->json->addRow($data);
                unset($data);
            }
        }
        $this->json->getJson();
    }

    /**
     * Renders list of available emails in queue container
     * 
     * @return string
     */
    public function renderEmailQueue() {
        $result = '';
        $queueCount = $this->email->getQueueCount();
        if ($queueCount > 0) {
            $columns = array('Date', 'Email', 'Actions');
            $result.=wf_JqDtLoader($columns, self::URL_ME . '&showqueue=email&ajaxmail=true', false, __('Email'), 100, '"order": [[ 0, "desc" ]]');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders JSON list of available emails in queue with some control
     * 
     * @return void
     */
    public function renderEmailAjaxQueue() {
        $queue = $this->email->getQueueData();
        if (!empty($queue)) {
            foreach ($queue as $io => $each) {
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->emailPreview($each), '');
                $actLinks.= wf_JSAlert(self::URL_ME . '&showqueue=email&deleteemail=' . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $each['date'];
                $data[] = $each['email'];
                $data[] = $actLinks;
                $this->json->addRow($data);
                unset($data);
            }
        }
        $this->json->getJson();
    }

    /**
     * Renders list of available telegram messages in queue container
     * 
     * @return string
     */
    public function renderTelegramQueue() {
        $result = '';
        $queueCount = $this->telegram->getQueueCount();
        if ($queueCount > 0) {
            $columns = array('Date', 'Chat ID', 'Actions');
            $result.=wf_JqDtLoader($columns, self::URL_ME . '&showqueue=telegram&ajaxtelegram=true', false, __('Message'), 100, '"order": [[ 0, "desc" ]]');
        } else {
            $result.=$this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders JSON list of available telegram messages in queue with some controls
     * 
     * @return void
     */
    public function renderTelegramAjaxQueue() {
        $queue = $this->telegram->getQueueData();
        if (!empty($queue)) {
            foreach ($queue as $io => $each) {
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->telegramPreview($each), '');
                $actLinks.= wf_JSAlert(self::URL_ME . '&showqueue=telegram&deletetelegram=' . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $each['date'];
                $data[] = $each['chatid'];
                $data[] = $actLinks;
                $this->json->addRow($data);
                unset($data);
            }
        }
        $this->json->getJson();
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
    public function renderPanel($phpMailerOn = false) {
        $result = '';
        $result.= wf_Link(self::URL_ME, wf_img('skins/icon_sms_micro.gif') . ' ' . __('SMS in queue'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&showqueue=email', wf_img('skins/icon_mail.gif') . ' ' . __('Emails in queue'), false, 'ubButton');
        $result.= ($phpMailerOn) ? wf_Link(self::URL_ME . '&showqueue=phpmail', wf_img('skins/icon_mail.gif') . ' PHPMailer: ' . __('Emails in queue'), false, 'ubButton') : '';
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

    /**
     * Renders one PHPMailer queue element in human readable preview
     *
     * @param array $data
     *
     * @return string
     */
    protected function phpMailPreview($data) {
        $result = '';
        if (!empty($data)) {
            $dataCells = wf_TableCell(__('Email'), '', 'row2');
            $dataCells .= wf_TableCell($data['email']);
            $dataRows = wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Subject'), '', 'row2');
            $dataCells .= wf_TableCell($data['subj']);
            $dataRows .= wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Message'), '', 'row2');
            $dataCells .= wf_TableCell($data['message']);
            $dataRows .= wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Attach path'), '', 'row2');
            $dataCells .= wf_TableCell($data['attachpath']);
            $dataRows .= wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Body as HTML'), '', 'row2');
            $dataCells .= wf_TableCell(($data['bodyashtml']) ? web_green_led() : web_red_led());
            $dataRows .= wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('From'), '', 'row2');
            $dataCells .= wf_TableCell($data['from']);
            $dataRows .= wf_TableRow($dataCells, 'row3');
            $dataCells = wf_TableCell(__('Custom headers'), '', 'row2');
            $dataCells .= wf_TableCell((empty($data['customheaders'])) ? web_red_led() : web_green_led());
            $dataRows .= wf_TableRow($dataCells, 'row3');

            $result = wf_TableBody($dataRows, '100%', '0', 'glamour');
        }
        return ($result);
    }

    /**
     * Renders list of available PHPMailer emails in queue container
     *
     * @return string
     */
    public function renderPHPMailQueue() {
        $result = '';
        $queueCount = $this->phpMail->getQueueCount();
        $ajaxURL = '&showqueue=phpmail&ajaxphpmail=true';

        if ($queueCount > 0) {
            $columns = array('Date', 'Email', 'Actions');
            $result .= wf_JqDtLoader($columns, self::URL_ME . $ajaxURL, false, __('Email'), 100, '"order": [[ 0, "desc" ]]');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }

        return ($result);
    }

    /**
     * Renders JSON list of available PHPMailer emails in queue with some controls
     *
     * @return void
     */
    public function renderPHPMailAjaxQueue() {
        $queue = $this->phpMail->getQueueData();
        $delURL = '&showqueue=phpmail&deletephpmail=';

        if (!empty($queue)) {
            foreach ($queue as $io => $each) {
                $actLinks = wf_modalAuto(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->phpMailPreview($each), '');
                $actLinks .= wf_JSAlert(self::URL_ME . $delURL . $each['filename'], web_delete_icon(), $this->messages->getDeleteAlert());
                $data[] = $each['date'];
                $data[] = $each['email'];
                $data[] = $actLinks;
                $this->json->addRow($data);
                unset($data);
            }
        }

        $this->json->getJson();
    }

    /**
     * Deletes email from local queue
     *
     * @param string $filename Existing email filename
     *
     * @return int
     */
    public function deletePHPMail($filename) {
        $result = $this->phpMail->deleteEmail($filename);
        return ($result);
    }

    /**
     * Returns modal window with email creation form
     *
     * @return string
     */
    public function phpMailCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newemailaddress', __('Email'), '', true, '20');
        $inputs .= wf_TextInput('newemailfrom', __('From'), '', true, '20');
        $inputs .= wf_CheckInput('newmailbodyashtml', __('Body as HTML'), true);
        $inputs .= wf_TextInput('newemailsubj', __('Subject'), '', true, '40');
        $inputs .= wf_TextArea('newemailmessage', '', '', true, '50x10');
        $inputs .= __('Add attachment') . ' <input id="fileselector" type="file" name="newmailattach" size="10" />' . wf_delimiter(0);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));

        $form = bs_UploadFormBody('', 'POST', $inputs, 'glamour');

        $result = wf_modalAuto(wf_img('skins/add_icon.png', __('Create new email')), __('Create new email'), $form, '');
        return ($result);
    }

    /**
     * Creates new PHPMail message in queue
     *
     * @param string $email
     * @param string $subj
     * @param string $messages
     * @param string $attachPath
     * @param bool $bodyAsHTML
     * @param string $from
     *
     * @return string/void
     */
    public function createPHPMail($email, $subj, $message, $attachPath = '', $bodyAsHTML = false, $from = '') {
        $result = '';

        if ((!empty($email)) AND (!empty($message)) AND (!empty($subj))) {
            $this->phpMail->sendEmail($email, $subj, $message, $attachPath, $bodyAsHTML, $from, array(), 'TQUEUE');
        } else {
            $result = __('Not all of required fields are filled');
        }

        return ($result);
    }

    /**
     * Uploads attachment file for further processing
     *
     * @return string
     */
    public function uploadAttach() {
        $result = '';
        $uploaddir = $this->phpMail->mailerAttachPath;
        $uploadfile = $uploaddir . vf($_FILES['newmailattach']['name']);

        if (move_uploaded_file($_FILES['newmailattach']['tmp_name'], $uploadfile)) {
            $result = $uploadfile;
        }

        return ($result);
    }
}

?>