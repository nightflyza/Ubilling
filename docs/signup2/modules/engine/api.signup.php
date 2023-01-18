<?php

class SignupService {

    //settings
    protected $configRaw = array();
    protected $optionCitySelectable = true;
    protected $optionStreetSelectable = true;
    protected $optionCityDisplay = true;
    protected $optionEmailDisplay = true;
    protected $optionSpamTraps = true;
    protected $optionCaching = false;
    protected $optionConfCaching=false;
    protected $optionServices = '';
    protected $optionTariffs = '';
    protected $optionIspName = '';
    protected $optionIspUrl = '';
    protected $optionIspLogo = '';
    protected $optionSidebarText = '';
    protected $optionGreetingText = '';
    protected $optionHideouts = '';
    protected $cachingTime=3600;

    //caching
    const CACHE_PATH = 'cache/';
    protected $cachigTime = 3600;

    //other properties
    protected $cities = array();
    protected $streets = array();
    protected $services = array();
    protected $tariffs = array();
    protected $hideouts = array();
    protected $spamTraps = array('surname', 'lastname', 'seenoevil', 'mobile');
    protected $required = array('street', 'build', 'realname', 'phone');
    protected $important = '';

    public function __construct($confcache=0,$cachetimeout=3600) {
        if ($confcache) {
            $this->optionConfCaching=true;
        }
        $this->cachingTime=$cachetimeout;
        $this->important = ' ' . la_tag('sup') . '*' . la_tag('sup', true);
        $this->loadConfig();
        $this->configPreprocess();
        $this->loadServices();
        $this->loadTariffs();
        $this->loadHideouts();
        $this->setTemplateData();
    }

    /**
     * Loads sigreqconf config from database
     *  
     * @return void 
     */

    protected function loadConfig() {
           if ($this->optionConfCaching) {
            $cacheTime = $this->cachingTime;
            $cacheTime = time() - $cacheTime;
            $cacheName = self::CACHE_PATH . 'config.dat';
            $updateCache = false;
            if (file_exists($cacheName)) {
                $updateCache = false;
                if ((filemtime($cacheName) > $cacheTime)) {
                    $updateCache = false;
                } else {
                    $updateCache = true;
                }
            } else {
                $updateCache = true;
            }

            if (!$updateCache) {
                //read data directly from cache
                $result = array();
                $rawData = file_get_contents($cacheName);
                if (!empty($rawData)) {
                    $rawData= base64_decode($rawData);
                    $result = unserialize($rawData);
                    
                }
                $this->configRaw = $result;
            } else {
                //updating cache
                 $query = "SELECT * from `sigreqconf`";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->configRaw[$each['key']] = $each['value'];
                    }
                }
                $cacheStoreData = serialize($this->configRaw);
                $cacheStoreData = base64_encode($cacheStoreData);
                file_put_contents($cacheName, $cacheStoreData);
            }
        } else {
        $query = "SELECT * from `sigreqconf`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->configRaw[$each['key']] = $each['value'];
            }
        }
        }
    }

    /**
     * preprocessing of raw config into private triggers
     * 
     * @return void
     */

    protected function configPreprocess() {
        //preprocess data
        $this->optionIspName = (isset($this->configRaw['ISP_NAME'])) ? $this->configRaw['ISP_NAME'] : '';
        $this->optionIspUrl = (isset($this->configRaw['ISP_URL'])) ? $this->configRaw['ISP_URL'] : '';
        $this->optionIspLogo = (isset($this->configRaw['ISP_LOGO'])) ? $this->configRaw['ISP_LOGO'] : '';
        $this->optionSidebarText = (isset($this->configRaw['SIDEBAR_TEXT'])) ? $this->configRaw['SIDEBAR_TEXT'] : '';
        $this->optionGreetingText = (isset($this->configRaw['GREETING_TEXT'])) ? $this->configRaw['GREETING_TEXT'] : '';
        $this->optionServices = (isset($this->configRaw['SERVICES'])) ? $this->configRaw['SERVICES'] : '';
        $this->optionTariffs = (isset($this->configRaw['TARIFFS'])) ? $this->configRaw['TARIFFS'] : '';
        $this->optionHideouts = (isset($this->configRaw['HIDEOUTS'])) ? $this->configRaw['HIDEOUTS'] : '';
        $this->optionCitySelectable = (isset($this->configRaw['CITY_SELECTABLE'])) ? true : false;
        $this->optionCityDisplay = (isset($this->configRaw['CITY_DISPLAY'])) ? true : false;
        $this->optionStreetSelectable = (isset($this->configRaw['STREET_SELECTABLE'])) ? true : false;
        $this->optionEmailDisplay = (isset($this->configRaw['EMAIL_DISPLAY'])) ? true : false;
        $this->optionSpamTraps = (isset($this->configRaw['SPAM_TRAPS'])) ? true : false;
        $this->optionCaching = (isset($this->configRaw['CACHING'])) ? true : false;
    }

    /**
     * sets ISP name and others propertys to external scope
     * 
     * @return void
     */

    public function setTemplateData() {
        global $templateData;
        $templateData['ISP_NAME'] = $this->optionIspName;
        $templateData['ISP_URL'] = $this->optionIspUrl;
        $templateData['ISP_LOGO'] = $this->optionIspLogo;
        if ((!empty($this->optionIspName)) AND ( !empty($this->optionIspUrl)) AND ( !empty($this->optionIspLogo))) {
            $templateData['ISP_LINK'] = la_Link($this->optionIspUrl, la_img($this->optionIspLogo, $this->optionIspName), false);
        } else {
            $templateData['ISP_LINK'] = '';
        }
        $templateData['SIDEBAR_TEXT'] = $this->optionSidebarText;
        $templateData['GREETING_TEXT'] = $this->optionGreetingText;
    }

    /**
     * loads cities from database into private data property
     * 
     * @return void
     */

    protected function loadCities() {
        if ($this->optionCaching) {
            $cacheTime = $this->cachingTime;
            $cacheTime = time() - $cacheTime;
            $cacheName = self::CACHE_PATH . 'city.dat';
            $updateCache = false;
            if (file_exists($cacheName)) {
                $updateCache = false;
                if ((filemtime($cacheName) > $cacheTime)) {
                    $updateCache = false;
                } else {
                    $updateCache = true;
                }
            } else {
                $updateCache = true;
            }

            if (!$updateCache) {
                //read data directly from cache
                $result = array();
                $rawData = file_get_contents($cacheName);
                if (!empty($rawData)) {
                    $result = unserialize($rawData);
                }
                $this->cities = $result;
            } else {
                //updating cache
                $query = "SELECT * from `city`  ORDER BY `id` ASC";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->cities[$each['id']] = $each['cityname'];
                    }
                }
                $cacheStoreData = serialize($this->cities);
                file_put_contents($cacheName, $cacheStoreData);
            }
        } else {
            $query = "SELECT * from `city` ORDER BY `id` ASC";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->cities[$each['id']] = $each['cityname'];
                }
            }
        }
    }

    /**
     * prepares services for service selector inputs
     * 
     * @return void
     */

    protected function loadServices() {
        if (!empty($this->optionServices)) {
            $tmpArr = explode(',', $this->optionServices);
            if (!empty($tmpArr)) {
                foreach ($tmpArr as $io => $each) {
                    $this->services[trim($each)] = trim($each);
                }
            }
        }
    }

    /**
     * prepares tariffs if available, for tariffs selector inputs
     * 
     * @return void
     */

    protected function loadTariffs() {
        if (!empty($this->optionTariffs)) {
            $tmpArr = explode(',', $this->optionTariffs);
            if (!empty($tmpArr)) {
                foreach ($tmpArr as $io => $each) {
                    $this->tariffs[trim($each)] = trim($each);
                }
            }
        }
    }

    /**
     * prepares hideouts if available, for excluding in city and streets lists
     * 
     * @return void
     */

    protected function loadHideouts() {
        if (!empty($this->optionHideouts)) {
            $this->hideouts = explode(',', $this->optionHideouts);
        }
    }

    /**
     * loads streets from database into private data property
     * 
     * @return void
     */

    protected function loadStreets() {
        if ($this->optionCaching) {
            $cacheTime = $this->cachingTime;
            $cacheTime = time() - $cacheTime;
            $cacheName = self::CACHE_PATH . 'street.dat';
            $updateCache = false;
            if (file_exists($cacheName)) {
                $updateCache = false;
                if ((filemtime($cacheName) > $cacheTime)) {
                    $updateCache = false;
                } else {
                    $updateCache = true;
                }
            } else {
                $updateCache = true;
            }

            if (!$updateCache) {
                //read data directly from cache
                $result = array();
                $rawData = file_get_contents($cacheName);
                if (!empty($rawData)) {
                    $result = unserialize($rawData);
                }
                $this->streets = $result;
            } else {
                //updating cache
                $query = "SELECT * from `street`";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->streets[$each['id']] = $each['streetname'];
                    }
                }
                $cacheStoreData = serialize($this->streets);
                file_put_contents($cacheName, $cacheStoreData);
            }
        } else {
            //cache disabled
            $query = "SELECT * from `street`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->streets[$each['id']] = $each['streetname'];
                }
            }
        }
    }

    /**
     * returns city input depends selectable option
     * 
     * @return string
     */

    protected function cityInput() {
        $result = '';
        if ($this->optionCitySelectable) {
            $this->loadCities();
            if (!empty($this->cities)) {
                $cityNames = array();
                foreach ($this->cities as $io => $each) {
                    $cityNames[$each] = $each;
                }
                //hideouts processing
                if (!empty($this->hideouts)) {
                    foreach ($this->hideouts as $ia => $hideout) {
                        if (isset($cityNames[$hideout])) {
                            unset($cityNames[$hideout]);
                        }
                    }
                }
                $result = la_JuiComboBox('city', $cityNames, __('Town') . $this->important, '', false);
            }
        } else {
            $result = la_TextInput('city', __('Town') . $this->important, '', false, 15);
        }
        return ($result);
    }

    /**
     * returns street input depends options
     * 
     * @return string
     */

    protected function streetInput() {
        $result = '';
        if ($this->optionStreetSelectable) {
            $this->loadStreets();
            if (!empty($this->streets)) {
                $streetNames = array();
                foreach ($this->streets as $io => $each) {
                    $streetNames[$each] = $each;
                }
                if (!empty($streetNames)) {
                    natsort($streetNames);
                }
                $sortedStreets = array('' => __('Select one'));
                $sortedStreets = array_merge($sortedStreets, $streetNames);
                //hideouts processing
                if (!empty($this->hideouts)) {
                    foreach ($this->hideouts as $ia => $hideout) {
                        if (isset($sortedStreets[$hideout])) {
                            unset($sortedStreets[$hideout]);
                        }
                    }
                }
                $result = la_JuiComboBox('street', $sortedStreets, __('Street') . $this->important, '', false);
            }
        } else {
            $result = la_TextInput('street', __('Street') . $this->important, '', false, 25);
        }
        return ($result);
    }

    /**
     * returns build input
     * 
     * @return string
     */

    protected function buildInput() {
        $result = la_TextInput('build', __('Build') . $this->important, '', false, '5');
        return ($result);
    }

    /**
     * returns apartment input
     * 
     * @return string
     */

    protected function aptInput() {
        $result = la_TextInput('apt', __('Apartment') . la_tag('sup') . '&nbsp' . la_tag('sup', true), '', false, '5'); //vertical align ugly hack
        return ($result);
    }

    /**
     * returns realname input
     * 
     * @return string
     */

    protected function realnameInput() {
        $result = la_TextInput('realname', __('Real name') . $this->important, '', false, '25');
        return ($result);
    }

    /**
     * returns phone number input
     * 
     * @return string
     */

    protected function phoneInput() {
        $result = la_TextInput('phone', __('Phone') . $this->important, '', false, '25');
        return ($result);
    }

    /**
     * returns phone number input
     * 
     * @return string
     */

    protected function emailInput() {
        $result = la_TextInput('email', __('Email'), '', false, '25');
        return ($result);
    }

    /**
     * returns services select input
     * 
     * @return string
     */

    protected function serviceInput() {
        $result = '';
        if (!empty($this->services)) {
            $result = la_JuiComboBox('service', $this->services, __('Service') . $this->important, '', false);
        }
        return ($result);
    }

    /**
     * returns tariffs select input
     * 
     * @return string
     */

    protected function tariffsInput() {
        $result = '';
        if (!empty($this->tariffs)) {
            $result = la_JuiComboBox('tariff', $this->tariffs, __('Tariff'), '', false);
        }
        return ($result);
    }

    /**
     * anti spam bots dirty magic inputs ;)
     * 
     * @rerutn string
     */

    protected function spambotsTrap() {
        $result = la_tag('input', false, 'somemagic', 'type="text" name="surname"');
        $result.= la_tag('input', false, '', 'type="text" name="lastname" style="display:none;"');
        $result.= la_tag('input', false, 'somemagic', 'type="text" name="seenoevil"');
        $result.= la_tag('input', false, 'somemagic', 'type="text" name="mobile"');
        return ($result);
    }

    /**
     * returns signup notes input
     * 
     * @return string
     */

    protected function notesInput() {
        $result = la_TextArea('notes', __('Notes'), '', false, '50x5');
        return ($result);
    }

    /**
     * returns signup service main form
     * 
     * @retun string
     */

    public function renderForm() {
        $inputs = '';
        $inputs.=la_HiddenInput('createrequest', 'true');
        //greeting text
        $inputs.=$this->optionGreetingText;

        //optional city selector
        if ($this->optionCityDisplay) {
            $inputs.=$this->cityInput();
            $inputs.=la_tag('br');
        }
        //street selector
        $inputs.= $this->streetInput();

        //build and apt inputs
        $baCells = la_TableCell($this->buildInput());
        $baCells.= la_TableCell($this->aptInput());
        $baRows = la_TableRow($baCells);
        $inputs.=la_TableBody($baRows, '', 0, '');

        //realname input
        $inputs.= $this->realnameInput();
        $inputs.= la_tag('br');

        //dirty magic here
        if ($this->optionSpamTraps) {
            $inputs.=$this->spambotsTrap();
        }

        //phone input
        $inputs.= $this->phoneInput();
        //email optional input
        if ($this->optionEmailDisplay) {
            $inputs.= $this->emailInput();
            $inputs.= la_tag('br');
        }
        //service combo selector
        if (!empty($this->services)) {
            $inputs.=$this->serviceInput();
            $inputs.= la_tag('br');
        }

        //optional tariffs selector
        if (!empty($this->tariffs)) {
            $inputs.=$this->tariffsInput();
            $inputs.= la_tag('br');
        }

        //notes text area
        $inputs.=$this->notesInput();


        $inputs.= la_tag('br');

        $inputs.=la_tag('small') . __('All fields marked with an asterisk (*) are required') . la_tag('small', true);
        $inputs.= la_tag('br');
        $inputs.= la_tag('br');
        $inputs.= la_Submit(__('Send signup request'));
        $result = la_tag('div', false, '', 'id="signup_form"');
        $result.= la_Form("", 'POST', $inputs, '');
        $result.= la_tag('div', true);

        return ($result);
    }

    /**
     * filters input data
     * 
     * @param string $data data to filter
     * 
     * @return string
     */

    protected function filter($data) {
        $data = trim($data);
        $data = strip_tags($data);
        $data = mysql_real_escape_string($data);
        return ($data);
    }

    /**
     * checks spam fields availability
     * 
     * @return bool 
     */

    protected function spamCheck() {
        $result = true;
        if ($this->optionSpamTraps) {
            foreach ($this->spamTraps as $eachTrap) {
                if (la_CheckPost(array($eachTrap))) {
                    return (false);
                }
            }
        }
        return ($result);
    }

    /**
     * creates signup request in database
     * 
     * @return bool
     */

    public function createRequest() {
        $date = date("Y-m-d H:i:s");
        $ip = $_SERVER['REMOTE_ADDR'];
        $state = 0;

        $result = true;
        if (la_CheckPost($this->required)) {
            //all of required fields filled
            $street = '';
            if (la_CheckPost(array('city'))) {
                $street.=$this->filter($_POST['city']) . ' ';
            }
            $street.=$this->filter($_POST['street']);
            $build = $this->filter($_POST['build']);

            if (la_CheckPost(array('apt'))) {
                $apt = $this->filter($_POST['apt']);
            } else {
                $apt = 0;
            }

            $realname = $this->filter($_POST['realname']);
            $phone = $this->filter($_POST['phone']);

            if (la_CheckPost(array('email'))) {
                $email = 'Email: ' . $this->filter($_POST['email']) . "\n";
            } else {
                $email = '';
            }

            if (la_CheckPost(array('service'))) {
                $service = $this->filter($_POST['service']);
            } else {
                $service = 'No';
            }

            if (la_CheckPost(array('tariff'))) {
                $tariff = 'Tariff: ' . $this->filter($_POST['tariff']) . "\n";
            } else {
                $tariff = '';
            }

            $notes = '';
            if (la_CheckPost(array('notes'))) {
                $notes.=$this->filter($_POST['notes']) . "\n";
            }
            $notes.=$tariff;
            $notes.=$email;


            $query = "INSERT INTO `sigreq` (
                                `id` ,
                                `date` ,
                                `state` ,
                                `ip` ,
                                `street` ,
                                `build` ,
                                `apt` ,
                                `realname` ,
                                `phone` ,
                                `service` ,
                                `notes`
                                )
                                VALUES (
                                NULL ,
                                '" . $date . "',
                                '" . $state . "',
                                '" . $ip . "',
                                '" . $street . "',
                                '" . $build . "',
                                '" . $apt . "',
                                '" . $realname . "',
                                '" . $phone . "',
                                '" . $service . "',
                                '" . $notes . "'
                                );
        ";
            //silent spam check
            if ($this->spamCheck()) {
                nr_query($query);
            }
        } else {
            $result = false;
        }
        return ($result);
    }

}

?>
