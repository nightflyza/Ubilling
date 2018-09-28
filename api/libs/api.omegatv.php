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
     * Contains available user profiles as customerid=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains available users data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

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
     * Default user profile viewing URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Creates new OmegaTV instance
     */
    public function __construct() {
        $this->initHls();
        $this->initMessages();
        $this->loadTariffs();
        $this->loadUserData();
        $this->loadUserProfiles();
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
     * Loads existing users profiles
     * 
     * @return void
     */
    protected function loadUserProfiles() {
        $query = "SELECT * from `om_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['customerid']] = $each;
            }
        }
    }

    /**
     * Loads internet users data into protected property for further usage
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
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
     * Trys to render human-readable tariff name
     * 
     * @param int $tariffId
     * 
     * @return string
     */
    protected function getTariffName($tariffId) {
        $result = '';
        if (isset($this->tariffNames[$tariffId])) {
            $result.=$this->tariffNames[$tariffId];
        } else {
            $result.=$tariffId;
        }
        return ($result);
    }

    /**
     * Returns current user devices info as JSON
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getUserDevicesData($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            $userInfo = $this->hls->getUserInfo($customerId);
            if (isset($userInfo['result'])) {
                $userInfo = $userInfo['result'];
                if (isset($userInfo['devices'])) {
                    $result.=json_encode($userInfo['devices']);
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes some device from user
     * 
     * @param string $userLogin
     * @param string $uniq
     * 
     * @return void
     */
    public function deleteUserDevice($userLogin, $uniq) {
        $customerId = $this->getLocalCustomerId($userLogin);
        $uniq = trim($uniq);
        if (!empty($customerId)) {
            $userInfo = $this->hls->getUserInfo($customerId);
            if (isset($userInfo['result'])) {
                //checking for user device ownership
                $userInfo = $userInfo['result'];
                if (isset($userInfo['devices'])) {
                    if (!empty($userInfo['devices'])) {
                        foreach ($userInfo['devices'] as $io => $each) {
                            if ($each['uniq'] == $uniq) {
                                $this->deleteDevice($customerId, $uniq);
                                log_register('OMEGATV DEVICE DELETE `' . $uniq . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders some user profile info
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function renderUserInfo($customerId) {
        $customerId = vf($customerId, 3);
        $result = '';

        $userInfo = $this->hls->getUserInfo($customerId);
        $localUserInfo = $this->allUsers[$customerId];

        if (isset($userInfo['result'])) {
            $result.=wf_AjaxLoader();
            $userInfo = $userInfo['result'];

            $cells = wf_TableCell(__('Full address'), '', 'row2');
            $cells .= wf_TableCell(@$this->allUserData[$localUserInfo['login']]['fulladress']);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('ID'), '', 'row2');
            $cells .= wf_TableCell($userInfo['id']);
            $rows.= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Date'), '', 'row2');
            $cells .= wf_TableCell($localUserInfo['actdate']);
            $rows.= wf_TableRow($cells, 'row3');

            if (!empty($userInfo['tariff'])) {
                foreach ($userInfo['tariff'] as $io => $each) {
                    $cells = wf_TableCell(__('Tariffs') . ' ' . __($io), '', 'row2');
                    $tariffsList = '';
                    if (!empty($each)) {
                        foreach ($each as $ia => $tariffId) {
                            $tariffsList.=$this->getTariffName($tariffId) . ' ';
                        }
                    }
                    $cells .= wf_TableCell($tariffsList);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('Status'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($userInfo['status']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Preview'), '', 'row2');
            $cells .= wf_TableCell(wf_Link($userInfo['web_url'], __('View online'), false, '', 'TARGET="_BLANK"'));
            $rows .= wf_TableRow($cells, 'row3');

            if (!empty($userInfo['devices'])) {
                foreach ($userInfo['devices'] as $io => $each) {
                    $cells = wf_TableCell(__('Device') . ' ' . $io, '', 'row2');
                    $deviceLabel = __('Uniq') . ': ' . $each['uniq'] . ' ' . __('Date') . ': ' . date("Y-m-d H:i:s", $each['activation_data']) . ' ' . __('Model') . ': ' . $each['model'];
                    $deviceControls = wf_JSAlert(self::URL_ME . '&subscriptions=true&customerid=' . $customerId . '&deletedevice=' . $each['uniq'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells .= wf_TableCell($deviceLabel . ' ' . $deviceControls);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('Device activation'), '', 'row2');
            $getCodeLink = $this->ajDevCodeLink($customerId, __('Get code'));
            $cells .= wf_TableCell(wf_AjaxContainer('deviceactivationcodecontainer', '', $getCodeLink));
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0);
        }
        return ($result);
    }

    /**
     * Returns device activation code ajax link
     * 
     * @param int $customerId
     * @param string $label
     * 
     * @return string
     */
    protected function ajDevCodeLink($customerId, $label) {
        $result = wf_AjaxLink(self::URL_ME . '&subscriptions=true&getdevicecode=' . $customerId, $label, 'deviceactivationcodecontainer');
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
                $cells .= wf_TableCell(__($each['type']));
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

    /**
     * Returns web-player URL
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function generateWebURL($customerId) {
        $result = '';
        $userInfo = $this->hls->getUserInfo($customerId);

        if (isset($userInfo['result'])) {
            $userInfo = $userInfo['result'];
            if (!empty($userInfo)) {
                $result .=$userInfo['web_url'];
            }
        }
        return ($result);
    }

    /**
     * Returns web URL by some user login
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getWebUrlByLogin($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            //already existing user
            $result.=$this->generateWebURL($customerId);
        } else {
            //first usage
            $this->createUserProfile($userLogin);
            $customerId = $this->getLocalCustomerId($userLogin);
            $result.=$this->generateWebURL($customerId);
        }
        return ($result);
    }

    /**
     * Returns local customer ID from database
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    public function getLocalCustomerId($userLogin) {
        $result = '';
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if ($each['login'] == $userLogin) {
                    $result = $each['customerid'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns new device activation code
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function generateDeviceCode($customerId) {
        $result = '';
        $codeData = $this->hls->getDeviceCode($customerId);
        if (isset($codeData['result'])) {
            if (isset($codeData['result']['code'])) {
                $result.=$codeData['result']['code'] . ' ' . $this->ajDevCodeLink($customerId, __('Renew'));
            }
        }
        return ($result);
    }

    /**
     * Returns new device activation code by user login
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getDeviceCodeByLogin($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            $codeData = $this->hls->getDeviceCode($customerId);
            if (isset($codeData['result'])) {
                if (isset($codeData['result']['code'])) {
                    $result.=$codeData['result']['code'];
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes device assigned to some customerid
     * 
     * @param int $customerId
     * @param string $deviceId
     * 
     * @return void
     */
    public function deleteDevice($customerId, $deviceId) {
        $this->hls->deleteDevice($customerId, $deviceId);
    }

    /**
     * Creates new user profile
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function createUserProfile($userLogin) {
        $customerId = $this->generateCustormerId($userLogin);
        $login_f = mysql_real_escape_string($userLogin);
        $curdate = curdatetime();
        $query = "INSERT INTO `om_users` (`id`,`login`,`customerid`,`basetariffid`,`bundletariffs`,`active`,`actdate`) VALUES ";
        $query.="(NULL,'" . $login_f . "','" . $customerId . "',NULL,NULL,'1','" . $curdate . "');";
        nr_query($query);
        log_register('OMEGATV CUSTOMER REGISTER (' . $userLogin . ') AS [' . $customerId . ']');
    }

    /**
     * Renders available subscriptions container list with some controls
     * 
     * @return string
     */
    public function renderUserListContainer() {
        $result = '';
        $columns = array('ID', 'Full address', 'Real Name', 'Cash', 'Base tariff', 'Bundle tariffs', 'Date', 'Active', 'Actions');
        $result.=wf_JqDtLoader($columns, self::URL_ME . '&subscriptions=true&ajuserlist=true', false, __('Users'));
        return ($result);
    }

    /**
     * Renders JSON data for ajax user list container content
     * 
     * @return void
     */
    public function ajUserList() {
        $result = '';
        $json = new wf_JqDtHelper();
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                $userAddress = @$this->allUserData[$each['login']]['fulladress'];
                $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $userAddress);

                $data[] = $each['id'];
                $data[] = $userLink;
                $data[] = @$this->allUserData[$each['login']]['realname'];
                $data[] = @$this->allUserData[$each['login']]['Cash'];
                $data[] = $this->getTariffName($each['basetariffid']);
                $data[] = $each['bundletariffs'];
                $data[] = $each['actdate'];
                $data[] = web_bool_led($each['active'], true);
                $actLinks = wf_Link(self::URL_ME . '&customerprofile=' . $each['customerid'], web_edit_icon());
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

}

?>
