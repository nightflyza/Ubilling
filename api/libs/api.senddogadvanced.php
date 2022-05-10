<?php

/**
 * Multi-service SendDog implementation
 */
class SendDogAdvanced extends SendDog {

    /**
     * Placeholder for SMS services IDs => APINames
     *
     * @var array
     */
    protected $servicesApiId = array();

    /**
     * Placeholder for default SMS service ID
     *
     * @var string
     */
    protected $defaultSmsServiceId = '';

    /**
     * Placeholder for default SMS service API name
     *
     * @var string
     */
    protected $defaultSmsServiceApi = '';

    /**
     * Placeholder for SMS_SERVICES_ADVANCED_PHPMAILER_ON alter.ini option
     *
     * @var bool
     */
    protected $phpMailerOn = false;

    /**
     * Contains path to files with services APIs implementations
     */
    const API_IMPL_PATH = 'api/vendor/sms_services_APIs/';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadAltCfg();
        $this->initSmsQueue();
        $this->initMessages();
        $this->loadTelegramConfig();
        $this->getServicesAPIsIDs();
        $this->loadPHPMailerConfig();

        $this->phpMailerOn = wf_getBoolFromVar($this->altCfg['SMS_SERVICES_ADVANCED_PHPMAILER_ON']);
    }

    /**
     * Loads PHPMailer config from storage
     */
    protected function loadPHPMailerConfig() {
        $mailerDebug = zb_StorageGet('SENDDOG_PHPMAILER_DEBUG');
        if (empty($mailerDebug)) {
            //Enable SMTP debugging
            // 1 - SMTP::DEBUG_OFF = off (for production use)
            // 2 - SMTP::DEBUG_CLIENT = client messages
            // 3 - SMTP::DEBUG_SERVER = client and server messages
            $mailerDebug = 1;
            zb_StorageSet('SENDDOG_PHPMAILER_DEBUG', $mailerDebug);
        }

        $mailerSMTPHost = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_HOST');
        if (empty($mailerSMTPHost)) {
            $mailerSMTPHost = 'smtp.mail.server';
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_HOST', $mailerSMTPHost);
        }

        $mailerSMTPPort = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_PORT');
        if (empty($mailerSMTPPort)) {
            $mailerSMTPPort = '25';
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_PORT', $mailerSMTPPort);
        }

        $mailerSMTPSecure = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_SECURE');
        if (empty($mailerSMTPSecure)) {
            $mailerSMTPSecure = 1;
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_SECURE', $mailerSMTPSecure);
        }

        $mailerSMTPAuth = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_USEAUTH');
        if (empty($mailerSMTPAuth)) {
            $mailerSMTPAuth = '';
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_USEAUTH', $mailerSMTPAuth);
        }

        $mailerSMTPUser = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_USER');
        if (empty($mailerSMTPUser)) {
            $mailerSMTPUser = '';
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_USER', $mailerSMTPUser);
        }

        $mailerSMTPPasswd = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_PASSWD');
        if (empty($mailerSMTPPasswd)) {
            $mailerSMTPPasswd = '';
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_PASSWD', $mailerSMTPPasswd);
        }

        $mailerSMTPDefaultFrom = zb_StorageGet('SENDDOG_PHPMAILER_SMTP_DEFAULTFROM');
        if (empty($mailerSMTPDefaultFrom)) {
            $mailerSMTPDefaultFrom = '';
            zb_StorageSet('SENDDOG_PHPMAILER_SMTP_DEFAULTFROM', $mailerSMTPDefaultFrom);
        }

        $mailerAttachPath = zb_StorageGet('SENDDOG_PHPMAILER_ATTACHMENTS_PATH');
        if (empty($mailerAttachPath)) {
            $mailerAttachPath = 'exports/';
            zb_StorageSet('SENDDOG_PHPMAILER_ATTACHMENTS_PATH', $mailerAttachPath);
        }


        $this->settings['SENDDOG_PHPMAILER_DEBUG'] = $mailerDebug;
        $this->settings['SENDDOG_PHPMAILER_SMTP_HOST'] = $mailerSMTPHost;
        $this->settings['SENDDOG_PHPMAILER_SMTP_PORT'] = $mailerSMTPPort;
        $this->settings['SENDDOG_PHPMAILER_SMTP_SECURE'] = $mailerSMTPSecure;
        $this->settings['SENDDOG_PHPMAILER_SMTP_USEAUTH'] = $mailerSMTPAuth;
        $this->settings['SENDDOG_PHPMAILER_SMTP_USER'] = $mailerSMTPUser;
        $this->settings['SENDDOG_PHPMAILER_SMTP_PASSWD'] = $mailerSMTPPasswd;
        $this->settings['SENDDOG_PHPMAILER_SMTP_DEFAULTFROM'] = $mailerSMTPDefaultFrom;
        $this->settings['SENDDOG_PHPMAILER_ATTACHMENTS_PATH'] = $mailerAttachPath;
    }

    /**
     * Fills up $SrvsAPIsIDs with IDs => APINames
     *
     * @return void
     */
    protected function getServicesAPIsIDs() {
        $allSmsServices = $this->getSmsServicesConfigData();

        if (!empty($allSmsServices)) {
            foreach ($allSmsServices as $index => $record) {
                if ($record['default_service']) {
                    $this->defaultSmsServiceId = $record['id'];
                    $this->defaultSmsServiceApi = $record['api_file_name'];
                }

                $this->servicesApiId[$record['id']] = $record['api_file_name'];
            }
        }
    }

    /**
     * Returns array with contents of API_IMPL_PATH dir with names of implemented services APIs
     *
     * @param bool $useValueAsIndex - if true API name used as array index(key) also
     *
     * @return array
     */
    protected function getImplementedSmsServicesApiNames($useValueAsIndex = false) {
        $apiImplementations = rcms_scandir(self::API_IMPL_PATH, '*.php');

        foreach ($apiImplementations as $index => $item) {
            $apiName = str_replace('.php', '', $item);
            $apiImplementations[$index] = $apiName;

            if ($useValueAsIndex) {
                $apiImplementations[$apiName] = $apiImplementations[$index];
                unset($apiImplementations[$index]);
            }
        }

        return $apiImplementations;
    }

    /**
     * Gets SMS services config data from DB
     *
     * @param string $whereString of the query, including ' WHERE ' keyword
     *
     * @return array
     */
    public function getSmsServicesConfigData($whereString = '') {
        if (empty($whereString)) {
            $whereString = " ";
        }

        $query = "SELECT * FROM `sms_services` " . $whereString . " ;";
        $result = simple_queryall($query);

        return $result;
    }

    /**
     * Returns true if SMS service with such name already exists
     *
     * @param $serviceName
     * @param int $excludeEditedServiceId
     *
     * @return string
     */
    public function checkServiceNameExists($serviceName, $excludeEditedServiceId = 0) {
        $serviceName = trim($serviceName);

        if (empty($excludeEditedServiceId)) {
            $query = "SELECT `id` FROM `sms_services` WHERE `name` = '" . $serviceName . "';";
        } else {
            $query = "SELECT `id` FROM `sms_services` WHERE `name` = '" . $serviceName . "' AND `id` != '" . $excludeEditedServiceId . "';";
        }

        $result = simple_queryall($query);

        return ( empty($result) ) ? '' : $result[0]['id'];
    }

    /**
     * Returns reference to UbillingConfig object
     *
     * @return object
     */
    public function getUBConfigInstance() {
        return $this->ubConfig;
    }

    /**
     * Returns reference to UbillingSMS object
     *
     * @return object
     */
    public function getSmsQueueInstance() {
        return $this->smsQueue;
    }

    /**
     * Returns reference to UbillingMessageHelper object
     *
     * @return object
     */
    public function getUBMsgHelperInstance() {
        return $this->messages;
    }

    /**
     * Changes telegram bot token if differs from already stored
     *
     * @param $token
     */
    public function editTelegramBotToken($token) {
        //telegram bot token configuration
        if ($token != $this->settings['TELEGRAM_BOTTOKEN']) {
            zb_StorageSet('SENDDOG_TELEGRAM_BOTTOKEN', $token);
            log_register('SENDDOG CONFIG SET TELEGRAMBOTTOKEN');
        }
    }

    /**
     * Returns set of inputs, required for Telegram service configuration
     *
     * @return string
     */
    public function renderTelegramConfigInputs() {
        $inputs = wf_tag('h2');
        $inputs .= __('Telegram bot token') . '&nbsp' . wf_Link(self::URL_ME . '&showmisc=telegramcontacts', wf_img_sized('skins/icon_search_small.gif', __('Telegram bot contacts'), '16', '16'));
        $inputs .= wf_tag('h2', true);
        $inputs .= wf_TextInput('edittelegrambottoken', '', $this->settings['TELEGRAM_BOTTOKEN'], false, '50');

        return ($inputs);
    }

    /**
     * Changes PHPMailer settings
     */
    public function editPHPMailerConfig($smtpdebug, $smtphost, $smtpport, $smtpsecure, $smtpuser, $smtppasswd, $smtpfrom, $smtpauth, $attachpath) {
        zb_StorageSet('SENDDOG_PHPMAILER_DEBUG', $smtpdebug);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_HOST', $smtphost);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_PORT', $smtpport);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_SECURE', $smtpsecure);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_USER', $smtpuser);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_PASSWD', $smtppasswd);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_DEFAULTFROM', $smtpfrom);
        zb_StorageSet('SENDDOG_PHPMAILER_SMTP_USEAUTH', $smtpauth);
        zb_StorageSet('SENDDOG_PHPMAILER_ATTACHMENTS_PATH', $attachpath);

        log_register('SENDDOG PHPMailer settings changed');
    }

    public function renderPHPMailerConfigInputs() {
        // smtpDebug = 0, 1, 2 - off, client, server
        $inputs = wf_tag('h2');
        $inputs .= __('PHPMailer settings');
        $inputs .= wf_tag('h2', true);
        $inputs .= wf_TextInput('editsmtpdebug', 'SMTP debug feature(1 - off, 2 - client messages debug, 3 - server & client messages debug)', $this->settings['SENDDOG_PHPMAILER_DEBUG'], true, '5', 'digits');
        $inputs .= wf_TextInput('editsmtpsecure', 'SMTP secure connection type (1 - off, 2 - TLS, 3 - SSL)', $this->settings['SENDDOG_PHPMAILER_SMTP_SECURE'], true, '5', 'digits');
        $inputs .= wf_TextInput('editsmtphost', 'SMTP host', $this->settings['SENDDOG_PHPMAILER_SMTP_HOST'], true);
        $inputs .= wf_TextInput('editsmtpport', 'SMTP port', $this->settings['SENDDOG_PHPMAILER_SMTP_PORT'], true, '20', 'digits');
        $inputs .= wf_TextInput('editsmtpuser', 'SMTP user name', $this->settings['SENDDOG_PHPMAILER_SMTP_USER'], true);
        $inputs .= wf_PasswordInput('editsmtppasswd', 'SMTP user password', $this->settings['SENDDOG_PHPMAILER_SMTP_PASSWD'], true);
        $inputs .= wf_TextInput('editsmtpdefaultfrom', 'SMTP default "From" value', $this->settings['SENDDOG_PHPMAILER_SMTP_DEFAULTFROM'], true);
        $inputs .= wf_TextInput('editattachpath', 'Attachments temporary upload path', $this->settings['SENDDOG_PHPMAILER_ATTACHMENTS_PATH'], true, '25');
        $inputs .= wf_CheckInput('editsmtpuseauth', 'SMTP use authentication', true, wf_getBoolFromVar($this->settings['SENDDOG_PHPMAILER_SMTP_USEAUTH']));
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Save'));

        $form = wf_Form('', 'POST', $inputs, 'glamour');

        return ($form);
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

            foreach ($queryData as $eachRec) {
                foreach ($eachRec as $fieldName => $fieldVal) {
                    switch ($fieldName) {
                        case 'default_service':
                            $data[] = ($fieldVal == 1) ? web_green_led() : web_red_led();
                            break;

                        case 'passwd':
                            if (!$this->ubConfig->getAlterParam('PASSWORDSHIDE')) {
                                $data[] = $fieldVal;
                            }
                            break;

                        default:
                            $data[] = $fieldVal;
                    }
                }

                $linkId = wf_InputId();
                $linkId2 = wf_InputId();
                $linkId3 = wf_InputId();
                $actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteSMSSrv(' . $eachRec['id'] . ', \'' . self::URL_ME . '\', \'deleteSMSSrv\', \'' . wf_InputId() . '\')') . ' ';
                $actions .= wf_tag('a', false, '', 'id="' . $linkId . '" href="#"');
                $actions .= web_edit_icon();
                $actions .= wf_tag('a', true);
                $actions .= wf_nbsp();
                $actions .= wf_tag('a', false, '', 'id="' . $linkId2 . '" href="#"');
                $actions .= wf_img_sized('skins/icon_dollar.gif', __('Balance'), '16', '16');
                $actions .= wf_tag('a', true);
                $actions .= wf_nbsp();
                $actions .= wf_tag('a', false, '', 'id="' . $linkId3 . '" href="#"');
                $actions .= wf_img_sized('skins/icon_sms_micro.gif', __('View SMS sending queue'), '16', '16');
                $actions .= wf_tag('a', true);
                $actions .= wf_tag('script', false, '', 'type="text/javascript"');
                $actions .= '
                                $(\'#' . $linkId . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME . '",
                                        data: { 
                                                action:"editSMSSrv",
                                                smssrvid:"' . $eachRec['id'] . '",                                                                                                                
                                                modalWindowId:"dialog-modal_' . $linkId . '", 
                                                ModalWBID:"body_dialog-modal_' . $linkId . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $linkId . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                                
                                $(\'#' . $linkId2 . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME . '",
                                        data: { 
                                                action:"getBalance",
                                                smssrvid:"' . $eachRec['id'] . '",                                                                                                                
                                                SMSAPIName:"' . $eachRec['api_file_name'] . '",
                                                modalWindowId:"dialog-modal_' . $linkId2 . '", 
                                                ModalWBID:"body_dialog-modal_' . $linkId2 . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $linkId2 . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                                
                                $(\'#' . $linkId3 . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME . '",
                                        data: { 
                                                action:"getSMSQueue",
                                                smssrvid:"' . $eachRec['id'] . '",                                                                                                                
                                                SMSAPIName:"' . $eachRec['api_file_name'] . '",
                                                modalWindowId:"dialog-modal_' . $linkId3 . '", 
                                                ModalWBID:"body_dialog-modal_' . $linkId3 . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $linkId3 . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                            ';
                $actions .= wf_tag('script', true);

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
        $hidePasswords = $this->ubConfig->getAlterParam('PASSWORDSHIDE');
        $columnTarget1 = ($hidePasswords) ? '4' : '5';
        $columnTarget2 = ($hidePasswords) ? '6' : '7';
        $columnTarget3 = ($hidePasswords) ? '7' : '8';
        $columnTarget4 = ($hidePasswords) ? '[5, 6, 7, 8]' : '[6, 7, 8, 9]';
        $columnTarget5 = ($hidePasswords) ? '[0, 1, 2, 3]' : '[0, 1, 2, 3, 4]';
        $columns = array();
        $opts = ' "order": [[ 0, "desc" ]], 
                                "columnDefs": [ {"className": "dt-head-center", "targets": ' . $columnTarget5 . '},
                                                {"width": "20%", "className": "dt-head-center jqdt_word_wrap", "targets": ' . $columnTarget1 . '}, 
                                                {"width": "8%", "targets": ' . $columnTarget2 . '},
                                                {"width": "10%", "targets": ' . $columnTarget3 . '},
                                                {"className": "dt-center", "targets": ' . $columnTarget4 . '} ]';
        $columns[] = ('ID');
        $columns[] = __('Name');
        $columns[] = __('Login');
        if (!$hidePasswords) {
            $columns[] = __('Password');
        }
        $columns[] = __('Gateway URL/IP');
        $columns[] = __('API key');
        $columns[] = __('Alpha name');
        $columns[] = __('Default service');
        $columns[] = __('API implementation file');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxUrlStr, false, __('results'), 100, $opts);

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= wf_JSEmptyFunc();
        $result .= wf_JSElemInsertedCatcherFunc();
        $result .= '
                    // making an event binding for "SMS service edit form" Submit action 
                    // to be able to create "SMS service add/edit form" dynamically                    
                    function toggleAlphaNameFieldReadonly() {
                        if ( $(".__SMSSrvAlphaAsLoginChk").is(\':checked\') ) {
                            $(".__SMSSrvAlphaName").val("");
                            $(".__SMSSrvAlphaName").attr("readonly", "readonly");
                            $(".__SMSSrvAlphaName").css(\'background-color\', \'#CECECE\');
                        } else {
                            $(".__SMSSrvAlphaName").removeAttr("readonly");               
                            $(".__SMSSrvAlphaName").css(\'background-color\', \'#FFFFFF\');
                        }
                    }

                    onElementInserted(\'body\', \'.__SMSSrvAlphaAsLoginChk\', function(element) {
                        toggleAlphaNameFieldReadonly();
                    });
                   
                    $(document).on("change", ".__SMSSrvAlphaAsLoginChk", function(evt) {
                          toggleAlphaNameFieldReadonly();
                    });
                    
                    function chekEmptyVal(ctrlCalssName) {
                        $(document).on("focus keydown", ctrlCalssName, function(evt) {
                            if ( $(ctrlCalssName).css("border-color") == "rgb(255, 0, 0)" ) {
                                $(ctrlCalssName).val("");
                                $(ctrlCalssName).css("border-color", "");
                                $(ctrlCalssName).css("color", "");
                            }
                        });
                    }
                     
                    onElementInserted(\'body\', \'.__EmptyCheck\', function(element) {
                        chekEmptyVal(\'.__EmptyCheck\');
                    });

                    $(document).on("submit", ".__SMSSrvForm", function(evt) {
                        var AlphaNameAsLogin = ( $(".__SMSSrvAlphaAsLoginChk").is(\':checked\') ) ? 1 : 0;
                        //var DefaultService   = ( $(".__SMSSrvDefaultSrvChk").is(\':checked\') ) ? 1 : 0;
                        var DefaultService   = ( $(".__SMSSrvDefaultSrvChk").is(\':checked\') ) ? 1 : ( $(".__DefaultServHidID").val() ) ? 1 : 0;
                        var FrmAction        = $(".__SMSSrvForm").attr("action");
                        var FrmData          = $(".__SMSSrvForm").serialize() + \'&smssrvalphaaslogin=\' + AlphaNameAsLogin + \'&smssrvdefault=\' + DefaultService + \'&errfrmid=' . $errorModalWindowId . '\'; 
                        //var modalWindowId    = $(".__SMSSrvForm").closest(\'div\').attr(\'id\');
                        evt.preventDefault();

                        var emptyCheckClass = \'.__EmptyCheck\';
                    
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
                                                $( \'#\'+$(".__SMSSrvFormModalWindowID").val() ).dialog("close");
                                            }
                                        }
                            });                       
                        }
                    });
    
                    function deleteSMSSrv(SMSSrvID, AjaxURL, ActionName, ErrFrmID) {
                        $.ajax({
                                type: "POST",
                                url: AjaxURL,
                                data: {action:ActionName, smssrvid:SMSSrvID, errfrmid:ErrFrmID},
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+ErrFrmID).dialog("open");
                                            }
                                            
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }
                ';
        $result .= wf_tag('script', true);

        return $result;
    }

    /**
     * Returns SMS srvice addition form
     *
     * @return string
     */
    public function renderAddForm($modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $alphaAsLoginChkId = 'AlphaAsLoginChkID_' . wf_InputId();
        $defaultServiceChkId = 'DefaultServChkID_' . wf_InputId();
        $defaultServiceHidId = 'DefaultServHidID_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $apiImplementations = $this->getImplementedSmsServicesApiNames(true);

// check if there is any services already added
        $query = "SELECT `id` FROM `sms_services`;";
        $result = simple_queryall($query);
        $useAsDefaultService = ( empty($result) );    // if no services yet - use the first added as default

        $inputs = wf_TextInput('smssrvname', __('Name'), '', true, '', '', '__EmptyCheck');
        $inputs .= wf_TextInput('smssrvlogin', __('Login'), '', true);
        $inputs .= wf_CheckInput('smssrvalphaaslogin', __('Use login as alpha name'), true, false, $alphaAsLoginChkId, '__SMSSrvAlphaAsLoginChk');
        $inputs .= ($this->ubConfig->getAlterParam('PASSWORDSHIDE')) ? wf_PasswordInput('smssrvpassw', __('Password'), '', true) :
                wf_TextInput('smssrvpassw', __('Password'), '', true);
        $inputs .= wf_TextInput('smssrvurlip', __('Gateway URL/IP'), '', true);
        $inputs .= wf_TextInput('smssrvapikey', __('API key'), '', true);
        $inputs .= wf_TextInput('smssrvalphaname', __('Alpha name'), '', true, '', '', '__SMSSrvAlphaName');
        $inputs .= wf_Selector('smssrvapiimplementation', $apiImplementations, __('API implementation file'), '', true);

        if ($useAsDefaultService) {
            $inputs .= wf_tag('span', false, '', 'style="display: block; margin: 5px 2px"');
            $inputs .= __('Will be used as a default SMS service');
            $inputs .= wf_tag('span', true);
            $inputs .= wf_HiddenInput('smssrvdefault', 'true', $defaultServiceHidId, '__DefaultServHidID');
        } else {
            $inputs .= wf_CheckInput('smssrvdefault', __('Use as default SMS service'), true, false, $defaultServiceChkId, '__SMSSrvDefaultSrvChk');
        }

        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__SMSSrvFormModalWindowID');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), false, true, $closeFormChkId);
        $inputs .= wf_HiddenInput('smssrvcreate', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SMSSrvForm', '', $formId);

        return ($form);
    }

    /**
     * Returns SMS service editing form
     *
     * @return string
     */
    public function renderEditForm($smsServiceId, $modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $alphaAsLoginChkId = 'AlphaAsLoginChkID_' . wf_InputId();
        $defaultServiceChkId = 'DefaultServChkID_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $apiImplementations = $this->getImplementedSmsServicesApiNames(true);
        $smsServiceData = $this->getSmsServicesConfigData(" WHERE `id` = " . $smsServiceId);

        $serviceName = $smsServiceData[0]['name'];
        $serviceLogin = $smsServiceData[0]['login'];
        $servicePassword = $smsServiceData[0]['passwd'];
        $serviceGatewayAddr = $smsServiceData[0]['url_addr'];
        $serviceAlphaName = $smsServiceData[0]['alpha_name'];
        $serviceApiKey = $smsServiceData[0]['api_key'];
        $serviceIsDefault = $smsServiceData[0]['default_service'];
        $serviceApiFile = $smsServiceData[0]['api_file_name'];

        $inputs = wf_TextInput('smssrvname', __('Name'), $serviceName, true, '', '', '__EmptyCheck');
        $inputs .= wf_TextInput('smssrvlogin', __('Login'), $serviceLogin, true);
        $inputs .= wf_CheckInput('smssrvalphaaslogin', __('Use login as alpha name'), true, (!empty($serviceLogin) and $serviceLogin == $serviceAlphaName), $alphaAsLoginChkId, '__SMSSrvAlphaAsLoginChk');
        $inputs .= ($this->ubConfig->getAlterParam('PASSWORDSHIDE')) ? wf_PasswordInput('smssrvpassw', __('Password'), $servicePassword, true) :
                wf_TextInput('smssrvpassw', __('Password'), $servicePassword, true);
        $inputs .= wf_TextInput('smssrvurlip', __('Gateway URL/IP'), $serviceGatewayAddr, true);
        $inputs .= wf_TextInput('smssrvapikey', __('API key'), $serviceApiKey, true);
        $inputs .= wf_TextInput('smssrvalphaname', __('Alpha name'), $serviceAlphaName, true, '', '', '__SMSSrvAlphaName');
        $inputs .= wf_Selector('smssrvapiimplementation', $apiImplementations, __('API implementation file'), $serviceApiFile, true);
        $inputs .= wf_CheckInput('smssrvdefault', __('Use as default SMS service'), true, $serviceIsDefault, $defaultServiceChkId, '__SMSSrvDefaultSrvChk');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), false, true, $closeFormChkId);
        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__SMSSrvFormModalWindowID');
        $inputs .= wf_HiddenInput('action', 'editSMSSrv');
        $inputs .= wf_HiddenInput('smssrvid', $smsServiceId);
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Edit'));

        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SMSSrvForm', '', $formId);

        return $form;
    }

    /**
     * Adds SMS service to DB
     *
     * @param $smsServiceName
     * @param $smsServiceLogin
     * @param $smsServicePassword
     * @param $smsServiceBaseUrl
     * @param $smsServiceApiKey
     * @param $smsServiceAlphaName
     * @param $smsServiceApiImplName
     * @param int $useAsDefaultService
     */
    public function addSmsService($smsServiceName, $smsServiceLogin, $smsServicePassword, $smsServiceBaseUrl, $smsServiceApiKey, $smsServiceAlphaName, $smsServiceApiImplName, $useAsDefaultService = 0) {

        if ($useAsDefaultService) {
            $tQuery = "UPDATE `sms_services` SET `default_service` = 0;";
            nr_query($tQuery);
        }

        $tQuery = "INSERT INTO `sms_services` ( `id`,`name`,`login`,`passwd`, `url_addr`, `api_key`, `alpha_name`, `default_service`, `api_file_name`) 
                                      VALUES  ( NULL, '" . $smsServiceName . "','" . $smsServiceLogin . "','" . $smsServicePassword . "','" . $smsServiceBaseUrl . "','" .
                $smsServiceApiKey . "','" . $smsServiceAlphaName . "','" . $useAsDefaultService . "','" . $smsServiceApiImplName . "');";
        nr_query($tQuery);
        log_register('CREATE SMS service [' . $smsServiceName . '] alpha name: `' . $smsServiceAlphaName . '`');
    }

    /**
     * Edits SMS service
     *
     * @param $smsServiceId
     * @param $smsServiceName
     * @param $smsServiceLogin
     * @param $smsServicePassword
     * @param $smsServiceBaseUrl
     * @param $smsServiceApiKey
     * @param $smsServiceAlphaName
     * @param $smsServiceApiImplName
     * @param int $useAsDefaultService
     */
    public function editSmsService($smsServiceId, $smsServiceName, $smsServiceLogin, $smsServicePassword, $smsServiceBaseUrl, $smsServiceApiKey, $smsServiceAlphaName, $smsServiceApiImplName, $useAsDefaultService = 0) {

        if ($useAsDefaultService) {
            $tQuery = "UPDATE `sms_services` SET `default_service` = 0;";
            nr_query($tQuery);
        }

        $tQuery = "UPDATE `sms_services` 
                        SET `name` = '" . $smsServiceName . "', 
                            `login` = '" . $smsServiceLogin . "', 
                            `passwd` = '" . $smsServicePassword . "', 
                            `url_addr` = '" . $smsServiceBaseUrl . "', 
                            `api_key` = '" . $smsServiceApiKey . "', 
                            `alpha_name` = '" . $smsServiceAlphaName . "', 
                            `default_service` = '" . $useAsDefaultService . "', 
                            `api_file_name` = '" . $smsServiceApiImplName . "' 
                    WHERE `id`= '" . $smsServiceId . "' ;";
        nr_query($tQuery);
        log_register('CHANGE SMS service [' . $smsServiceId . '] `' . $smsServiceName . '` alpha name: `' . $smsServiceAlphaName . '`');
    }

    /**
     * Deletes SMS service
     *
     * @param $smsServiceId
     * @param string $smsServiceName
     * @param string $smsServiceAlphaName
     */
    public function deleteSmsService($smsServiceId, $smsServiceName = '', $smsServiceAlphaName = '') {
        $query = "DELETE FROM `sms_services` WHERE `id` = '" . $smsServiceId . "';";
        nr_query($query);
        log_register('DELETE SMS service [' . $smsServiceId . '] `' . $smsServiceName . '` alpha name: `' . $smsServiceAlphaName . '`');
    }

    /**
     * Check if SMS service is protected from deletion
     *
     * @param $smsServiceId
     *
     * @return bool
     */
    public function checkSmsServiceProtected($smsServiceId) {
        $query = "SELECT `id` FROM `sms_services_relations` WHERE `sms_srv_id` = " . $smsServiceId . ";";
        $result = simple_queryall($query);

        return (!empty($result));
    }

    /**
     * Loads and sends all stored SMS from system queue
     * Or checks statuses of already sent SMS
     * 
     * @param bool $checkStatuses
     *
     * @return integer
     */
    public function smsProcessing($checkStatuses = false) {
        $allMessages = array();
        $smsCount = 0;

        if ($checkStatuses) {
            $smsCheckStatusExpireDays = $this->altCfg['SMS_CHECKSTATUS_EXPIRE_DAYS'];
            $query = "UPDATE `sms_history` SET `no_statuschk` = 1,
                                               `send_status` = '" . __('SMS status check period expired') . "'
                        WHERE ABS( DATEDIFF(NOW(), `date_send`) ) > " . $smsCheckStatusExpireDays . " 
                              AND no_statuschk < 1 AND `delivered` < 1;";
            nr_query($query);

            $query = "SELECT * FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";
            $messages = simple_queryall($query);
            $smsCount = count($messages);
            if ($smsCount > 0) {
                $allMessages = zb_sortArray($messages, 'smssrvid');
            }
        } else {
            $smsCount = $this->smsQueue->getQueueCount();
            if ($smsCount > 0) {
                $allMessages = zb_sortArray($this->smsQueue->getQueueData(), 'smssrvid');
            }
        }

        /*
          Annie, are you okay, you okay, you okay, Annie?
          Annie, are you okay, you okay, you okay, Annie?
          Annie, are you okay, you okay, you okay, Annie?
          Annie, are you okay, you okay, you okay, Annie?
         */
        if (!empty($smsCount)) {
            $nextServiceId = null;
            $currentServiceId = null;
            $tmpMessagePack = array();
            $arrayEnd = false;

            end($allMessages);
            $lastArrayKey = key($allMessages);

            foreach ($allMessages as $io => $eachmessage) {
// checking, if we're at the end of array and current element is the last one
                if ($io === $lastArrayKey) {
                    $arrayEnd = true;
// if we're at the end of array and $TmpMessPack is empty - that means that probably array consists only of one element
// but if $TmpMessPack is NOT empty - that probably means that we've reached the last message for the current SMS service(smssrvid)
                    //if (empty($tmpMessagePack)) {
                        $tmpMessagePack[] = $eachmessage;
                    //}
                }

                if (is_null($nextServiceId) and is_null($currentServiceId)) {
// init the values on the very begining of the array
                    $nextServiceId = $eachmessage['smssrvid'];
                    $currentServiceId = $eachmessage['smssrvid'];
                } else {
// just getting next SMS service ID
                    $nextServiceId = $eachmessage['smssrvid'];
                }
// checking if SMS service ID is changed comparing to previous one or we reached the end of an array
// if so - we need to process accumulated messages in $TmpMessPack
// if not - keep going to the next array element and accumulate messages to $TmpMessPack
                if (($nextServiceId !== $currentServiceId or $arrayEnd) and ! empty($tmpMessagePack)) {
                    $this->actualSmsProcessing($tmpMessagePack, $currentServiceId, $checkStatuses);

                    $tmpMessagePack = array();
                }

                $tmpMessagePack[] = $eachmessage;

// checking and processing the very last element of the $AllMessages array if it has different SMS service ID
                if (($nextServiceId !== $currentServiceId and $arrayEnd) and ! empty($tmpMessagePack)) {
                    $this->actualSmsProcessing($tmpMessagePack, $nextServiceId, $checkStatuses);
                }

                $currentServiceId = $eachmessage['smssrvid'];
            }
        }

        return ($smsCount);
    }

    /**
     * Creates SMS service object from given API file name and processes the
     *
     * @param $messagePack
     * @param int $serviceId
     * @param bool $checkStatuses
     *
     * @return void
     */
    protected function actualSmsProcessing($messagePack, $serviceId = 0, $checkStatuses = false) {
// if for some reason $serviceId is empty - use SMS service chosen as default
        if (empty($serviceId) or $serviceId == $this->defaultSmsServiceId) {
            $serviceId = $this->defaultSmsServiceId;
            $serviceApi = $this->defaultSmsServiceApi;
        } else {
            $serviceApi = (empty($this->servicesApiId[$serviceId])) ? '' : $this->servicesApiId[$serviceId];
        }

        if (empty($serviceApi)) {
            log_register('SENDDOG SMS service with ID [' . $serviceId . '] does not exists');
        } else {
            include_once (self::API_IMPL_PATH . $serviceApi . '.php');
            $tmpApiObj = new $serviceApi($serviceId, $messagePack);

            if ($checkStatuses) {
                $tmpApiObj->checkMessagesStatuses();
            } else {
                $tmpApiObj->pushMessages();
            }
        }
    }

    /**
     * Loads and sends all email messages from system queue via PHPMailer
     *
     * @return int
     */
    public function phpMailProcessing() {
        $email = new UbillingPHPMail();
        $messagesCount = $email->getQueueCount();

        if ($messagesCount > 0) {
            $allMessagesData = $email->getQueueData();

            if (!empty($allMessagesData)) {
                foreach ($allMessagesData as $io => $eachmessage) {
                    $email->directPushEmail($eachmessage['email'], $eachmessage['subj'], $eachmessage['message'], $eachmessage['attachpath'], $eachmessage['bodyashtml'], $eachmessage['from'], $eachmessage['customheaders']);

                    $email->phpMailer->clearAllRecipients();
                    $email->phpMailer->clearAttachments();
                    $email->deleteAttachment($eachmessage['attachpath']);
                    $email->deleteEmail($eachmessage['filename']);
                }
            }
        }

        return ($messagesCount);
    }

    /**
     * Dirty input data filtering
     *
     * @param $string - string to filter
     *
     * @return string
     */
    public function safeEscapeString($string) {
        @$result = preg_replace("#[~@\?\%\/\;=\*\>\<\"\']#Uis", '', $string);

        return ($result);
    }

}
