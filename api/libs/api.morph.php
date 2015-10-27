<?php

class UBMorph {

    protected $currencyType = '';
    protected $altCfg = array();

    public function __construct() {
        $this->loadAlter();
        $this->initType();
    }

    /**
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Returns current currency
     * 
     * @return string
     */
    public function getType() {
        return ($this->currencyType);
    }

    /**
     * Inits default currency type at startup, handles TEMPLATE_CURRENCY option
     * 
     * @return void
     */
    protected function initType() {
        if (isset($this->altCfg['TEMPLATE_CURRENCY'])) {
            $this->currencyType = $this->altCfg['TEMPLATE_CURRENCY'];
        } else {
            $this->currencyType = 'UAH';
        }
    }

    /**
     * Sets current currency type like UAH or RUR
     * 
     * @param string $type
     * 
     * @return void
     */
    public function setType($type) {
        $this->currencyType = $type;
    }

    /**
     * Returns localized and literated sum for cash
     * 
     * @param float $sum
     * @param bool  $strippenny
     * @return string
     */
    public function sum2str($sum, $strippenny = false) {
        $zero = __('zero');
        $str[100] = array('', __('one hundred'), __('two hundred'), __('three hundred'), __('four hundred'), __('five hundred'), __('six hundred'), __('seven hundred'), __('eight hundred'), __('nine hundred'));
        $str[11] = array('', __('ten'), __('eleven'), __('twelve'), __('thirteen'), __('fourteen'), __('fifteen'), __('sixteen'), __('seventeen'), __('eightteen'), __('nineteen'), __('twenty'));
        $str[10] = array('', __('ten'), __('twenty'), __('thirty'), __('fourty'), __('fifty'), __('sixty'), __('seventy'), __('eighty'), __('ninety'));
        $sex = array(
            array('', __('one male'), __('two male'), __('three male'), __('four male'), __('five male'), __('six male'), __('seven male'), __('eight male'), __('nine male')), // m
            array('', __('one female'), __('two female'), __('three female'), __('four female'), __('five female'), __('six female'), __('seven female'), __('eight female'), __('nine female')) // f
        );

        if ($this->currencyType == 'UAH') {
            $nowCurrency = array(__('hryvna'), __('hryvnax'), __('hryvnas'), 0);
        }

        if ($this->currencyType == 'RUR') {
            $nowCurrency = array(__('ruble'), __('rublex'), __('rubles'), 0);
        }


        $forms = array(
            array(__('penny'), __('pennyx'), __('pennies'), 1), // 10^-2
            $nowCurrency, // 10^ 0
            array(__('thousand'), __('thousandx'), __('thousands'), 1), // 10^ 3
            array(__('million'), __('millionx'), __('millions'), 0), // 10^ 6
            array(__('billion'), __('billionx'), __('billions'), 0), // 10^ 9
            array(__('trillion'), __('trillionx'), __('trillions'), 0), // 10^12
        );
        $out = $tmp = array();

        $tmp = explode('.', str_replace(',', '.', $sum));
        $currency = number_format($tmp[0], 0, '', '-');
        if ($currency == 0) {
            $out[] = $zero;
        }
        // normalize penny
        $penny = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT), 0, 2) : '00';
        $segments = explode('-', $currency);
        $offset = sizeof($segments);
        if ((int) $currency == 0) { // if 0 money
            $o[] = $zero;
            $o[] = $this->morph(0, $forms[1][0], $forms[1][1], $forms[1][2]);
        } else {
            foreach ($segments as $k => $lev) {
                $sexi = (int) $forms[$offset][3]; // detect sex
                $ri = (int) $lev; // current segment
                if ($ri == 0 && $offset > 1) {
                    $offset--;
                    continue;
                }
                // normalization
                $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
                //extract digits
                $r1 = (int) substr($ri, 0, 1); //first digit
                $r2 = (int) substr($ri, 1, 1); //second digit
                $r3 = (int) substr($ri, 2, 1); //third digit
                $r22 = (int) $r2 . $r3; //second and third digit
                //extract limits
                if ($ri > 99)
                    $o[] = $str[100][$r1]; // hundreds
                if ($r22 > 20) {// >20
                    $o[] = $str[10][$r2];
                    $o[] = $sex[$sexi][$r3];
                } else { // <=20
                    if ($r22 > 9)
                        $o[] = $str[11][$r22 - 9]; // 10-20
                    elseif ($r22 > 0)
                        $o[] = $sex[$sexi][$r3]; // 1-9
                }
                // rounded cash
                $o[] = $this->morph($ri, $forms[$offset][0], $forms[$offset][1], $forms[$offset][2]);
                $offset--;
            }
        }
        // pennies
        if (!$strippenny) {
            $o[] = $penny;
            $o[] = $this->morph($penny, $forms[0][0], $forms[0][1], $forms[0][2]);
        }
        return preg_replace("/\s{2,}/", ' ', implode(' ', $o));
    }

    /**
     * Brutal morph here
     * 
     * @param int $n
     * @param int $f1
     * @param int $f2
     * @param int $f5
     * @return int
     */
    protected function morph($n, $f1, $f2, $f5) {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20)
            return $f5;
        if ($n1 > 1 && $n1 < 5)
            return $f2;
        if ($n1 == 1)
            return $f1;
        return $f5;
    }

}
