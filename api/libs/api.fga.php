<?php

/**
 * Implements fucking great advices interface
 */
class FGA {

    /**
     * System cache object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default response timeout in seconds
     *
     * @var int
     */
    protected $responseTimeout = 1;

    /**
     * Default advices caching timeout in seconds
     *
     * @var int
     */
    protected $cacheTimeout = 86400;

    /**
     * Contains remote advice api placeholder
     *
     * @var object
     */
    protected $adviceApi = '';

    /**
     * Predefined routes/URLs/etc
     */
    const URL_ADVICE = 'http://ubilling.net.ua/fga/api/random/';
    const ADVICE_KEY = 'FGADVICE';

    /**
     * Creates new awesome advices instance
     */
    public function __construct() {
        $this->initCache();
    }

    /**
     * Inits remote advice API handler
     * 
     * @return void
     */
    protected function initAdviceApi() {
        $this->adviceApi = new OmaeUrl(self::URL_ADVICE);
        $this->adviceApi->setTimeout(1);
    }

    /**
     * Inits Ubilling caching engine
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Returns a random advice
     * 
     * @return string
     */
    public function getRandomAdvice() {
        $this->initAdviceApi();
        $result = '';

        $randomAdviceRaw = $this->adviceApi->response();
        if (!empty($randomAdviceRaw)) {
            $randomAdviceText = json_decode($randomAdviceRaw, true);
            if (is_array($randomAdviceText)) {
                $result .= @$randomAdviceText['text'];
            }
        }

        //something went wrong?
        if (empty($result)) {
            $result = 'Oo';
        }

        return($result);
    }

    /**
     * Returns advice of the day
     * 
     * @return string
     */
    public function getAdviceOfTheDay() {
        $result = '';
        $this->initCache();

        $cachedData = $this->cache->get(self::ADVICE_KEY, $this->cacheTimeout);
        if (empty($cachedData)) {
            //getting new advice
            $randomAdvice = $this->getRandomAdvice();
            $result .= $randomAdvice;
            //updating cache
            $this->cache->set(self::ADVICE_KEY, $randomAdvice, $this->cacheTimeout);
        } else {
            $result .= $cachedData;
        }
        return($result);
    }

}
