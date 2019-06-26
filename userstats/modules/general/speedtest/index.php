<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if ($us_config['SP_ENABLED']) {

    class UserstatsSpeedTest {

        /**
         * Contains userstats config as key=>value
         *
         * @var array
         */
        protected $usConf = array();

        /**
         * Contains external speedtest URL
         *
         * @var string
         */
        protected $url = '';

        /**
         * Contains speedtest type
         *
         * @var int
         */
        protected $type = 1;

        /**
         * Contains default disclaimer for service
         *
         * @var array
         */
        protected $notice = '';

        /**
         * Custom width
         *
         * @var string
         */
        protected $width = '100%';

        /**
         * Custom height
         *
         * @var string
         */
        protected $height = '500';

        public function __construct() {
            $this->loadConfig();
            $this->setOptions();
        }

        /**
         * Loads userstats config into protected property for further usage
         * 
         * @global array $us_config
         * 
         * @return void
         */
        protected function loadConfig() {
            global $us_config;
            $this->usConf = $us_config;
        }

        /**
         * Sets some required options
         * 
         * @return void
         */
        protected function setOptions() {
            if (isset($this->usConf['SP_TYPE'])) {
                if (!empty($this->usConf['SP_TYPE'])) {
                    $this->type = $this->usConf['SP_TYPE'];
                }
            }
            
            if (isset($this->usConf['SP_SIZE'])) {
                if (!empty($this->usConf['SP_SIZE'])) {
                    $split=  explode('|', $this->usConf['SP_SIZE']);
                    if (isset($split[1])) {
                        $this->width=$split[0];
                        $this->height=$split[1];
                    } else {
                        show_window(__('Error'), __('wrong format').': SP_SIZE');
                    }
                }
            }

            $this->url = $this->usConf['SP_URL'];
            $this->notice = la_delimiter() . __('The test may not be accurate and is dependent on the type and configuration of client software. The results of tests can influence the type of browser settings firewall, flash player, active anti-virus scanning of HTTP traffic function, active downloads, etc');
        }

        /**
         * Returns default data container with disclaimer
         * 
         * @param string $data
         * 
         * @return string
         */
        protected function getContainer($data) {
            $result = la_tag('div', false, '', 'style="clear: both;"') . la_tag('div', true);
            $result.=la_tag('center', false);
            $result.=$data;
            $result.=la_tag('center', true);
            $result.=' ' . $this->notice;
            return ($result);
        }

        /**
         * Performs redirect to external testing service/url
         * 
         * @return void
         */
        protected function goRedirect() {
            rcms_redirect($this->url);
        }

        /**
         * Loads embedded HTML5 speedtest template and performs some localisation
         * 
         * @return string
         */
        protected function getEmbedded() {
            $result = file_get_contents('modules/jsc/speedtest/embed.html');
            $result = str_replace('DOWN_LABEL', __('Download speed'), $result);
            $result = str_replace('UP_LABEL', __('Upload speed'), $result);
            $result = str_replace('PING_LABEL', __('Ping'), $result);
            $result = str_replace('JITTER_LABEL', __('Jitter'), $result);
            $result = str_replace('START_LABEL', __('Start'), $result);
            $result = str_replace('ABORT_LABEL', __('Abort'), $result);
            return ($result);
        }

        /**
         * Returns iframe with custom URL
         * 
         * @return string
         */
        protected function getIframe() {
            $result = '';
            $result.=la_tag('iframe', false, '', 'width="'.$this->width.'" height="'.$this->height.'" frameborder="0" src="' . $this->url . '"') . la_tag('iframe', true);
            return ($result);
        }

        /**
         * Renders some speed testing code directed by type option 
         * 
         * @return string
         */
        public function render() {
            $result = '';
            $data = '';
            switch ($this->type) {
                case 1:
                    $data = $this->getEmbedded();
                    break;
                case 2:
                    $this->goRedirect();
                    break;
                case 3:
                    $data = $this->getIframe();
                    break;
            }
            $result = $this->getContainer($data);
            return ($result);
        }

    }

    $speedTest = new UserstatsSpeedTest();


    show_window(__('Speed test'), $speedTest->render());
} else {
    show_window(__('Sorry'), __('Unfortunately speedtest is now disabled'));
}
?>
