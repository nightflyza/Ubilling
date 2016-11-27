<?php

class widget_finance extends TaskbarWidget {

    /**
     * Caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Caching timeout in seconds
     *
     * @var int
     */
    protected $timeout = 3600; //hour

    /**
     * Initalizes system cache object for further usage
     * 
     * @return void
     */

    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Returns array of payments performed in current month
     * 
     * @return array
     */
    protected function getMonthPayments() {
        $result = array();
        $curmonth = curmonth();
        $query = "SELECT * from `payments` WHERE `date` LIKE '" . $curmonth . "-%' AND `summ`>0";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['id']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Direcly renders chart by current month payments
     * 
     * @return string
     */
    public function renderChart() {
        $result = '';
        $allPayments = $this->getMonthPayments();
        $chartData = array();
        $tmpArr = array();
        if (!empty($allPayments)) {
            $chartData[] = array(__('Day'), __('Cash'));
            foreach ($allPayments as $io => $each) {
                $paymentDate = strtotime($each['date']);
                $paymentDate = date("d", $paymentDate);

                if (isset($tmpArr[$paymentDate])) {
                    $tmpArr[$paymentDate]+=$each['summ'];
                } else {
                    $tmpArr[$paymentDate] = $each['summ'];
                }
            }

            if (!empty($tmpArr)) {
                foreach ($tmpArr as $day => $cash) {
                    $chartData[] = array($day, $cash);
                }
            }

            $result = $this->widgetContainer(wf_gchartsLine($chartData, __('Month payments'), '500px', '256px', ''));
        }
        return ($result);
    }

    /**
     * Renders widget code
     * 
     * @return string
     */
    public function render() {
        $result = '';
        $this->initCache();
        $obj = $this;
        $result = $this->cache->getCallback('WIDGET_FINANCE', function() use ($obj) {
            return ($obj->renderChart());
        }, $this->timeout);
        return ($result);
    }

}

?>