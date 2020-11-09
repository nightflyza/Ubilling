<?php

/**
 * Most Retarded Neural Network ever. Yep, with single neuron.
 */
class MRNN {

    /**
     * Initial weight
     *
     * @var float
     */
    protected $weight = 0.01;

    /**
     * Last neuron training error
     *
     * @var float
     */
    protected $lastError = 1;

    /**
     * Smoothing factor
     *
     * @var float
     */
    protected $smoothing = 0.0001;

    /**
     * Training routine result
     *
     * @var float
     */
    protected $actualResult = 0.01;

    /**
     * Contains current weight correction
     *
     * @var float
     */
    protected $correction = 0;

    /**
     * Contains current training iteration
     *
     * @var int
     */
    protected $epoch = 0;

    /**
     * Contains current training stats as epoch=>error
     *
     * @var array
     */
    protected $trainStats = array();

    /**
     * Contains train stats multiplier
     *
     * @var int
     */
    protected $statEvery = 5000;

    /**
     * Output of debug messages due train progress
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * What did you expect?
     */
    public function __construct() {
        //nothing to see here
    }

    /**
     * Sets neuron instance weight
     * 
     * @param float $weight
     * 
     * @return void
     */
    public function setWeight($weight) {
        $this->weight = $weight;
    }

    /**
     * Returns data output processed by trained neuron (forward)
     * 
     * @param float $input
     * 
     * @return float
     */
    public function processInputData($input) {
        $result = $input * $this->weight;
        return($result);
    }

    /**
     * Returns data input processed by trained neuron (backward)
     * 
     * @param float $output
     * 
     * @return float
     */
    public function restoreInputData($output) {
        $result = $output / $this->weight;
        return($result);
    }

    /**
     * Just native sigmoid function
     * 
     * @param float $value
     * 
     * @return float
     */
    protected function sigmoid($value) {
        return 1 / (1 + exp(-$value));
    }

    /**
     * Do the neuron train routine
     * 
     * @param float $input
     * @param float $expectedResult
     * 
     * @return void
     */
    protected function train($input, $expectedResult) {
        $this->actualResult = $input * $this->weight;
        $this->lastError = $expectedResult - $this->actualResult;
        $this->correction = ($this->lastError / $this->actualResult) * $this->smoothing;
        $this->weight += $this->correction;
    }

    /**
     * Train neural network on some single input value
     * 
     * @param float $input
     * @param float $expectedResult
     * 
     * @return bool
     */
    protected function learn($input, $expectedResult) {
        $this->epoch = 0;
        while ($this->lastError > $this->smoothing OR $this->lastError < '-' . $this->smoothing) {
            $this->train($input, $expectedResult);
            if (($this->epoch % $this->statEvery) == 0) {
                $this->trainStats[$this->epoch] = $this->lastError;
            }
            $this->epoch++;
        }
        return(true);
    }

    /**
     * Performs training of neural network with 
     * 
     * @param array $dataSet inputs data array like array(inputValue=>estimatedValue)
     * @param bool $accel perform learning optimizations with previous weight inherition
     * 
     * @return bool
     */
    public function learnDataSet($dataSet, $accel = false) {
        $result = false;
        if (is_array($dataSet)) {
            if (!empty($dataSet)) {
                $totalweight = 0;
                $neurons = array();
                $neuronIndex = 0;
                $prevWeight = $this->weight;
                $networkName = get_class($this);
                foreach ($dataSet as $input => $expectedResult) {
                    $neurons[$neuronIndex] = new $networkName();
                    //optional learning acceleration via  next weight correction
                    if ($accel) {
                        $neurons[$neuronIndex]->setWeight($prevWeight);
                    }
                    if ($neurons[$neuronIndex]->learn($input, $expectedResult)) {
                        if ($this->debug) {
                            show_success('Trained weight: ' . $neurons[$neuronIndex]->getWeight() . ' on epoch ' . $neurons[$neuronIndex]->getEpoch());
                        }
                        $totalweight += $neurons[$neuronIndex]->getWeight();
                        $this->trainStats[] = $neurons[$neuronIndex]->getTrainStats();
                        $prevWeight = $neurons[$neuronIndex]->getWeight();
                        unset($neurons[$neuronIndex]);
                    }
                    $neuronIndex++;
                }
                $this->weight = $totalweight / $neuronIndex; //learning complete
            }
        }
        $result = true;
        return($result);
    }

    /**
     * Retrurns current network instance training stats
     * 
     * @return array
     */
    public function getTrainStats() {
        return($this->trainStats);
    }

    /**
     * Returns current neuron weight
     * 
     * @return float
     */
    public function getWeight() {
        return($this->weight);
    }

    /**
     * Returns current train last error
     * 
     * @return float
     */
    protected function getLastError() {
        return($this->lastError);
    }

    /**
     * Returns current training epoch
     * 
     * @return float
     */
    protected function getEpoch() {
        return($this->epoch);
    }

    /**
     * Sets debug state of learning progress
     * 
     * @param bool $debugState
     * 
     * @return void
     */
    public function setDebug($debugState = false) {
        $this->debug = $debugState;
    }

    /**
     * Performs network training progress
     * 
     * @param array $trainStats
     * 
     * @return string
     */
    public function visualizeTrain($trainStats) {
        $result = '';
        $chartData = array(0 => array(__('Epoch'), __('Error')));
        if (!empty($trainStats)) {
            foreach ($trainStats as $neuron => $neuronStats) {
                if (!empty($neuronStats)) {
                    foreach ($neuronStats as $epoch => $error) {
                        $chartData[] = array($epoch, $error);
                    }
                }
            }
            $result .= wf_gchartsLine($chartData, __('Network training'), '100%', '400px', '');
        }
        return($result);
    }

}
