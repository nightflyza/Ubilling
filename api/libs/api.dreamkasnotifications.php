<?php

/**
 * DreamKas notification area
 */
class DreamKasNotifications {

    /**
     * UbillingConfig object placeholder
     *
     * @var null
     */
    protected $ubConfig = null;

    /**
     * UbillingCache instance placeholder
     *
     * @var null
     */
    protected $ubCache = null;

    /**
     * Placeholder for DREAMKAS_NOTIFICATIONS_ENABLED alter.ini option
     *
     * @var bool
     */
    protected $notysEnabled = false;

    /**
     * Placeholder for DREAMKAS_CACHE_CHECK_INTERVAL alter.ini option
     *
     * @var int
     */
    protected $notysPollingInterval = 8000;

    /**
     * Placeholder for DREAMKAS_POPUP_TIMEOUT alter.ini option
     *
     * @var int
     */
    protected $notysPopupTimeout = 10000;

    /**
     * Placeholder for DREAMKAS_NOTIFY_ANYWHERE alter.ini option
     *
     * @var bool
     */
    protected $notysEverywhere = true;

    /**
     * Placeholder for DREAMKAS_DESKTOP_NOTIFICATIONS alter.ini option
     *
     * @var bool
     */
    protected $notysOnDesktop = false;

    /**
     * Placeholder for DREAMKAS_ADMINS_ALLOWED alter.ini option
     *
     * @var array
     */
    protected $notysAdminsAllowed = array();

    /**
     * Caching timeout based on polling interval in seconds.
     *
     * @var int
     */
    protected $cachingTimeout = 8;

    /**
     * Contains current instance admin user login
     *
     * @var string
     */
    protected $curAdminLogin = '';

    const URL_NOTIFICATIONS = '?module=dreamkas&getnotys=true';
    const DREAMKAS_NOTYS_CAHCE_KEY = 'DREAMKAS_NOTIFICATIONS';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->ubCache = new UbillingCache();
        $this->loadOptions();
    }

    /**
     * Getting an alter.ini options
     *
     * @return void
     */
    protected function loadOptions() {
        $this->notysEnabled = wf_getBoolFromVar($this->ubConfig->getAlterParam('DREAMKAS_NOTIFICATIONS_ENABLED'));
        $this->notysPollingInterval = ($this->ubConfig->getAlterParam('DREAMKAS_CACHE_CHECK_INTERVAL')) ? $this->ubConfig->getAlterParam('DREAMKAS_CACHE_CHECK_INTERVAL') * 1000 : 8000;
        $this->cachingTimeout = ($this->ubConfig->getAlterParam('DREAMKAS_CACHE_CHECK_INTERVAL')) ? $this->ubConfig->getAlterParam('DREAMKAS_CACHE_CHECK_INTERVAL') : 8;
        $this->notysPopupTimeout = ($this->ubConfig->getAlterParam('DREAMKAS_POPUP_TIMEOUT')) ? $this->ubConfig->getAlterParam('DREAMKAS_POPUP_TIMEOUT') * 1000 : 10000;
        $this->notysEverywhere = wf_getBoolFromVar($this->ubConfig->getAlterParam('DREAMKAS_NOTIFY_ANYWHERE'));
        $this->notysOnDesktop = wf_getBoolFromVar($this->ubConfig->getAlterParam('DREAMKAS_DESKTOP_NOTIFICATIONS'));
        $this->notysAdminsAllowed = explode(',', str_replace(' ', '', $this->ubConfig->getAlterParam('DREAMKAS_ADMINS_ALLOWED')));
        $this->notysAdminsAllowed = array_flip($this->notysAdminsAllowed);
    }

    public function getDreamkasNotifications() {
        $noty = array();
        $count = 0;
        $notyCached = $this->ubCache->get(self::DREAMKAS_NOTYS_CAHCE_KEY, $this->cachingTimeout);

        if (!empty($notyCached)) {
            foreach ($notyCached as $eachNoty) {
                $notificationText = wf_tag('div', false, 'dreamkastext');
                $notificationText .= wf_tag('span', false, 'dreamkastitle') . $eachNoty['title'] . wf_tag('span', true);
                $notificationText .= wf_delimiter() . $eachNoty['text'];
                $notificationText .= wf_tag('div', true);

                $noty[$count]['text'] = $notificationText;
                $noty[$count]['type'] = $eachNoty['type'];
                $noty[$count]['index'] = $count;

                $count++;
            }

            $this->ubCache->delete(self::DREAMKAS_NOTYS_CAHCE_KEY);
        }

        die(json_encode($noty));
    }

    /**
     * Returns notification frontend with some background polling
     *
     * @return string
     */
    protected function getDreamkasNotificationsJS() {
        $result = '';
        //some custom style
        $result .= wf_tag('style');
        //this style is inline for preventing of css caching
        $result .= '
                #noty_layout__bottomRight {
                    width: 480px !important;
                }

                .dreamkastext {
                    float: left;
                    display: block;
                    font-size: 11pt;
                    margin: 10px 1px;                    
                }
                
                .dreamkastitle {
                    font-weight: 700;
                }
            ';

        if ($this->notysOnDesktop) {
            $result .= '
                #noty_layout__bottomRight {
                margin-bottom: 120px !important;
                }
            ';
        }

        $result .= wf_tag('style', true);
        //basic notification frontend
        $result .= wf_tag('script');
        $result .= '
                $(document).ready(function() {

                Notification.requestPermission().then(function(result) {
                    console.log(result);
                });

                $(".dismiss").click(function(){$("#notification").fadeOut("slow");});
                   setInterval(
                   function() {
                    $.get("' . self::URL_NOTIFICATIONS . '&reqadm=' . $this->curAdminLogin . '", function(message) {
                    if (message) {
                    var data= JSON.parse(message);
                    data.forEach(function(key) {  
                    new Noty({
                        theme: \'bootstrap-v4\',
                        timeout: \'' . $this->notysPopupTimeout . '\',
                        progressBar: true,
                        type: key.type,
                        layout: \'bottomRight\',
                        killer: key.index,
                        queue: key.index,
                        text: key.text
                        }).show();

                        if (typeof (sendNotificationDesktop) === "function") {
                            var title = "' . __('Dreamkas notification') . '";
                            var options = {
                                body: key.text,                                
                                tag: key.index,
                                dir: "auto"
                            };
                                sendDSNotificationDesktop(title, options, key.link);
                        }
                    });
                        }
                      }
                    )
                    },
                    ' . $this->notysPollingInterval . ');
                })
                ';
        $result .= wf_tag('script', true);

        if ($this->notysOnDesktop) {
            $result .= wf_tag('script');
            $result .= '
                   function sendDSNotificationDesktop(title, options, link) {
                        if (Notification.permission === "granted") {
                            var notification = new Notification(title, options);
                            if(link) {
                                notification.onclick = function() {
                                    window.open(link,"_self");
                                }
                            }
                        } else if (Notification.permission !== "denied") {
                            Notification.requestPermission(function (permission) {
                                if (permission === "granted") {
                                    var notification = new Notification(title, options);
                                    if(link) {
                                        notification.onclick = function() {
                                            window.open(link,"_self");
                                        }
                                    }
                                }
                            });
                        }
                        };

                    ';
            $result .= wf_tag('script', true);
        }

        return ($result);
    }

    /**
     * Renders widget code if it required for current situation
     *
     * @return string/void
     */
    public function renderWidget() {
        $result = '';

        if (cfr('DREAMKAS')) {
            if ($this->ubConfig->getAlterParam('DREAMKAS_ENABLED')) {
                $widget = $this->getDreamkasNotificationsJS();

                if ($this->notysEverywhere) {
                    $result .= $widget;
                } else {
                    if ((@$_GET['module'] == 'taskbar') OR ( !isset($_GET['module']))) {
                        $result .= $widget;
                    }
                }

                //per-admin controls
                if ((!empty($this->notysAdminsAllowed) AND ( !isset($this->notysAdminsAllowed[$this->curAdminLogin])))) {
                    $result = '';
                }

                return ($result);
            }
        }
    }

}
