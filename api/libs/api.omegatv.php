<?php

class OmegaTV {

    /**
     * HlsTV object placeholder for further usage 
     *
     * @var object
     */
    protected $hls = '';

    /**
     * Contains default channel icon size
     *
     * @var int
     */
    protected $chanIconSize = 32;
    
    /**
     * Basic module path
     */
    const URL_ME='?module=omegatv';

    /**
     * Creates new OmegaTV instance
     */
    public function __construct() {
        $this->initHls();
    }

    /**
     * Inits HLS object for further usage
     * 
     * @return void
     */
    protected function initHls() {
        $this->hls = new HlsTV();
    }

    /**
     * Renders available tariffs list
     * 
     * @param string $list - tariff list to render base/bundle/promo
     * @param bool $withIds - render tariff IDs or not?
     * @param bool $withChannels - render channels preview or not?
     * 
     * @return string
     */
    public function renderTariffs($list, $withIds = true, $withChannels = true) {
        $result = '';

        switch ($list) {
            case 'base':
                $allTariffs = $this->hls->getTariffsBase();
                break;
            case 'bundle':
                $allTariffs = $this->hls->getTariffsBundle();
                break;
            case 'promo':
                $allTariffs = $this->hls->getTariffsPromo();
                break;
        }
        if (!empty($allTariffs)) {
            if (isset($allTariffs['result'])) {
                if ($list != 'promo') {
                    $allTariffs = $allTariffs['result'];
                } else {
                    $allTariffs = $allTariffs['result']['promo_limited'];
                }
                if (!empty($allTariffs)) {
                    foreach ($allTariffs as $io => $each) {
                        $tariffTitle = ($withIds) ? $each['tariff_id'] . ': ' . $each['tariff_name'] : $each['tariff_name'];
                        $result .= wf_tag('h3') . $tariffTitle . wf_tag('h3', true);
                        if ($withChannels) {
                            if (!empty($each['hls_channels'])) {
                                $cells = wf_TableCell('');
                                $cells .= wf_TableCell(__('Channels'));
                                $cells .= wf_TableCell(__('Category'));

                                $rows = wf_TableRow($cells, 'row1');
                                foreach ($each['hls_channels'] as $chanId => $eachChannel) {
                                    $cells = wf_TableCell(wf_img_sized($eachChannel['logo'], $eachChannel['name'], $this->chanIconSize), $this->chanIconSize + 10);
                                    $cells .= wf_TableCell($eachChannel['name']);
                                    $cells .= wf_TableCell($eachChannel['ganre']);

                                    $rows .= wf_TableRow($cells, 'row3');
                                }
                                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Renders some user profile info
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function renderUserInfo($customerId) {
        $result = '';
        $userInfo = $this->hls->getUserInfo($customerId);
        if (isset($userInfo['result'])) {
            $userInfo = $userInfo['result'];
            $cells = wf_TableCell(__('ID'), '', 'row2');
            $cells .= wf_TableCell($userInfo['id']);
            $rows = wf_TableRow($cells, 'row3');

            if (!empty($userInfo['tariff'])) {
                foreach ($userInfo['tariff'] as $io => $each) {
                    $cells = wf_TableCell(__('Tariffs') . ' ' . $io, '', 'row2');
                    $cells .= wf_TableCell(implode(', ', $each));
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('Status'), '', 'row2');
            $cells .= wf_TableCell($userInfo['status']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Preview'), '', 'row2');
            $cells .= wf_TableCell(wf_Link($userInfo['web_url'], __('Play'), false, '', 'TARGET="_BLANK"'));
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0);
        }
        return ($result);
    }
    
    
    /**
     * Renders default module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result='';
        $result.= wf_Link(self::URL_ME.'&subscriptions=true', wf_img('skins/ukv/users.png').' '.__('Subscriptions'), false, 'ubButton').' ';
        $result.= wf_Link(self::URL_ME.'&tariffs=true', wf_img('skins/ukv/dollar.png').' '.__('Tariffs'), false, 'ubButton').' ';
        $result.= wf_Link(self::URL_ME.'&reports=true', wf_img('skins/ukv/report.png').' '.__('Reports'), false, 'ubButton').' ';
        return($result);
    }

}

?>