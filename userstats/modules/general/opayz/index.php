<?php

$user_ip = zbs_UserDetectIp('debug');
$us_config = zbs_LoadConfig();
if ($us_config['OPENPAYZ_ENABLED']) {


    class UserstatsOpenPayz {

        /**
         * Contains userstats config as key=>value
         *
         * @var array
         */
        protected $usConf = array();

        /**
         * Contains openpayz backends URL
         *
         * @var string
         */
        protected $url = '';

        /**
         * Contains user IP
         *
         * @var string
         */
        protected $userIp = '';

        /**
         * Contains current user login
         *
         * @var string
         */
        protected $userLogin = '';

        /**
         * Contains current user payment id
         *
         * @var string
         */
        protected $paymentId = '';

        /**
         * Contains available payment systems backends list
         *
         * @var string
         */
        protected $paySys = array();

        /**
         * Contains available backends descriptions
         *
         * @var array
         */
        protected $payDesc = array();

        /**
         * Default payment system icon extension
         *
         * @var string
         */
        protected $iconExt = 'png';

        /**
         * Contains current skins path
         *
         * @var string
         */
        protected $skinPath = '';

        public function __construct($ip) {
            $this->setIp($ip);
            $this->setSkinPath();
            $this->loadConfig();
            $this->loadPaySys();
            $this->setPaymentId();
        }

        /**
         * Loads userstats config
         * 
         * @return void
         */
        protected function loadConfig() {
            $this->usConf = zbs_LoadConfig();
        }

        /**
         * Sets current skin path
         * 
         * @return void
         */
        protected function setSkinPath() {
            $this->skinPath = zbs_GetCurrentSkinPath($this->usConf);
        }

        /**
         * Sets current instance user ip
         * 
         * @param string $ip
         * 
         * @return void
         */
        protected function setIp($ip) {
            $this->userIp = $ip;
        }

        /**
         * Loads available payment systems and their descriptions
         * 
         * @return void
         */
        protected function loadPaySys() {
            $this->paySys = explode(",", $this->usConf['OPENPAYZ_PAYSYS']);
            if (file_exists('config/opayz.ini')) {
                $this->payDesc = parse_ini_file('config/opayz.ini');
            } else {
                $this->payDesc = array();
            }
        }

        /**
         * Sets current user payment id property
         * 
         * @return void
         */
        protected function setPaymentId() {
            if ($this->usConf['OPENPAYZ_REALID']) {
                $this->userLogin = zbs_UserGetLoginByIp($this->userIp);
                $this->paymentId = zbs_PaymentIDGet($this->userLogin);
            } else {
                $this->paymentId = ip2long($this->userIp);
            }
        }

        /**
         * Loads and returns module style
         * 
         * @return string
         */
        protected function getStyle() {
            $result = la_tag('style', false);
            $result.= file_get_contents($this->skinPath . '/opayz.css');
            $result.= la_tag('style', true);
            return ($result);
        }

        /**
         * Renders backends list
         * 
         * @return void
         */
        public function render() {
            $result = '';
            $result.=$this->getStyle();
            $inputs = '';
            if (!empty($this->paySys)) {
                if (!empty($this->paymentId)) {
                    foreach ($this->paySys as $eachpaysys) {
                        if (isset($this->payDesc[$eachpaysys])) {
                            $paysys_desc = $this->payDesc[$eachpaysys];
                        } else {
                            $paysys_desc = '';
                        }


                        $iconsPath = $this->skinPath . 'paysys/';
                        if (file_exists($iconsPath . $eachpaysys . '.' . $this->iconExt)) {
                            $paysysIcon = $iconsPath . $eachpaysys . '.' . $this->iconExt;
                        } else {
                            $paysysIcon = '';
                        }

                        $inputs = la_tag('div', false, 'opbackend');
                        $inputs.=la_HiddenInput('customer_id', $this->paymentId);

                        if (empty($paysysIcon)) {
                            $inputs.=la_Submit(strtoupper($eachpaysys));
                            $inputs.=la_tag('br');
                            $inputs.=$paysys_desc;
                        } else {
                            $fullDesc = ' alt="' . strtoupper($eachpaysys) . ' - ' . $paysys_desc . '" title="' . strtoupper($eachpaysys) . ' - ' . $paysys_desc . '" ';
                            $iconParams = 'width="200" height="200" ';
                            $inputs.=la_tag('input', false, '', 'type="image" src="' . $paysysIcon . '"' . $fullDesc . $iconParams);
                        }
                        $inputs.=la_tag('div', true);


                        $result.= la_Form($this->usConf['OPENPAYZ_URL'] . $eachpaysys . '/', 'GET', $inputs, '', '', false);
                    }


                    show_window(__('Online payments'), $result);
                }
            } else {
                show_window(__('Sorry'), __('No available payment systems'));
            }
        }

    }

    $usOpz = new UserstatsOpenPayz($user_ip);
    $usOpz->render();
} else {
    show_window(__('Sorry'), __('Unfortunately online payments are disabled'));
}
?>
