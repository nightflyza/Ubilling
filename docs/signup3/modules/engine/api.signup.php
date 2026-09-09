<?php

/**
 * Signup request service
 */
class SignupService {

    /**
     * Local cache directory
     */
    const CACHE_PATH = 'cache/';

    /**
     * API HTTP timeout seconds
     */
    const API_TIMEOUT = 15;

    /**
     * Burst window in seconds
     */
    const BURST_WINDOW = 60;

    /**
     * Remote API URL from ini
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Remote API key from ini
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Local cache TTL
     *
     * @var int
     */
    protected $cacheTimeout = 3600;

    /**
     * Max create requests per BURST_WINDOW (0 disables)
     *
     * @var int
     */
    protected $burstLimit = 60;

    /**
     * Optional salt for daily JS-proof token
     *
     * @var string
     */
    protected $tokenSalt = '';

    /**
     * Remote payload
     *
     * @var array
     */
    protected $payload = array();

    /**
     * Last create error message
     *
     * @var string
     */
    protected $lastError = '';

    /**
     * Required POST fields
     *
     * @var array
     */
    protected $required = array('snln', 'build', 'realname', 'phone');

    /**
     * Honeypot field names
     *
     * @var array
     */
    protected $spamTraps = array('surname', 'lastname', 'seenoevil', 'actualmobile', 'crnwp', 'nqwp');

    /**
     * Creates signup3 service instance
     *
     * @return void
     */
    public function __construct() {
        global $snConfig;
        if (isset($snConfig['API_URL'])) {
            $this->apiUrl = $snConfig['API_URL'];
        }
        if (isset($snConfig['API_KEY'])) {
            $this->apiKey = $snConfig['API_KEY'];
        }
        if (isset($snConfig['cachetimeout'])) {
            $this->cacheTimeout = $snConfig['cachetimeout'];
        }
        if (isset($snConfig['burstlimit'])) {
            $this->burstLimit = intval($snConfig['burstlimit']);
        }
        if (isset($snConfig['TOKEN_SALT'])) {
            $this->tokenSalt = $snConfig['TOKEN_SALT'];
        }
        $this->assertCacheWritable();
        $this->debugRequestStart();
        $this->payload = $this->loadRemoteConfig();
        $this->setTemplateData();
    }

    /**
     * One-line dump for debug.log
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function debugDump($data) {
        $result = '';
        if (is_array($data) or is_object($data)) {
            $encoded = json_encode($data);
            if ($encoded === false) {
                $result = print_r($data, true);
            } else {
                $result = $encoded;
            }
        } else {
            $result = strval($data);
        }
        $result = str_replace(array("\r", "\n"), ' ', $result);
        if (strlen($result) > 4000) {
            $result = substr($result, 0, 4000) . '...';
        }
        return ($result);
    }

    /**
     * Logs request context at the beginning of a page hit
     *
     * @return void
     */
    protected function debugRequestStart() {
        $method = '';
        $uri = '';
        $ip = '';
        $ua = '';
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        sn_DebugLog('--- request start ---');
        sn_DebugLog('http method=' . $method . ' uri=' . $uri . ' ip=' . $ip);
        sn_DebugLog('ua=' . $ua);
        if (!empty($_GET)) {
            sn_DebugLog('GET ' . $this->debugDump($_GET));
        }
        if (!empty($_POST)) {
            sn_DebugLog('POST ' . $this->debugDump($_POST));
        } else {
            sn_DebugLog('POST empty');
        }
    }

    /**
     * Stops the site if cache/ cannot be used
     *
     * @return void
     */
    protected function assertCacheWritable() {
        $path = self::CACHE_PATH;
        $ok = false;
        if (is_dir($path)) {
            if (is_writable($path)) {
                $ok = true;
            }
        }
        if (!$ok) {
            die('Fatal error: cache directory is not writable - fix directory permissions');
        }
    }

    /**
     * Global create burst: true if another signup may call RemoteAPI
     *
     * @return bool
     */
    protected function burstAllow() {
        $result = false;
        if ($this->burstLimit <= 0) {
            $result = true;
            sn_DebugLog('burst disabled burstlimit=0 allow=1');
        } else {
            $path = self::CACHE_PATH . 'burst.dat';
            $now = time();
            $fh = fopen($path, 'c+');
            if ($fh) {
                if (flock($fh, LOCK_EX)) {
                    $start = $now;
                    $count = 0;
                    $raw = stream_get_contents($fh);
                    if ($raw != '') {
                        $parts = explode(':', trim($raw));
                        if (sizeof($parts) == 2) {
                            $start = intval($parts[0]);
                            $count = intval($parts[1]);
                        }
                    }
                    if (($start + self::BURST_WINDOW) <= $now) {
                        sn_DebugLog('burst window reset oldstart=' . $start . ' oldcount=' . $count);
                        $start = $now;
                        $count = 0;
                    }
                    if ($count < $this->burstLimit) {
                        $count++;
                        $result = true;
                    }
                    sn_DebugLog('burst start=' . $start . ' count=' . $count . '/' . $this->burstLimit . ' allow=' . intval($result));
                    ftruncate($fh, 0);
                    rewind($fh);
                    fwrite($fh, $start . ':' . $count);
                    fflush($fh);
                    flock($fh, LOCK_UN);
                } else {
                    sn_DebugLog('burst flock failed');
                }
                fclose($fh);
            } else {
                sn_DebugLog('burst fopen failed path=' . $path);
            }
        }
        return ($result);
    }

    /**
     * Returns last create error
     *
     * @return string
     */
    public function getLastError() {
        $result = $this->lastError;
        return ($result);
    }

    /**
     * Empty payload used when remote config is unavailable
     *
     * @return array
     */
    protected function emptyPayload() {
        $result = array(
            'error' => true,
            'config' => array(
                'CITY_DISPLAY' => false,
                'CITY_SELECTABLE' => false,
                'STREET_SELECTABLE' => false,
                'EMAIL_DISPLAY' => false,
                'SPAM_TRAPS' => false,
                'NAME_DISPLAY' => true,
                'NOTES_DISPLAY' => true,
                'CACHING' => false,
                'ISP_NAME' => '',
                'ISP_URL' => '',
                'ISP_LOGO' => '',
                'SIDEBAR_TEXT' => '',
                'GREETING_TEXT' => '',
                'SERVICES' => array(),
                'TARIFFS' => array()
            ),
            'cities' => array(),
            'streets' => array()
        );
        return ($result);
    }

    /**
     * Reads a boolean flag from remote config
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool
     */
    protected function cfgFlag($key, $default = false) {
        $result = $default;
        if (isset($this->payload['config'][$key])) {
            $result = false;
            if ($this->payload['config'][$key]) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Reads a string from remote config
     *
     * @param string $key
     *
     * @return string
     */
    protected function cfgString($key) {
        $result = '';
        if (isset($this->payload['config'][$key])) {
            if (!is_array($this->payload['config'][$key])) {
                $result = $this->payload['config'][$key];
            }
        }
        return ($result);
    }

    /**
     * Reads a list from remote config or payload root
     *
     * @param string $key
     * @param bool $fromRoot
     *
     * @return array
     */
    protected function cfgList($key, $fromRoot = false) {
        $result = array();
        $source = array();
        if ($fromRoot) {
            if (isset($this->payload[$key])) {
                $source = $this->payload[$key];
            }
        } else {
            if (isset($this->payload['config'][$key])) {
                $source = $this->payload['config'][$key];
            }
        }
        if (!empty($source) and is_array($source)) {
            foreach ($source as $io => $each) {
                if (!is_array($each) and !is_object($each)) {
                    $item = trim($each);
                    if ($item != '') {
                        $result[$item] = $item;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Builds remoteapi URL for an operation
     *
     * @param string $param
     *
     * @return string
     */
    protected function apiEndpoint($param) {
        $result = rtrim($this->apiUrl, '/') . '/?module=remoteapi&key=' . urlencode($this->apiKey) . '&action=sigreq&param=' . urlencode($param);
        return ($result);
    }

    /**
     * Performs remoteapi JSON call
     *
     * @param string $param
     * @param string $rawBody
     *
     * @return array
     */
    protected function apiRequest($param, $rawBody = '') {
        $result = array();
        $safeUrl = rtrim($this->apiUrl, '/') . '/?module=remoteapi&key=***&action=sigreq&param=' . $param;
        sn_DebugLog('apiRequest start param=' . $param . ' url=' . $safeUrl);
        if ($rawBody != '') {
            sn_DebugLog('apiRequest body=' . $this->debugDump($rawBody));
        }
        if ((!empty($this->apiUrl)) and (!empty($this->apiKey))) {
            $omae = new OmaeUrl($this->apiEndpoint($param));
            $omae->setTimeout(self::API_TIMEOUT);
            $omae->setOpt(CURLOPT_TIMEOUT, self::API_TIMEOUT);
            if ($rawBody != '') {
                $omae->dataHeader('Content-Type', 'application/json');
                $omae->dataPostRaw($rawBody);
            }
            $response = $omae->response();
            $curlError = $omae->error();
            sn_DebugLog('apiRequest http=' . $omae->httpCode() . ' bytes=' . strlen($response));
            if (!empty($curlError)) {
                sn_DebugLog('apiRequest curl ' . $this->debugDump($curlError));
            }
            if (!$omae->error()) {
                if (!empty($response)) {
                    sn_DebugLog('apiRequest raw=' . $this->debugDump($response));
                    $decoded = json_decode($response, true);
                    if (is_array($decoded)) {
                        $result = $decoded;
                        sn_DebugLog('apiRequest json=' . $this->debugDump($decoded));
                    } else {
                        sn_DebugLog('apiRequest json_decode failed');
                    }
                } else {
                    sn_DebugLog('apiRequest empty response');
                }
            } else {
                sn_DebugLog('apiRequest aborted due to curl error');
            }
        } else {
            sn_DebugLog('apiRequest skipped empty API_URL or API_KEY');
        }
        return ($result);
    }

    /**
     * Loads public form payload from cache or remoteapi
     *
     * @return array
     */
    protected function loadRemoteConfig() {
        $result = $this->emptyPayload();
        $cacheName = self::CACHE_PATH . 'config.dat';
        $useCache = false;

        if (file_exists($cacheName)) {
            $age = time() - filemtime($cacheName);
            sn_DebugLog('config cache exists age=' . $age . 's ttl=' . $this->cacheTimeout);
            if ((filemtime($cacheName) + $this->cacheTimeout) > time()) {
                $useCache = true;
            } else {
                sn_DebugLog('config cache expired, unlink');
                @unlink($cacheName);
            }
        } else {
            sn_DebugLog('config cache missing');
        }

        if ($useCache) {
            $rawData = file_get_contents($cacheName);
            if (!empty($rawData)) {
                $decoded = json_decode($rawData, true);
                if (is_array($decoded) and isset($decoded['config'])) {
                    $result = $decoded;
                    sn_DebugLog('config loaded from cache CACHING=' . intval($this->payloadCachingFlag($decoded)) . ' cities=' . $this->debugCount($decoded, 'cities') . ' streets=' . $this->debugCount($decoded, 'streets'));
                } else {
                    $useCache = false;
                    sn_DebugLog('config cache JSON invalid');
                }
            } else {
                $useCache = false;
                sn_DebugLog('config cache empty file');
            }
        }

        if (!$useCache) {
            sn_DebugLog('config fetching remoteapi');
            $remote = $this->apiRequest('config');
            if (!empty($remote) and isset($remote['config'])) {
                $result = $remote;
                $writeCache = false;
                if (isset($remote['config']['CACHING'])) {
                    if ($remote['config']['CACHING']) {
                        $writeCache = true;
                    }
                }
                sn_DebugLog('config remote ok CACHING=' . intval($writeCache) . ' cities=' . $this->debugCount($remote, 'cities') . ' streets=' . $this->debugCount($remote, 'streets'));
                if ($writeCache) {
                    @file_put_contents($cacheName, json_encode($remote));
                    sn_DebugLog('config cache written');
                }
            } else {
                sn_DebugLog('config remote failed, using empty payload');
            }
        }

        return ($result);
    }

    /**
     * CACHING flag from a payload
     *
     * @param array $payload
     *
     * @return bool
     */
    protected function payloadCachingFlag($payload) {
        $result = false;
        if (isset($payload['config']['CACHING'])) {
            if ($payload['config']['CACHING']) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Count of a root list in payload
     *
     * @param array $payload
     * @param string $key
     *
     * @return int
     */
    protected function debugCount($payload, $key) {
        $result = 0;
        if (isset($payload[$key])) {
            if (is_array($payload[$key])) {
                $result = sizeof($payload[$key]);
            }
        }
        return ($result);
    }

    /**
     * Fills template branding from remote config
     *
     * @return void
     */
    protected function setTemplateData() {
        global $templateData;
        $ispName = $this->cfgString('ISP_NAME');
        $ispUrl = $this->cfgString('ISP_URL');
        $ispLogo = $this->cfgString('ISP_LOGO');
        $safeName = htmlspecialchars($ispName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($ispUrl, ENT_QUOTES, 'UTF-8');
        $safeLogo = htmlspecialchars($ispLogo, ENT_QUOTES, 'UTF-8');

        $templateData['ISP_NAME'] = $safeName;
        $templateData['ISP_URL'] = $safeUrl;
        $templateData['ISP_LOGO'] = $safeLogo;
        $templateData['SIDEBAR_TEXT'] = $this->cfgString('SIDEBAR_TEXT');
        $templateData['GREETING_TEXT'] = $this->cfgString('GREETING_TEXT');
        $templateData['ISP_LINK'] = '';
        if ((!empty($ispLogo)) and (!empty($ispUrl))) {
            $templateData['ISP_LINK'] = '<a class="sn-logo" href="' . $safeUrl . '">' . wf_img($safeLogo, $ispName) . '</a>';
        } else {
            if (!empty($ispLogo)) {
                $templateData['ISP_LINK'] = '<span class="sn-logo">' . wf_img($safeLogo, $ispName) . '</span>';
            } else {
                if ((!empty($ispUrl)) and (!empty($ispName))) {
                    $templateData['ISP_LINK'] = '<a class="sn-logo" href="' . $safeUrl . '">' . $safeName . '</a>';
                }
            }
        }
    }

    /**
     * Wraps control into a labeled field
     *
     * @param string $label
     * @param string $control
     * @param bool $required
     * @param string $extraClass
     *
     * @return string
     */
    protected function fieldWrap($label, $control, $required = false, $extraClass = '') {
        $star = '';
        $wrapClass = 'sn-field';
        if ($required) {
            $star = ' ' . wf_tag('span', false, 'sn-req') . '*' . wf_tag('span', true);
        }
        if ($extraClass != '') {
            $wrapClass .= ' ' . $extraClass;
        }
        $result = wf_tag('div', false, $wrapClass);
        if ($label != '') {
            $result .= wf_tag('div', false, 'sn-label') . $label . $star . wf_tag('div', true);
        }
        $result .= $control;
        $result .= wf_tag('div', true);
        return ($result);
    }

    /**
     * Builds name=>name selector params
     *
     * @param array $items
     * @param bool $withPlaceholder
     *
     * @return array
     */
    protected function selectorParams($items, $withPlaceholder = false) {
        $result = array();
        if ($withPlaceholder) {
            $result[''] = __('Select one');
        }
        if (!empty($items)) {
            foreach ($items as $io => $each) {
                $safe = htmlspecialchars($each, ENT_QUOTES, 'UTF-8');
                $result[$safe] = $safe;
            }
        }
        return ($result);
    }

    /**
     * First option value of selector params
     *
     * @param array $params
     *
     * @return string
     */
    protected function firstSelectorValue($params) {
        $result = '';
        if (!empty($params)) {
            reset($params);
            $result = key($params);
        }
        return ($result);
    }

    /**
     * City input. Form name is snplc  
     *
     * @return string
     */
    protected function cityInput() {
        $result = '';
        if ($this->cfgFlag('CITY_DISPLAY')) {
            $cities = $this->cfgList('cities', true);
            if ($this->cfgFlag('CITY_SELECTABLE') and !empty($cities)) {
                $control = wf_SelectorSearchable('snplc', $this->selectorParams($cities, true), '', '', false, ' autocomplete="off"');
            } else {
                $control = wf_TextInput('snplc', '', '', false, '', '', 'sn-control', '', 'autocomplete="off"');
            }
            $result = $this->fieldWrap(__('Town'), $control, true);
        }
        return ($result);
    }

    /**
     * Street input. Form name is snln  
     *
     * @return string
     */
    protected function streetInput() {
        $streets = $this->cfgList('streets', true);
        if ($this->cfgFlag('STREET_SELECTABLE') and !empty($streets)) {
            $control = wf_SelectorSearchable('snln', $this->selectorParams($streets, true), '', '', false, ' autocomplete="off"');
        } else {
            $control = wf_TextInput('snln', '', '', false, '', '', 'sn-control', '', 'autocomplete="off"');
        }
        $result = $this->fieldWrap(__('Street'), $control, true);
        return ($result);
    }

    /**
     * Honeypot fields
     *
     * @return string
     */
    protected function spamTrapsInput() {
        $result = '';
        if ($this->cfgFlag('SPAM_TRAPS')) {
            $result .= wf_tag('div', false, 'sn-hp', 'aria-hidden="true"');
            $result .= wf_TextInput('surname', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="new-password"');
            $result .= wf_TextInput('lastname', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="new-password"');
            $result .= wf_TextInput('seenoevil', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="new-password"');
            $result .= wf_TextInput('actualmobile', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="new-password"');
            $result .= wf_tag('div', true);
            $result .= wf_tag('div', false, 'sn-hp-dn', 'aria-hidden="true"');
            $result .= wf_TextInput('crnwp', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="new-password"');
            $result .= wf_tag('div', true);
            $result .= wf_TextInput('nqwp', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="new-password" style="display:none" aria-hidden="true"');
            $result .= wf_HiddenInput('unicrnpwr', '', 'unicrnpwr');
        }
        return ($result);
    }

    /**
     * Daily JS-proof token: md5(Y-m-d) or md5(Y-m-d + TOKEN_SALT)
     *
     * @param string $day
     *
     * @return string
     */
    protected function jsProofToken($day) {
        $raw = $day;
        if ($this->tokenSalt != '') {
            $raw .= $this->tokenSalt;
        }
        $result = md5($raw);
        return ($result);
    }

    /**
     * True when posted unicrnpwr matches today's or yesterday's token
     *
     * @return bool
     */
    protected function jsProofValid() {
        $result = false;
        $posted = $this->filterPost('unicrnpwr', 'raw');
        $today = $this->jsProofToken(date('Y-m-d'));
        $yesterday = $this->jsProofToken(date('Y-m-d', (time() - 86400)));
        if ($posted === $today) {
            $result = true;
        } else {
            if ($posted === $yesterday) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Fills unicrnpwr on form submit
     *
     * @return string
     */
    protected function jsProofScript() {
        $result = '';
        if ($this->cfgFlag('SPAM_TRAPS')) {
            $token = $this->jsProofToken(date('Y-m-d'));
            $result .= wf_tag('script', false, '', 'type="text/javascript"');
            $result .= '(function(){var t="' . $token . '";jQuery(function(){jQuery("#signup_form form").submit(function(){jQuery("#unicrnpwr").val(t);});});})();';
            $result .= wf_tag('script', true);
        }
        return ($result);
    }

    /**
     * Renders public signup form
     *
     * @return string
     */
    public function renderForm() {
        sn_DebugLog('renderForm SPAM_TRAPS=' . intval($this->cfgFlag('SPAM_TRAPS')) . ' NAME_DISPLAY=' . intval($this->cfgFlag('NAME_DISPLAY', true)) . ' EMAIL_DISPLAY=' . intval($this->cfgFlag('EMAIL_DISPLAY')) . ' NOTES_DISPLAY=' . intval($this->cfgFlag('NOTES_DISPLAY', true)) . ' CITY_DISPLAY=' . intval($this->cfgFlag('CITY_DISPLAY')));
        $inputs = wf_HiddenInput('createrequest', 'true');
        $greeting = $this->cfgString('GREETING_TEXT');
        if ($greeting != '') {
            $inputs .= wf_tag('div', false, 'sn-greeting') . $greeting . wf_tag('div', true);
        }

        $inputs .= $this->cityInput();
        $inputs .= $this->streetInput();

        $build = wf_TextInput('build', '', '', false, '', '', 'sn-control', '', 'autocomplete="off"');
        $apt = wf_TextInput('apt', '', '', false, '', '', 'sn-control', '', 'inputmode="numeric"');
        $inputs .= wf_tag('div', false, 'sn-row');
        $inputs .= $this->fieldWrap(__('Build'), $build, true, 'sn-col');
        $inputs .= $this->fieldWrap(__('Apartment'), $apt, false, 'sn-col');
        $inputs .= wf_tag('div', true);

        $inputs .= $this->spamTrapsInput();

        if ($this->cfgFlag('NAME_DISPLAY', true)) {
            $realname = wf_TextInput('realname', '', '', false, '', '', 'sn-control', '', 'autocomplete="name"');
            $inputs .= $this->fieldWrap(__('Your name'), $realname, true);
        }

        $phone = wf_TextInput('phone', '', '', false, '', 'mobile', 'sn-control', '', 'inputmode="tel" autocomplete="tel"');
        $inputs .= $this->fieldWrap(__('Phone'), $phone, true);

        if ($this->cfgFlag('EMAIL_DISPLAY')) {
            $email = wf_TextInput('email', '', '', false, '', 'email', 'sn-control', '', 'inputmode="email" autocomplete="email"');
            $inputs .= $this->fieldWrap(__('Email'), $email, false);
        }

        $services = $this->cfgList('SERVICES');
        if (!empty($services)) {
            $serviceParams = $this->selectorParams($services);
            $serviceControl = wf_SelectorSearchable('service', $serviceParams, '', $this->firstSelectorValue($serviceParams), false);
            $inputs .= $this->fieldWrap(__('Service'), $serviceControl, false);
        }

        $tariffs = $this->cfgList('TARIFFS');
        if (!empty($tariffs)) {
            $tariffParams = $this->selectorParams($tariffs);
            $tariffControl = wf_SelectorSearchable('tariff', $tariffParams, '', $this->firstSelectorValue($tariffParams), false);
            $inputs .= $this->fieldWrap(__('Tariff'), $tariffControl, false);
        }

        if ($this->cfgFlag('NOTES_DISPLAY', true)) {
            $notes = wf_TextArea('notes', '', '', false, '40x4', 'sn-notes');
            $inputs .= $this->fieldWrap(__('Notes'), $notes, false);
        }

        $inputs .= wf_tag('p', false, 'sn-hint') . sn_RequiredHint() . wf_tag('p', true);
        $inputs .= wf_Submit('Send signup request', '', 'class="sn-submit"');

        $result = wf_tag('div', false, '', 'id="signup_form"');
        $result .= wf_Form('', 'POST', $inputs, 'sn-form');
        $result .= $this->jsProofScript();
        $result .= wf_tag('div', true);
        return ($result);
    }

    /**
     * Filters a POST field before sending to API
     *
     * @param string $name
     * @param string $mode
     *
     * @return string
     */
    protected function filterPost($name, $mode = 'safe') {
        $result = '';
        if (ubRouting::checkPost($name, false)) {
            $result = ubRouting::post($name, $mode);
            $result = trim($result);
        }
        return ($result);
    }

    /**
     * Sends signup request via remoteapi
     *
     * @return bool
     */
    public function createRequest() {
        $result = false;
        $this->lastError = '';
        sn_DebugLog('createRequest start');

        $trapHit = '';
        foreach ($this->spamTraps as $io => $each) {
            $trapVal = '';
            if (isset($_POST[$each])) {
                $trapVal = $_POST[$each];
            }
            sn_DebugLog('honeypot ' . $each . ' set=' . intval(isset($_POST[$each])) . ' empty=' . intval(empty($_POST[$each])) . ' value=' . $this->debugDump($trapVal));
        }

        if (ubRouting::checkPost($this->spamTraps, true, true)) {
            foreach ($this->spamTraps as $io => $each) {
                if (!empty($_POST[$each])) {
                    $trapHit = $each;
                }
            }
            $result = true;
            sn_DebugLog('createRequest honeypot hit field=' . $trapHit . ' fake success, skip RemoteAPI');
        } else {
            sn_DebugLog('createRequest honeypot clean');
            $jsProofOk = true;
            if ($this->cfgFlag('SPAM_TRAPS')) {
                $jsProofOk = $this->jsProofValid();
                sn_DebugLog('createRequest jsproof ok=' . intval($jsProofOk) . ' post=' . $this->debugDump($this->filterPost('unicrnpwr', 'raw')));
            }
            if (!$jsProofOk) {
                $result = true;
                sn_DebugLog('createRequest jsproof miss, fake success, skip RemoteAPI');
            } else {
                $needFields = $this->required;
                if (!$this->cfgFlag('NAME_DISPLAY', true)) {
                    $needFields = array();
                    foreach ($this->required as $io => $each) {
                        if ($each != 'realname') {
                            $needFields[] = $each;
                        }
                    }
                }
                $missing = array();
                foreach ($needFields as $io => $each) {
                    $present = ubRouting::checkPost($each);
                    sn_DebugLog('required ' . $each . ' ok=' . intval($present) . ' raw=' . $this->debugDump($this->filterPost($each)));
                    if (!$present) {
                        $missing[] = $each;
                    }
                }
                if (ubRouting::checkPost($needFields)) {
                    sn_DebugLog('createRequest required fields ok');
                    if ($this->burstAllow()) {
                        $visitorIp = '';
                        if (isset($_SERVER['REMOTE_ADDR'])) {
                            $visitorIp = $_SERVER['REMOTE_ADDR'];
                        }
                        $realname = $this->filterPost('realname');
                        if (!$this->cfgFlag('NAME_DISPLAY', true)) {
                            $realname = 'Not specified';
                        }
                        $city = $this->filterPost('snplc', 'nb');
                        if ($city == '') {
                            $city = $this->filterPost('city', 'nb');
                        }
                        $street = $this->filterPost('snln', 'nb');
                        if ($street == '') {
                            $street = $this->filterPost('street', 'nb');
                        }
                        $payload = array(
                            'city' => $city,
                            'street' => $street,
                            'build' => $this->filterPost('build'),
                            'apt' => $this->filterPost('apt'),
                            'realname' => $realname,
                            'phone' => $this->filterPost('phone'),
                            'email' => $this->filterPost('email', 'nb'),
                            'service' => $this->filterPost('service', 'nb'),
                            'tariff' => $this->filterPost('tariff', 'nb'),
                            'notes' => $this->filterPost('notes', 'emsafe'),
                            'ip' => $visitorIp
                        );
                        sn_DebugLog('createRequest payload=' . $this->debugDump($payload));
                        $reply = $this->apiRequest('create', json_encode($payload));
                        if (!empty($reply) and isset($reply['created']) and $reply['created']) {
                            $result = true;
                            sn_DebugLog('createRequest RemoteAPI created=1');
                        } else {
                            $apiMessage = '';
                            if (isset($reply['error_message'])) {
                                $apiMessage = $reply['error_message'];
                            }
                            sn_DebugLog('createRequest RemoteAPI failed error_message=' . $apiMessage . ' reply=' . $this->debugDump($reply));
                            if ($apiMessage == 'REQUIRED_FIELDS') {
                                $this->lastError = sn_RequiredHint();
                            } else {
                                $this->lastError = __('Unable to send signup request');
                            }
                        }
                    } else {
                        $this->lastError = __('Unable to send signup request');
                        sn_DebugLog('createRequest blocked by burstlimit');
                    }
                } else {
                    $this->lastError = sn_RequiredHint();
                    sn_DebugLog('createRequest missing required ' . $this->debugDump($missing));
                }
            }
        }

        sn_DebugLog('createRequest end result=' . intval($result) . ' lastError=' . $this->lastError);
        return ($result);
    }
}
