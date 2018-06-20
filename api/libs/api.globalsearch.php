<?php

class GlobalSearch {

    protected $jsRuntime = '';
    protected $styles = '';
    protected $placeholder = '';
    protected $alterConf = array();
    protected $rawData = array();
    protected $fields = array();

    const CACHE_NAME = 'exports/globalsearchcache.dat';
    const EX_NO_SEARCHTYPE = 'SEARCHTYPE_NOT_DETECTED';

    public function __construct() {
        $this->loadAlter();
        $this->setPlaceholder();
        $this->setStyles();
        $this->setJsRuntime();
    }

    /**
     * Loads system alter config into protected prop
     * 
     * @global type $ubillingConfig
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->alterConf = $ubillingConfig->getAlter();
    }

    /**
     * Sets javascript runtime
     * 
     * @return void
     */
    protected function setJsRuntime() {
        $this->jsRuntime = wf_tag('script', false, '', 'type="text/javascript" language="javascript" src="modules/jsc/glsearch.js"');
        $this->jsRuntime.= wf_tag('script', true);
    }

    /**
     * Sets CSS input styling
     * 
     * @return void
     */
    protected function setStyles() {
        $this->styles = wf_tag('link', false, '', 'rel="stylesheet" href="skins/glsearch.css" type="text/css" media="screen""');
        $this->styles.= wf_tag('link', true);
    }

    /**
     * Sets input placeholder
     * 
     * @return void
     */
    protected function setPlaceholder() {
        $this->placeholder = ' value="' . __('User search') . '" onfocus="if(!this._haschanged){this.value=\'\'};this._haschanged=true;"';
    }

    /**
     * Renders search form
     * 
     * @return string
     */
    public function renderSearchInput() {
        $result = '';
        if ($this->alterConf['GLOBALSEARCH_ENABLED']) {
            $result.=$this->styles;
            $result.=$this->jsRuntime;
            $result.= wf_tag('input', false, '.ui-autocomplete', 'type="text" id="globalsearch" name="globalsearchquery"' . $this->placeholder);
            $result.= wf_tag('input', false, '', 'type="hidden" id="globalsearch_type" name="globalsearch_type" value="" ');
        } else {
            $result = wf_tag('input', false, '', 'type="text" name="partialaddr"' . $this->placeholder);
        }

        $result.='';
        return ($result);
    }

    /**
     * Prepares data array to json encoding
     * 
     * @param array $data data array to transform
     * @param string $category data category
     * @param string $type globalsearch type
     * @return array
     */
    protected function transformArray($data, $category, $type) {
        $result = array();
        if (!empty($data)) {
            foreach ($data as $io => $each) {
                if (!empty($each)) {
                    $result[zb_rand_string(8)] = array(
                        'label' => $each,
                        'lower' => strtolower_utf8($each),
                        'category' => $category,
                        'type' => $type
                    );
                }
            }
        }
        return ($result);
    }

    /**
     * Preloads raw data for searchable user fields and controls caching
     * 
     * @return void
     */
    protected function loadRawdata($forceCache = false) {
        $cacheTime = $this->alterConf['GLOBALSEARCH_CACHE'];
        $cacheTime = time() - ($cacheTime * 60); //in minutes
        //extracting user fields types to load
        if (!empty($this->alterConf['GLOBALSEARCH_FIELDS'])) {
            $this->fields = explode(',', $this->alterConf['GLOBALSEARCH_FIELDS']);
            $this->fields = array_flip($this->fields);
        }

        $updateCache = false;
        if (file_exists(self::CACHE_NAME)) {
            $updateCache = false;
            if ((filemtime(self::CACHE_NAME) > $cacheTime)) {
                $updateCache = false;
            } else {
                $updateCache = true;
            }
        } else {
            $updateCache = true;
        }

        //force cache parameter
        if ($forceCache) {
            $updateCache = true;
        }

        //updating rawdata cache
        if ($updateCache) {
            //loading needed fields
            if (isset($this->fields['realname'])) {
                $this->rawData = $this->rawData + $this->transformArray(zb_UserGetAllRealnames(), __('Real Name'), 'realname');
            }

            if (isset($this->fields['address'])) {
                $this->rawData = $this->rawData + $this->transformArray(zb_AddressGetFulladdresslist(), __('Full address'), 'address');
            }


            if (isset($this->fields['contract'])) {
                $allContracts = zb_UserGetAllContracts();
                $allContracts = array_flip($allContracts);
                $this->rawData = $this->rawData + $this->transformArray($allContracts, __('Contract'), 'contract');
            }

            if ((isset($this->fields['phone'])) OR ( isset($this->fields['mobile']))) {
                $allPhonedata = zb_UserGetAllPhoneData();
                if (isset($this->fields['phone'])) {
                    if (!empty($allPhonedata)) {
                        $allPhones = array();
                        foreach ($allPhonedata as $io => $each) {
                            $allPhones[$io] = $each['phone'];
                        }
                        $this->rawData = $this->rawData + $this->transformArray($allPhones, __('Phone'), 'phone');
                    }
                }

                if (isset($this->fields['mobile'])) {
                    if (!empty($allPhonedata)) {
                        $allMobiles = array();
                        foreach ($allPhonedata as $io => $each) {
                            $allMobiles[$io] = $each['mobile'];
                        }
                        $this->rawData = $this->rawData + $this->transformArray($allMobiles, __('Mobile'), 'mobile');
                    }
                }
            }

            if (isset($this->fields['ip'])) {
                $this->rawData = $this->rawData + $this->transformArray(zb_UserGetAllIPs(), __('IP'), 'ip');
            }

            if (isset($this->fields['mac'])) {
                $this->rawData = $this->rawData + $this->transformArray(zb_UserGetAllIpMACs(), __('MAC address'), 'mac');
            }


            if (isset($this->fields['login'])) {
                $allLogins = zb_UserGetAllStargazerLogins();
                $this->rawData = $this->rawData + $this->transformArray($allLogins, __('Login'), 'login');
            }

            if (isset($this->fields['seal'])) {
                $conDet = new ConnectionDetails();
                $allSeals = $conDet->getAllSeals();
                $this->rawData = $this->rawData + $this->transformArray($allSeals, __('Cable seal'), 'seal');
            }

            if (isset($this->fields['paymentid'])) {
                if ($this->alterConf['OPENPAYZ_REALID']) {
                    $allPayIds_q = "SELECT * from `op_customers`";
                    $allPayIds = simple_queryall($allPayIds_q);
                    $tmpArrPayids = array();
                    if (!empty($allPayIds)) {
                        foreach ($allPayIds as $io => $each) {
                            $tmpArrPayids[$each['realid']] = $each['virtualid'];
                        }
                    }
                    $this->rawData = $this->rawData + $this->transformArray($tmpArrPayids, __('Payment ID'), 'payid');
                } else {
                    $allPayIds_q = "SELECT `login`,`IP` from `users`";
                    $allPayIds = simple_queryall($allPayIds_q);
                    $tmpArrPayids = array();
                    if (!empty($allPayIds)) {
                        foreach ($allPayIds as $io => $each) {
                            $tmpArrPayids[$each['login']] = ip2int($each['IP']);
                        }
                    }
                    $this->rawData = $this->rawData + $this->transformArray($tmpArrPayids, __('Payment ID'), 'payid');
                }
            }


            file_put_contents(self::CACHE_NAME, serialize($this->rawData));
        } else {
            $this->rawData = file_get_contents(self::CACHE_NAME);
            $this->rawData = unserialize($this->rawData);
        }
    }

    /**
     * Returns json encoded data for input autocomplete
     * 
     * @return void
     */
    public function ajaxCallback($forceCache = false) {
        $this->loadRawdata($forceCache);
        $data = array();
        if (!empty($this->rawData)) {
            $term = (wf_CheckGet(array('term'))) ? strtolower_utf8($_GET['term']) : '';
            foreach ($this->rawData as $io => $each) {
                if ($term) {
                    if (ispos($each['lower'], $term)) {
                        $data[] = $each;
                    }
                } else {
                    $data[] = $each;
                }
            }
        }

        if (!$forceCache) {
            //output not needed 
            die(json_encode($data));
        }
    }

    /**
     * Detects searchtype by search query fragment
     * 
     * @param string $term
     * @return string
     */
    public function detectSearchType($term) {
        $result = '';
        $term = trim($term);
        if (!empty($term)) {
            $term = strtolower_utf8($term);
            $this->loadRawdata();
            if (!empty($this->rawData)) {
                foreach ($this->rawData as $io => $each) {
                    if (ispos($each['lower'], $term)) {
                        $result = $each['type'];
                        break;
                    }
                }
            }
        }
        return ($result);
    }

}

?>