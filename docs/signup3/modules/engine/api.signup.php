<?php

/**
 * Public signup3 service - talks to Ubilling remoteapi only
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
    protected $required = array('street', 'build', 'realname', 'phone');

    /**
     * Honeypot field names
     *
     * @var array
     */
    protected $spamTraps = array('surname', 'lastname', 'seenoevil', 'mobile');

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
        $this->payload = $this->loadRemoteConfig();
        $this->setTemplateData();
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
        if ((!empty($this->apiUrl)) and (!empty($this->apiKey))) {
            $omae = new OmaeUrl($this->apiEndpoint($param));
            $omae->setTimeout(self::API_TIMEOUT);
            $omae->setOpt(CURLOPT_TIMEOUT, self::API_TIMEOUT);
            if ($rawBody != '') {
                $omae->dataHeader('Content-Type', 'application/json');
                $omae->dataPostRaw($rawBody);
            }
            $response = $omae->response();
            if (!$omae->error()) {
                if (!empty($response)) {
                    $decoded = json_decode($response, true);
                    if (is_array($decoded)) {
                        $result = $decoded;
                    }
                }
            }
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
            if ((filemtime($cacheName) + $this->cacheTimeout) > time()) {
                $useCache = true;
            } else {
                @unlink($cacheName);
            }
        }

        if ($useCache) {
            $rawData = file_get_contents($cacheName);
            if (!empty($rawData)) {
                $decoded = json_decode($rawData, true);
                if (is_array($decoded) and isset($decoded['config'])) {
                    $result = $decoded;
                } else {
                    $useCache = false;
                }
            } else {
                $useCache = false;
            }
        }

        if (!$useCache) {
            $remote = $this->apiRequest('config');
            if (!empty($remote) and isset($remote['config'])) {
                $result = $remote;
                if (isset($remote['config']['CACHING'])) {
                    if ($remote['config']['CACHING']) {
                        @file_put_contents($cacheName, json_encode($remote));
                    }
                }
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
     * City input
     *
     * @return string
     */
    protected function cityInput() {
        $result = '';
        if ($this->cfgFlag('CITY_DISPLAY')) {
            $cities = $this->cfgList('cities', true);
            if ($this->cfgFlag('CITY_SELECTABLE') and !empty($cities)) {
                $control = wf_SelectorSearchable('city', $this->selectorParams($cities, true), '', '', false);
            } else {
                $control = wf_TextInput('city', '', '', false, '', '', 'sn-control', '', 'autocomplete="address-level2"');
            }
            $result = $this->fieldWrap(__('Town'), $control, true);
        }
        return ($result);
    }

    /**
     * Street input
     *
     * @return string
     */
    protected function streetInput() {
        $streets = $this->cfgList('streets', true);
        if ($this->cfgFlag('STREET_SELECTABLE') and !empty($streets)) {
            $control = wf_SelectorSearchable('street', $this->selectorParams($streets, true), '', '', false);
        } else {
            $control = wf_TextInput('street', '', '', false, '', '', 'sn-control', '', 'autocomplete="address-line1"');
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
            $result .= wf_TextInput('surname', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="off"');
            $result .= wf_TextInput('lastname', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="off"');
            $result .= wf_TextInput('seenoevil', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="off"');
            $result .= wf_TextInput('mobile', '', '', false, '', '', '', '', 'tabindex="-1" autocomplete="off"');
            $result .= wf_tag('div', true);
        }
        return ($result);
    }

    /**
     * Renders public signup form
     *
     * @return string
     */
    public function renderForm() {
        $inputs = wf_HiddenInput('createrequest', 'true');
        $greeting = $this->cfgString('GREETING_TEXT');
        if ($greeting != '') {
            $inputs .= wf_tag('div', false, 'sn-greeting') . $greeting . wf_tag('div', true);
        }

        $inputs .= $this->cityInput();
        $inputs .= $this->streetInput();

        $build = wf_TextInput('build', '', '', false, '', '', 'sn-control', '', 'autocomplete="address-line2"');
        $apt = wf_TextInput('apt', '', '', false, '', '', 'sn-control', '', 'inputmode="numeric"');
        $inputs .= wf_tag('div', false, 'sn-row');
        $inputs .= $this->fieldWrap(__('Build'), $build, true, 'sn-col');
        $inputs .= $this->fieldWrap(__('Apartment'), $apt, false, 'sn-col');
        $inputs .= wf_tag('div', true);

        $inputs .= $this->spamTrapsInput();

        $realname = wf_TextInput('realname', '', '', false, '', '', 'sn-control', '', 'autocomplete="name"');
        $inputs .= $this->fieldWrap(__('Real name'), $realname, true);

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

        if (ubRouting::checkPost($this->spamTraps, true, true)) {
            $result = true;
        } else {
            if (ubRouting::checkPost($this->required)) {
                $visitorIp = '';
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $visitorIp = $_SERVER['REMOTE_ADDR'];
                }
                $payload = array(
                    'city' => $this->filterPost('city', 'nb'),
                    'street' => $this->filterPost('street', 'nb'),
                    'build' => $this->filterPost('build'),
                    'apt' => $this->filterPost('apt'),
                    'realname' => $this->filterPost('realname'),
                    'phone' => $this->filterPost('phone'),
                    'email' => $this->filterPost('email'),
                    'service' => $this->filterPost('service', 'nb'),
                    'tariff' => $this->filterPost('tariff', 'nb'),
                    'notes' => $this->filterPost('notes', 'emsafe'),
                    'ip' => $visitorIp,
                    'surname' => '',
                    'lastname' => '',
                    'seenoevil' => '',
                    'mobile' => ''
                );
                $reply = $this->apiRequest('create', json_encode($payload));
                if (!empty($reply) and isset($reply['created']) and $reply['created']) {
                    $result = true;
                } else {
                    $apiMessage = '';
                    if (isset($reply['error_message'])) {
                        $apiMessage = $reply['error_message'];
                    }
                    if ($apiMessage == 'REQUIRED_FIELDS') {
                        $this->lastError = sn_RequiredHint();
                    } else {
                        $this->lastError = __('Unable to send signup request');
                    }
                }
            } else {
                $this->lastError = sn_RequiredHint();
            }
        }

        return ($result);
    }
}
