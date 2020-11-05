<?php

/**
 * Most Retarded Neural Network ever. Yep, with single neuron.
 */
class RNN {

    /**
     * Initial weight
     *
     * @var float
     */
    protected $weight = 0.5;

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
    protected $smoothing = 0.00001;

    /**
     * Training routine result
     *
     * @var float
     */
    protected $actualResult = 0.1;

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
    protected $iteration = 0;

    /**
     * Usage example:
     * 
      set_time_limit(0);
      $neuron = new RNN();

      $usd = 1;
      $uah = 28.24;

      //$neuron->setWeight(28.239990000006);

      if ($neuron->learn($usd, $uah)) {
      show_success('Learned weight: ' . $neuron->getWeight() . ' on iteration ' . $neuron->getIteration());
      }

      show_success($neuron->processInputData(100));
      show_success($neuron->restoreInputData(10));
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
     * TODO: different activation functions
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
     * TODO: different activation functions
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
     * Train neural network on some input value
     * 
     * @param float $input
     * @param float $expectedResult
     * 
     * @return bool
     */
    public function learn($input, $expectedResult) {
        $this->iteration = 0;
        while ($this->lastError > $this->smoothing OR $this->lastError < '-' . $this->smoothing) {
            $this->train($input, $expectedResult);
            // show_info(__('Train iteration') . ':' . $this->iteration . ' ' . __('Last error') . ': ' . $this->lastError);
            $this->iteration++;
        }
        return(true);
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
     * Returns smoothing factor
     * 
     * @return float
     */
    protected function getSmoothing() {
        return($this->smoothing);
    }

    /**
     * Returns current training iteration
     * 
     * @return float
     */
    public function getIteration() {
        return($this->iteration);
    }

}
