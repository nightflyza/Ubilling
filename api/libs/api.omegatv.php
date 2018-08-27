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
                        $result.=wf_tag('h3') . $tariffTitle . wf_tag('h3', true);
                        if ($withChannels) {
                            if (!empty($each['hls_channels'])) {
                                $cells = wf_TableCell('');
                                $cells.= wf_TableCell(__('Channels'));
                                $cells.= wf_TableCell(__('Category'));

                                $rows = wf_TableRow($cells, 'row1');
                                foreach ($each['hls_channels'] as $chanId => $eachChannel) {
                                    $cells = wf_TableCell(wf_img_sized($eachChannel['logo'], $eachChannel['name'], $this->chanIconSize), $this->chanIconSize + 10);
                                    $cells.= wf_TableCell($eachChannel['name']);
                                    $cells.= wf_TableCell($eachChannel['ganre']);

                                    $rows.=wf_TableRow($cells, 'row3');
                                }
                                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
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
            debarr($userInfo);
        }
        return ($result);
    }

}

?>