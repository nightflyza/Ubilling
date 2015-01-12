<?php

class DarkVoid {

    protected $altCfg = array();
    protected $alerts = '';

    public function __construct() {
        $this->loadAlter();
        $this->loadAlerts();
    }

    /**
     * Loads global alter.ini config into protected property
     * 
     * @global type $ubillingConfig
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Renders available and enabled alerts into  DarkVoid notification area
     * 
     * @return void
     */
    public function loadAlerts() {
        //new tickets alert
        if ($this->altCfg['TB_NEWTICKETNOTIFY']) {
            $newticketcount = zb_TicketsGetAllNewCount();
            if ($newticketcount != 0) {
                $this->alerts.=wf_Link('?module=ticketing', wf_img('skins/ticketnotify.gif', $newticketcount . ' ' . __('support tickets expected processing')), false);
            }
        }

        //new signups notification
        if ($this->altCfg['SIGREQ_ENABLED']) {
            $signups = new SignupRequests();
            $newreqcount = $signups->getAllNewCount();
            if ($newreqcount != 0) {
                $this->alerts.= wf_Link('?module=sigreq', wf_img('skins/sigreqnotify.gif', $newreqcount . ' ' . __('signup requests expected processing')), false);
            }
        }

        //check for unread messages in instant messanger
        if ($this->altCfg['TB_UBIM']) {
            if (cfr('UBIM')) {
                $unreadMessageCount = im_CheckForUnreadMessages();
                if ($unreadMessageCount) {
                    //we have new messages
                    $unreadIMNotify = __('You received') . ' ' . $unreadMessageCount . ' ' . __('new messages');
                    $urlIM = $unreadIMNotify . wf_delimiter() . wf_Link("?module=ubim&checknew=true", __('Click here to go to the instant messaging service.'), false, 'ubButton');
                    $this->alerts.=wf_Link("?module=ubim&checknew=true", wf_img("skins/ubim_blink.gif", $unreadMessageCount . ' ' . __('new message received')), false, '');
                    $this->alerts.=wf_modalOpened(__('New messages received'), $urlIM, '450', '200');
                }
            }
        }

        //switchmon at nptify area
        if ($this->altCfg['TB_SWITCHMON']) {
            $dead_raw = zb_StorageGet('SWDEAD');
            $last_pingtime = zb_StorageGet('SWPINGTIME');
            $deathTime = zb_SwitchesGetAllDeathTime();
            $deadarr = array();
            $content = '';

            if ($this->altCfg['SWYMAP_ENABLED']) {
                $content= wf_Link('?module=switchmap', wf_img('skins/swmapsmall.png',__('Switches map')), false);
            }

            $content.= wf_AjaxLoader() . wf_AjaxLink("?module=switches&forcereping=true&ajaxping=true", wf_img('skins/refresh.gif', __('Force ping')), 'switchping', true, '');



            if ($dead_raw) {
                $deadarr = unserialize($dead_raw);
                if (!empty($deadarr)) {
                    //there is some dead switches
                    $deadcount = sizeof($deadarr);
                    if ($this->altCfg['SWYMAP_ENABLED']) {
                        //getting geodata
                        $switchesGeo = zb_SwitchesGetAllGeo();
                    }
                    //ajax container
                    $content.=wf_tag('div', false, '', 'id="switchping"');

                    foreach ($deadarr as $ip => $switch) {
                        if ($this->altCfg['SWYMAP_ENABLED']) {
                            if (isset($switchesGeo[$ip])) {
                                if (!empty($switchesGeo[$ip])) {
                                    $devicefind = wf_Link('?module=switchmap&finddevice=' . $switchesGeo[$ip], wf_img('skins/icon_search_small.gif', __('Find on map'))) . ' ';
                                } else {
                                    $devicefind = '';
                                }
                            } else {
                                $devicefind = '';
                            }
                        } else {
                            $devicefind = '';
                        }
                        //check morgue records for death time
                        if (isset($deathTime[$ip])) {
                            $deathClock = wf_img('skins/clock.png', __('Switch dead since') . ' ' . $deathTime[$ip]) . ' ';
                        } else {
                            $deathClock = '';
                        }
                        //add switch as dead
                        $content.=$devicefind . '&nbsp;' . $deathClock . $ip . ' - ' . $switch . '<br>';
                    }

                    //ajax container end
                    $content.=wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);

                    $this->alerts.='<div class="ubButton">' . wf_modal(__('Dead switches') . ': ' . $deadcount, __('Dead switches'), $content, '', '500', '400') . '</div>';
                } else {
                    $content.=wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);
                    $this->alerts.=wf_tag('div',false,'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div',true);
                }
            } else {
                $content.=wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . @date("H:i:s", $last_pingtime) . wf_tag('div', true);
                $this->alerts.=wf_tag('div',false,'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div',true);
            }
        }
    }

    public function render() {
        return ($this->alerts);
    }

}
?>