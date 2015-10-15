<?php

class UserSideApi {

    /**
     * Contains all of available tariffs data as tariffname=>data
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all of available tariff speeds as tariffname=>data (speeddown/speedup keys)
     *
     * @var array
     */
    protected $allTariffSpeeds = array();

    /**
     * Contains all tariffs periods as tariffname=>period (month/day)
     *
     * @var array
     */
    protected $allTariffPeriods = array();

    public function __construct() {
        $this->loadTariffs();
        $this->loadTariffSpeeds();
        $this->loadTariffPeriods();
    }

    /**
     * Loads existing tariffs from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['name']] = $each;
            }
        }
    }

    /**
     * Loads existing tariff speeds from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffSpeeds() {
        $query = "SELECT * from `speeds`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffSpeeds[$each['tariff']] = $each;
            }
        }
    }

    /**
     * Loads existing tariff periods from database into protected property for further usage
     * 
     * @return void
     */
    protected function loadTariffPeriods() {
        $this->allTariffPeriods = zb_TariffGetPeriodsAll();
    }

    /**
     * Returns array of all of existing tariffs data
     * 
     * @return array
     */
    protected function getTariffsData() {
        $result = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $tariffName => $tariffData) {
                $result[$tariffName]['id'] = $tariffName;
                $result[$tariffName]['name'] = $tariffName;
                $result[$tariffName]['payment'] = $tariffData['Fee'];
                $result[$tariffName]['payment_interval'] = ($this->allTariffPeriods[$tariffName] == 'month') ? 30 : 1;
                $downspeed = (isset($this->allTariffSpeeds[$tariffName]['speedown'])) ? $this->allTariffSpeeds[$tariffName]['speedown'] : 0;
                $upspeed = (isset($this->allTariffSpeeds[$tariffName]['speedup'])) ? $this->allTariffSpeeds[$tariffName]['speedup'] : 0;
                $result[$tariffName]['speed'] = array(
                    'up' => $upspeed,
                    'down' => $downspeed,
                );
                $result[$tariffName]['traffic'] = ($tariffData['Free']) ? $tariffData['Free'] : -1;
            }
        }
        return ($result);
    }

    /**
     * Renders API reply as JSON string
     * 
     * @param array $data
     * 
     * @rerutn void
     */
    protected function renderReply($data) {
        $result = 'undefined';
        if (!empty($data)) {
            $result = json_encode($data);
        }
        die($result);
    }

    /**
     * Listens API requests and renders replies for it
     * 
     * @return void
     */
    public function catchRequest() {
        if (wf_CheckGet(array('request'))) {
            $request = $_GET['request'];
            switch ($request) {
                case 'get_tariff_list':
                    $this->renderReply($this->getTariffsData());
                    break;
                default :
                    $this->renderReply(array('unknown_request'));
                    break;
            }
        }
    }

}

?>