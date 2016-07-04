<?php

class UbillingWhois {

    /**
     * Current IP for lookup
     *
     * @var string
     */
    protected $ip = '';

    /**
     * Raw GEO data received by remote API
     *
     * @var string 
     */
    protected $geoDataRaw = '';

    /**
     * Raw ISP data received by remote API
     *
     * @var string
     */
    protected $ispDataRaw = '';

    /**
     * Preprocessed JSON geo data 
     *
     * @var object
     */
    protected $geoData = array();

    /**
     * Preprocessed JSON ISP data
     *
     * @var object
     */
    protected $ispData = array();

    /**
     * Some URLs for requesting of IP data
     */
    const URL_GEO = 'http://api.2ip.com.ua/geo.json?ip=';
    const URL_ISP = 'http://api.2ip.com.ua/provider.json?ip=';
    const URL_ASINFO = 'http://bgp.he.net/AS';

    /**
     * Creates new Whois object instance
     * 
     * @param string $ip
     * @throws Exception
     */
    public function __construct($ip) {
        if (!empty($ip)) {
            $this->setIp($ip);
        } else {
            throw new Exception("EX_EMPTY_IP");
        }

        $this->loadIpData();
        $this->preprocessLoadedData();
    }

    /**
     * Sets current IP
     * 
     * @param string $ip
     * 
     * @return void
     */
    protected function setIp($ip) {
        $this->ip = $ip;
    }

    /**
     * Requests geo data by remote API
     * 
     * @return void
     */
    protected function getGeoData() {
        $request = self::URL_GEO . $this->ip;
        $this->geoDataRaw = file_get_contents($request);
    }

    /**
     * Requests isp data by remote API
     * 
     * @return void
     */
    protected function getIspData() {
        $request = self::URL_ISP . $this->ip;
        $this->ispDataRaw = file_get_contents($request);
    }

    /**
     * Runs data loaders for current IP
     * 
     * @return void
     */
    protected function loadIpData() {
        $this->getGeoData();
        $this->getIspData();
    }

    /**
     * Do some preprocessing of raw data
     * 
     * @return void
     */
    protected function preprocessLoadedData() {
        if (!empty($this->geoDataRaw)) {
            $this->geoData = json_decode($this->geoDataRaw);
        }

        if (!empty($this->ispDataRaw)) {
            $this->ispData = json_decode($this->ispDataRaw);
        }
    }

    /**
     * Use for debug only
     * 
     * @return void
     */
    public function dumpData() {
        debarr($this->ispData);
        debarr($this->geoData);
    }

    /**
     * Renders minimap if long/lat is present
     * 
     * @global object $ubillingConfig
     * 
     * @return string
     */
    protected function renderMinimap() {
        $result = '';
        if ((!empty($this->geoData->latitude)) AND ( !empty($this->geoData->longitude))) {
            global $ubillingConfig;
            $ymconf = $ubillingConfig->getYmaps();


            $result = wf_tag('div', false, '', 'id="swmap" style="width: 100%; height:300px;"');
            $result.=wf_tag('div', true);
            $placemarks = sm_MapAddMark($this->geoData->latitude . ',' . $this->geoData->longitude);
            sm_MapInit($this->geoData->latitude . ',' . $this->geoData->longitude, 8, $ymconf['TYPE'], $placemarks, '', $ymconf['LANG']);
        }
        return ($result);
    }

    /**
     * Renders IP ISP/Geo data in human readable view
     * 
     * @return string
     */
    public function renderData() {
        $result = '';
        $rows = '';
        $miniMap = '';

        if (!empty($this->ispData)) {
            $siteLink = (!empty($this->ispData->site)) ? wf_Link($this->ispData->site, $this->ispData->site) : '';
            $asLink = (!empty($this->ispData->as)) ? wf_Link(self::URL_ASINFO . $this->ispData->as, $this->ispData->as) : '';

            $cells = wf_TableCell(__('IP'), '', 'row2');
            $cells.= wf_TableCell($this->ispData->ip);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('RIPE name'), '', 'row2');
            $cells.= wf_TableCell(@$this->ispData->name_ripe);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('ISP name'), '', 'row2');
            $cells.= wf_TableCell(@$this->ispData->name_rus);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('ISP site'), '', 'row2');
            $cells.= wf_TableCell($siteLink);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('AS'), '', 'row2');
            $cells.= wf_TableCell($asLink);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Prefix'), '', 'row2');
            $prefix = (!empty($this->ispData->route)) ? $this->ispData->route . '/' . $this->ispData->mask : '';
            $cells.= wf_TableCell($prefix);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('IP range'), '', 'row2');
            if ((!empty($this->ispData->ip_range_start)) AND ( !empty($this->ispData->ip_range_end))) {
                $ipRange = int2ip($this->ispData->ip_range_start) . ' - ' . int2ip($this->ispData->ip_range_end);
            } else {
                $ipRange = '';
            }
            $cells.= wf_TableCell($ipRange);
            $rows.=wf_TableRow($cells, 'row3');
        }

        if (!empty($this->geoData)) {
            $cells = wf_TableCell(__('Country'), '', 'row2');
            $cells.= wf_TableCell($this->geoData->country);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Region'), '', 'row2');
            $cells.= wf_TableCell($this->geoData->region);
            $rows.=wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('City'), '', 'row2');
            $cells.= wf_TableCell($this->geoData->city);
            $rows.=wf_TableRow($cells, 'row3');
            $miniMap = $this->renderMinimap();
        }

        if (!empty($rows)) {
            $result = wf_TableBody($rows, '100%', 0, '');
            $result.=$miniMap;
        }

        return ($result);
    }

}

?>