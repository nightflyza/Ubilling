<?php

class OmegaTV {

    /**
     * HlsTV object placeholder for further usage 
     *
     * @var object
     */
    protected $hls = '';

    /**
     * Contains all of available omega tariffs as id=>data
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all tariff names as tariffid=>name
     *
     * @var array
     */
    protected $tariffNames = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains default channel icon size
     *
     * @var int
     */
    protected $chanIconSize = 32;

    /**
     * Basic module path
     */
    const URL_ME = '?module=omegatv';

    /**
     * Creates new OmegaTV instance
     */
    public function __construct() {
        $this->initHls();
        $this->initMessages();
        $this->loadTariffs();
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
     * Inits system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads existing tariffs from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `om_tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
                $this->tariffNames[$each['tariffid']] = $each['tariffname'];
            }
        }
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
    public function renderTariffsRemote($list, $withIds = true, $withChannels = true) {
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

//debarr($this->hls->getDeviceCode(1));

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
            $cells .= wf_TableCell(wf_Link($userInfo['web_url'], __('View online'), false, '', 'TARGET="_BLANK"'));
            $rows .= wf_TableRow($cells, 'row3');

            if (!empty($userInfo['devices'])) {
                foreach ($userInfo['devices'] as $io => $each) {
                    $cells = wf_TableCell(__('Device') . ' ' . $io, '', 'row2');
                    $cells .= wf_TableCell(implode(', ', $each));
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

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
        $result = '';
        $result .= wf_Link(self::URL_ME . '&subscriptions=true', wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&tariffs=true', wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&reports=true', wf_img('skins/ukv/report.png') . ' ' . __('Reports'), false, 'ubButton') . ' ';
        return($result);
    }

    /**
     * Returns array of available remote tariffs as tariffid=>name
     * 
     * @return array
     */
    protected function getTariffsRemote() {
        $result = array();
        $baseTariffs = $this->hls->getTariffsBase();
        $bundleTariffs = $this->hls->getTariffsBundle();
        $promoTariffs = $this->hls->getTariffsPromo();

        if (isset($baseTariffs['result'])) {
            foreach ($baseTariffs['result'] as $io => $each) {
                $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('base') . ')';
            }
        }

        if (isset($bundleTariffs['result'])) {
            foreach ($bundleTariffs['result'] as $io => $each) {
                $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('bundle') . ')';
            }
        }

        if (isset($promoTariffs['result'])) {
            if (isset($promoTariffs['result']['promo_limited'])) {
                foreach ($promoTariffs['result']['promo_limited'] as $io => $each) {
                    $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('promo limited') . ')';
                }
            }

            if (isset($promoTariffs['result']['promo'])) {
                foreach ($promoTariffs['result']['promo'] as $io => $each) {
                    $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('promo') . ')';
                }
            }
        }

        return($result);
    }

    /**
     * Renders tariff creation form
     * 
     * @return string
     */
    public function renderTariffCreateForm() {
        $result = '';
        $remoteTariffs = $this->getTariffsRemote();
        $tmpArr = array();
        if (!empty($remoteTariffs)) {
            foreach ($remoteTariffs as $io => $each) {
                $tmpArr[$io] = $io . ' - ' . $each;
            }
        }

        if (!empty($tmpArr)) {
            $tariffsTypes = array(
                'base' => __('Base'),
                'bundle' => __('Bundle'),
                'promo' => __('Promo')
            );

            $inputs = wf_Selector('newtariffid', $tmpArr, __('ID'), '', true);
            $inputs .= wf_TextInput('newtariffname', __('Tariff name'), '', true, 25);
            $inputs .= wf_Selector('newtarifftype', $tariffsTypes, __('Type'), '', true);
            $inputs .= wf_TextInput('newtarifffee', __('Fee'), '0', true, 3);
            $inputs .= wf_Submit(__('Create'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Creates new tariff in database
     * 
     * @return void
     */
    public function createTariff() {
        if (wf_CheckPost(array('newtariffid', 'newtariffname', 'newtarifftype'))) {
            $tariffid_f = vf($_POST['newtariffid'], 3);
            $name_f = mysql_real_escape_string($_POST['newtariffname']);
            $type_f = vf($_POST['newtarifftype']);
            $fee = $_POST['newtarifffee'];
            $fee_f = mysql_real_escape_string($fee);
            $query = "INSERT INTO `om_tariffs` (`id`,`tariffid`,`tariffname`,`type`,`fee`) VALUES ";
            $query .= "(NULL,'" . $tariffid_f . "','" . $name_f . "','" . $type_f . "','" . $fee_f . "');";
            nr_query($query);
            $newId = simple_get_lastid('om_tariffs');
            log_register('OMEGATV TARIFF CREATE [' . $tariffid_f . '] AS [' . $newId . '] TYPE `' . $type_f . '` FEE `' . $fee . '`');
        }
    }

    /**
     * Renders list of available tariffs
     * 
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Tariff') . ' ' . __('Code'));
            $cells .= wf_TableCell(__('Tariff name'));
            $cells .= wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['tariffid']);
                $cells .= wf_TableCell($each['tariffname']);
                $cells .= wf_TableCell($each['type']);
                $cells .= wf_TableCell($each['fee']);
                $actLinks = wf_JSAlert(self::URL_ME . '&tariffs=true&deleteid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Deletes some tariff from database
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteTariff($id) {
        $id = vf($id, 3);
        if (isset($this->allTariffs[$id])) {
            $query = "DELETE from `om_tariffs` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register('OMEGATV TARIFF DELETE [' . $id . ']');
        }
    }

    /**
     * Returns user login transformed to some numeric hash
     * 
     * @param string $login
     * 
     * @return int
     */
    public function generateCustormerId($login) {
        $result = '';
        if (!empty($login)) {
            $result = crc32($login);
        }
        return($result);
    }

}

?>
