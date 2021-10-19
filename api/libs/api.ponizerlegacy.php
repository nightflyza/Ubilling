<?php

/**
 * Ponizer legacy all-in-one-page renderer
 */
class PONizerLegacy extends PONizer {

    protected $json = '';

    public function __construct() {
        parent::__construct();
        $this->json = new wf_JqDtHelper();
    }

    /**
     * Renders json formatted data for jquery data tables list
     *
     * @return void
     */
    public function ajaxOnuData($OltId = '') {

        foreach ($this->allOltDevices as $OltId => $eachOltData) {
            $OnuByOLT = $this->getOnuArrayByOltID($OltId);

            $allRealnames = zb_UserGetAllRealnames();
            $allAddress = zb_AddressGetFulladdresslistCached();
            $allTariffs = zb_TariffsGetAllUsers();

            if ($this->altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('PONONU');
                $adc = true;
            } else {
                $adc = false;
            }

            $this->loadSignalsCache();

            $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
            if (!empty($distCacheAvail)) {
                $distCacheAvail = true;
                $this->loadDistanceCache();
            } else {
                $distCacheAvail = false;
            }

            $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
            if (!empty($intCacheAvail)) {
                $intCacheAvail = true;
                $this->loadInterfaceCache();
            } else {
                $intCacheAvail = false;
            }

            $lastDeregCacheAvail = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);
            if (!empty($lastDeregCacheAvail)) {
                $lastDeregCacheAvail = true;
                $this->loadLastDeregCache();
            } else {
                $lastDeregCacheAvail = false;
            }

            if (!empty($OnuByOLT)) {
                foreach ($OnuByOLT as $io => $each) {
                    $userTariff = '';
                    $ONUIsOffline = false;

                    if (!empty($each['login'])) {
                        $userLogin = trim($each['login']);
                        $userLink = wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin], false);
                        @$userRealName = $allRealnames[$userLogin];

//tariff data
                        if (isset($allTariffs[$userLogin])) {
                            $userTariff = $allTariffs[$userLogin];
                        }
                    } else {
                        $userLink = '';
                        $userRealName = '';
                    }
//checking adcomments availability
                    if ($adc) {
                        $indicatorIcon = $adcomments->getCommentsIndicator($each['id']);
                    } else {
                        $indicatorIcon = '';
                    }

                    $actLinks = wf_Link('?module=ponizer&editonu=' . $each['id'], web_edit_icon(), false);

                    $actLinks .= ' ' . $indicatorIcon;


//coloring signal
                    if (isset($this->signalCache[$each['mac']])) {
                        $signal = $this->signalCache[$each['mac']];
                        if (($signal > 0) OR ( $signal < -25)) {
                            $sigColor = self::COLOR_BAD;
                        } else {
                            $sigColor = self::COLOR_OK;
                        }

                        if ($signal == self::NO_SIGNAL) {
                            $ONUIsOffline = true;
                            $signal = __('No');
                            $sigColor = self::COLOR_NOSIG;
                        }
                    } elseif (isset($this->signalCache[$each['serial']])) {
                        $signal = $this->signalCache[$each['serial']];
                        if (($signal > 0) OR ( $signal < -25)) {
                            $sigColor = self::COLOR_BAD;
                        } else {
                            $sigColor = self::COLOR_OK;
                        }

                        if ($signal == self::NO_SIGNAL) {
                            $ONUIsOffline = true;
                            $signal = __('No');
                            $sigColor = self::COLOR_NOSIG;
                        }
                    } else {
                        $ONUIsOffline = true;
                        $signal = __('No');
                        $sigColor = self::COLOR_NOSIG;
                    }

                    $data[] = $each['id'];
                    if ($this->altCfg['PONIZER_LEGACY_VIEW'] == 2) {
                        $data[] = $this->allOltNames[$each['oltid']];
                    }
                    if ($intCacheAvail) {
                        $data[] = @$this->interfaceCache[$each['mac']];
                    }
                    $data[] = $this->getModelName($each['onumodelid']);
                    $data[] = $each['ip'];
                    $data[] = $each['mac'];
                    $data[] = wf_tag('font', false, '', 'color=' . $sigColor . '') . $signal . wf_tag('font', true);

                    if ($distCacheAvail) {
                        if (isset($this->distanceCache[$each['mac']])) {
                            $data[] = @$this->distanceCache[$each['mac']];
                        } else {
                            $data[] = @$this->distanceCache[$each['serial']];
                        }
                    }

                    if ($lastDeregCacheAvail) {
                        if ($ONUIsOffline) {
                            $data[] = @$this->lastDeregCache[$each['mac']];
                        } else {
                            $data[] = '';
                        }
                    }

                    $data[] = $userLink;
                    $data[] = $userRealName;
                    $data[] = $userTariff;
                    $data[] = $actLinks;

                    $this->json->addRow($data);
                    unset($data);
                }
            }
        }
        $this->json->getJson();
    }

    /**
     * Renders available ONU JQDT list container
     *
     * @return string
     */
    public function renderOnuList() {
        $distCacheAvail = rcms_scandir(self::DISTCACHE_PATH, '*_' . self::DISTCACHE_EXT);
        $intCacheAvail = rcms_scandir(self::INTCACHE_PATH, '*_' . self::INTCACHE_EXT);
        $lastDeregCacheAvail = rcms_scandir(self::DEREGCACHE_PATH, '*_' . self::DEREGCACHE_EXT);

        $distCacheAvail = !empty($distCacheAvail) ? true : false;
        $intCacheAvail = !empty($intCacheAvail) ? true : false;
        $lastDeregCacheAvail = !empty($lastDeregCacheAvail) ? true : false;
        $oltOnuCounters = $this->getOltOnuCounts();

        $columns = array('ID');
        if (@$this->altCfg['PONIZER_LEGACY_VIEW'] == 2) {
            $columns[] = __('OLT');
        }

        if ($intCacheAvail) {
            $columns[] = __('Interface');
        }

        $columns[] = 'Model';
        if (@$this->altCfg['PON_ONUIPASIF']) {
            $columns[] = 'Interface';
        } else {
            $columns[] = 'IP';
        }
        $columns[] = 'MAC';
        $columns[] = 'Signal';

        if ($distCacheAvail) {
            $columns[] = __('Distance') . ' (' . __('m') . ')';
        }

        if ($lastDeregCacheAvail) {
            $columns[] = __('Last dereg reason');
        }

        $columns[] = 'Address';
        $columns[] = 'Real Name';
        $columns[] = 'Tariff';
        $columns[] = 'Actions';

        $opts = '"order": [[ 0, "desc" ]]';

        $result = '';

        $AjaxURLStr = '' . self::URL_ME . '&ajaxonu=true&legacyView=true';

        $result .= show_window('', wf_JqDtLoader($columns, $AjaxURLStr, false, 'ONU', 100, $opts));
        return ($result);
    }

}
