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
    protected $epoch = 0;

    /**
     * Contains current training stats as epoch=>error
     *
     * @var array
     */
    protected $trainStats=array();

    /**
     * Contains train stats multiplier
     *
     * @var int
     */
    protected $statEvery=5000;

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
        $this->epoch = 0;
        while ($this->lastError > $this->smoothing OR $this->lastError < '-' . $this->smoothing) {
            $this->train($input, $expectedResult);
            if (($this->epoch % $this->statEvery)==0) {
            $this->trainStats[$this->epoch]=$this->lastError;
        	}
            $this->epoch++;
        }
        return(true);
    }

    public function learnDataSet($dataSet) {
    	$result=array();
    	if (is_array($dataSet)) {
    		if (!empty($dataSet)) {
    	    $totalweight = 0;
    		$neurons=array();
    		$neuronIndex=0;
    		foreach ($dataSet as $input=>$expectedResult) {
    			$neurons[$neuronIndex] = new MRNN();
    			 if ($neurons[$neuronIndex]->learn($input, $expectedResult)) {
         		   show_success('Learned weight: ' . $neurons[$neuronIndex]->getWeight() . ' on epoch ' . $neurons[$neuronIndex]->getEpoch()); //TODO: remove it
         		   $totalweight += $neurons[$neuronIndex]->getWeight();
         		   $result[]=$neurons[$neuronIndex]->getTrainStats();
         		   unset($neurons[$neuronIndex]);
        		 }
    			$neuronIndex++;
    		 }
  		 
			
    	     $this->weight = $totalweight / $neuronIndex; //learning complete
    	  }
    	}
    	return($result);
    }

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
    public function getEpoch() {
        return($this->epoch);
    }

}
