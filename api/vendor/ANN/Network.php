<?php

/**
 * Artificial Neural Network - Version 2.2
 *
 * For updates and changes visit the project page at http://ann.thwien.de/
 *
 *
 *
 * <b>LICENCE</b>
 *
 * The BSD 2-Clause License
 *
 * http://opensource.org/licenses/bsd-license.php
 *
 * Copyright (c) 2002, Eddy Young
 * Copyright (c) 2007 - 2012, Thomas Wien
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Eddy Young <jeyoung_at_priscimon_dot_com>
 * @author Thomas Wien <info_at_thwien_dot_de>
 * @version ANN Version 1.0 by Eddy Young
 * @version ANN Version 2.2 by Thomas Wien
 * @copyright Copyright (c) 2002 by Eddy Young
 * @copyright Copyright (c) 2007-2012 by Thomas Wien
 * @package ANN
 */

namespace ANN;

/**
 * @package ANN
 * @access public
 */

class Network extends Filesystem implements InterfaceLoadable
{
	/**#@+
	 * @ignore
	 */

	/**
	 * @var Layer
	 */
	protected $objOutputLayer = null;

	/**
	 * @var array
	 */
	protected $arrHiddenLayers = array();

	/**
	 * @var array
	 */
	protected $arrInputs = null;

	/**
	 * @var array
	 */
	protected $arrOutputs = null;

	/**
	 * @var integer
	 */
	protected $intTotalLoops = 0;

	/**
	 * @var integer
	 */
	protected $intTotalTrainings = 0;

	/**
	 * @var integer
	 */
	protected $intTotalActivations = 0;

	/**
	 * @var integer
	 */
	protected $intTotalActivationsRequests = 0;

	/**
	 * @var integer
	 */
	protected $intNumberOfHiddenLayers = null;

	/**
	 * @var integer
	 */
	protected $intNumberOfHiddenLayersDec = null; // decremented value

	/**
	 * @var integer
	 */
	protected $intMaxExecutionTime = 0;

	/**
	 * @var integer
	 */
	protected $intNumberEpoch = 0;

	/**
	 * @var boolean
	 */
	protected $boolLoggingWeights = FALSE;

	/**
	 * @var boolean
	 */
	protected $boolLoggingNetworkErrors = FALSE;

	/**
	 * @var boolean
	 */
	protected $boolTrained = FALSE;

	/**
	 * @var integer
	 */
	protected $intTrainingTime = 0; // Seconds

	/**
	 * @var Logging
	 */
	protected $objLoggingWeights = null;

	/**
	 * @var Logging
	 */
	protected $objLoggingNetworkErrors = null;

	/**
	 * @var boolean
	 */
	protected $boolNetworkActivated = FALSE;

	/**
	 * @var array
	 */
	protected $arrTrainingComplete = array();

	/**
	 * @var integer
	 */
	protected $intNumberOfNeuronsPerLayer = 0;

	/**
	 * @var float
	 */
	protected $floatOutputErrorTolerance = 0.02;

	/**
	 * @var float
	 */
	public $floatMomentum = 0.95;

	/**
	 * @var array
	 */
	private $arrInputsToTrain = array();

	/**
	 * @var integer
	 */
	private $intInputsToTrainIndex = -1;

	/**
	 * @var integer
	 */
	public $intOutputType = self::OUTPUT_LINEAR;

	/**
	 * @var float
	 */
	public $floatLearningRate = 0.7;

	/**
	 * @var boolean
	 */
	public $boolFirstLoopOfTraining = TRUE;

	/**
	 * @var boolean
	 */
	public $boolFirstEpochOfTraining = TRUE;
	
	/**#@-*/
	
	/**
	 * Linear output type
	 */
	
	const OUTPUT_LINEAR = 1;
	
	/**
	 * Binary output type
	 */
	
	const OUTPUT_BINARY = 2;
	
	/**
	 * @param integer $intNumberOfHiddenLayers (Default: 1)
	 * @param integer $intNumberOfNeuronsPerLayer (Default: 6)
	 * @param integer $intNumberOfOutputs (Default: 1)
	 * @uses Exception::__construct()
	 * @uses setMaxExecutionTime()
	 * @uses createHiddenLayers()
	 * @uses createOutputLayer()
	 * @throws Exception
	 */
	
	public function __construct($intNumberOfHiddenLayers = 1, $intNumberOfNeuronsPerLayer = 6, $intNumberOfOutputs = 1)
	{
	  if(!is_integer($intNumberOfHiddenLayers) || $intNumberOfHiddenLayers < 1)
	    throw new Exception('Constraints: $intNumberOfHiddenLayers must be a positiv integer >= 1');
	
	  if(!is_integer($intNumberOfNeuronsPerLayer) || $intNumberOfNeuronsPerLayer < 2)
	    throw new Exception('Constraints: $intNumberOfNeuronsPerLayer must be a positiv integer number >= 2');
	
	  if(!is_integer($intNumberOfOutputs) || $intNumberOfOutputs < 1)
	    throw new Exception('Constraints: $intNumberOfOutputs must be a positiv integer number >= 1');
	
		$this->createOutputLayer($intNumberOfOutputs);
		
		$this->createHiddenLayers($intNumberOfHiddenLayers, $intNumberOfNeuronsPerLayer);
	
		$this->intNumberOfHiddenLayers = $intNumberOfHiddenLayers;
	
	  $this->intNumberOfHiddenLayersDec = $this->intNumberOfHiddenLayers - 1;
	  
	  $this->intNumberOfNeuronsPerLayer = $intNumberOfNeuronsPerLayer;
	  
	  $this->setMaxExecutionTime();
	}
		
	/**
	 * @param array $arrInputs
	 */
	
	protected function setInputs($arrInputs)
	{
	  if(!is_array($arrInputs))
	    throw new Exception('Constraints: $arrInputs should be an array');
	
	  $this->arrInputs = $arrInputs;
	  
	  $this->intNumberEpoch = count($arrInputs);
	  
	  $this->nextIndexInputToTrain = 0;
	  
	  $this->boolNetworkActivated = FALSE;
	}
	
	/**
	 * @param array $arrOutputs
	 * @uses Exception::__construct()
	 * @uses Layer::getNeuronsCount()
	 * @throws Exception
	 */
	
	protected function setOutputs($arrOutputs)
	{
	  if(isset($arrOutputs[0]) && is_array($arrOutputs[0]))
	    if(count($arrOutputs[0]) != $this->objOutputLayer->getNeuronsCount())
	      throw new Exception('Count of arrOutputs doesn\'t fit to number of arrOutputs on instantiation of \\'. __NAMESPACE__ .'\\Network');
	
	  $this->arrOutputs = $arrOutputs;
	  
	  $this->boolNetworkActivated = FALSE;
	}
	
	/**
	 * Set Values for training or using network
	 *
	 * Set Values of inputs and outputs for training or just inputs for using
	 * already trained network.
	 *
	 * <code>
	 * $objNetwork = new \ANN\Network(2, 4, 1);
	 *
	 * $objValues = new \ANN\Values;
	 *
	 * $objValues->train()
	 *           ->input(0.12, 0.11, 0.15)
	 *           ->output(0.56);
	 *
	 * $objNetwork->setValues($objValues);
	 * </code>
	 *
	 * @param Values $objValues
	 * @uses Values::getInputsArray()
	 * @uses Values::getOutputsArray()
	 * @uses setInputs()
	 * @uses setOutputs()
	 * @since 2.0.6
	 */
	
	public function setValues(Values $objValues)
	{
	  $this->setInputs($objValues->getInputsArray());
	
	  $this->setOutputs($objValues->getOutputsArray());
	}
	
	/**
	 * @param array $arrInputs
	 * @uses Layer::setInputs()
	 */
	
	protected function setInputsToTrain($arrInputs)
	{
	  $this->arrHiddenLayers[0]->setInputs($arrInputs);
	  
	  $this->boolNetworkActivated = FALSE;
	}
		
	/**
	 * Get the output values
	 *
	 * Get the output values to the related input values set by setValues(). This
	 * method returns the output values as a two-dimensional array.
	 *
	 * @return array two-dimensional array
	 * @uses activate()
	 * @uses getCountInputs()
	 * @uses Layer::getOutputs()
	 * @uses Layer::getThresholdOutputs()
	 * @uses setInputsToTrain()
	 */
	
	public function getOutputs()
	{
	  $arrReturnOutputs = array();
	
	  $intCountInputs = $this->getCountInputs();
	
		for ($intIndex = 0; $intIndex < $intCountInputs; $intIndex++)
		{
	    $this->setInputsToTrain($this->arrInputs[$intIndex]);
	
	    $this->activate();
	
	    switch($this->intOutputType)
	    {
	      case self::OUTPUT_LINEAR:
	        $arrReturnOutputs[] = $this->objOutputLayer->getOutputs();
	        break;
	
	      case self::OUTPUT_BINARY:
	        $arrReturnOutputs[] = $this->objOutputLayer->getThresholdOutputs();
	        break;
	    }
	  }
	
		return $arrReturnOutputs;
	}
	
	/**
	 * @param integer $intKeyInput
	 * @return array
	 * @uses activate()
	 * @uses Layer::getOutputs()
	 * @uses Layer::getThresholdOutputs()
	 * @uses setInputsToTrain()
	 */
	
	public function getOutputsByInputKey($intKeyInput)
	{
		$this->setInputsToTrain($this->arrInputs[$intKeyInput]);
	
	  $this->activate();
	
	  switch($this->intOutputType)
	  {
	    case self::OUTPUT_LINEAR:
	      return $this->objOutputLayer->getOutputs();
	
	    case self::OUTPUT_BINARY:
	      return $this->objOutputLayer->getThresholdOutputs();
	  }
	}
	
	/**
	 * @param integer $intNumberOfHiddenLayers
	 * @param integer $intNumberOfNeuronsPerLayer
	 * @uses Layer::__construct()
	 */
	
	protected function createHiddenLayers($intNumberOfHiddenLayers, $intNumberOfNeuronsPerLayer)
	{
	  $layerId = $intNumberOfHiddenLayers;
	
	  for ($i = 0; $i < $intNumberOfHiddenLayers; $i++)
	  {
	    $layerId--;
	
	    if($i == 0)
	      $nextLayer = $this->objOutputLayer;
	
	    if($i > 0)
	      $nextLayer = $this->arrHiddenLayers[$layerId + 1];
	
	    $this->arrHiddenLayers[$layerId] = new Layer($this, $intNumberOfNeuronsPerLayer, $nextLayer);
	  }
	
	  ksort($this->arrHiddenLayers);
	}
		
	/**
	 * @param integer $intNumberOfOutputs
	 * @uses Layer::__construct()
	 */
	
	protected function createOutputLayer($intNumberOfOutputs)
	{
		$this->objOutputLayer = new Layer($this, $intNumberOfOutputs);
	}
		
	/**
	 * @uses Layer::setInputs()
	 * @uses Layer::activate()
	 * @uses Layer::getOutputs()
	 */
	
	protected function activate()
	{
	  $this->intTotalActivationsRequests++;
	
	  if($this->boolNetworkActivated)
	    return;
	
	  $this->arrHiddenLayers[0]->activate();
	
		$this->boolNetworkActivated = TRUE;
		
	  $this->intTotalActivations++;
	}
		
	/**
	 * @return boolean
	 * @uses Exception::__construct()
	 * @uses setInputs()
	 * @uses setOutputs()
	 * @uses hasTimeLeftForTraining()
	 * @uses isTrainingComplete()
	 * @uses isTrainingCompleteByEpoch()
	 * @uses setInputsToTrain()
	 * @uses training()
	 * @uses isEpoch()
	 * @uses logWeights()
	 * @uses logNetworkErrors()
	 * @uses getNextIndexInputsToTrain()
	 * @uses isTrainingCompleteByInputKey()
	 * @uses setDynamicLearningRate()
	 * @uses detectOutputType()
	 * @throws Exception
	 */
	
	public function train()
	{
	  if(!$this->arrInputs)
	    throw new Exception('No arrInputs defined. Use \\'. __NAMESPACE__ .'\\Network::setValues().');
	
	  if(!$this->arrOutputs)
	    throw new Exception('No arrOutputs defined. Use \\'. __NAMESPACE__ .'\\Network::setValues().');
	    
	  $this->detectOutputType();
	
	  if($this->isTrainingComplete())
	  {
	    $this->boolTrained = TRUE;
	    
	    return $this->boolTrained;
	  }
	
	  $intStartTime = date('U');
	  
	  $this->getNextIndexInputsToTrain(TRUE);
	
	  $this->boolFirstLoopOfTraining = TRUE;
	  
	  $this->boolFirstEpochOfTraining = TRUE;
	
	  $intLoop = 0;
	  
	  while($this->hasTimeLeftForTraining())
	  {
	  	$intLoop++;

    	$this->setDynamicLearningRate($intLoop);

	    $j = $this->getNextIndexInputsToTrain();
	
	    $this->setInputsToTrain($this->arrInputs[$j]);
	
	    if(!($this->arrTrainingComplete[$j] = $this->isTrainingCompleteByInputKey($j)))
	      $this->training($this->arrOutputs[$j]);
	
	    if($this->isEpoch())
	    {
	      if($this->boolLoggingWeights)
	        $this->logWeights();
	
	      if($this->boolLoggingNetworkErrors)
	        $this->logNetworkErrors();
	
	      if($this->isTrainingCompleteByEpoch())
	        break;
	        
	      $this->boolFirstEpochOfTraining = FALSE;
	    }
	
	    $this->boolFirstLoopOfTraining = FALSE;
	  }
	
	  $intStopTime = date('U');
	
	  $this->intTotalLoops += $intLoop;
	
	  $this->intTrainingTime += $intStopTime - $intStartTime;
	  
	  $this->boolTrained = $this->isTrainingComplete();
	  
	  return $this->boolTrained;
	}
	
	/**
	 * @return boolean
	 */
	protected function hasTimeLeftForTraining()
	{
		return ($_SERVER['REQUEST_TIME'] + $this->intMaxExecutionTime > date('U'));
	}
		
	/**
	 * @param boolean $boolReset (Default: FALSE)
	 * @return integer
	 */
	
	protected function getNextIndexInputsToTrain($boolReset = FALSE)
	{
	  if($boolReset)
	  {
	    $this->arrInputsToTrain = array_keys($this->arrInputs);
	
	    $this->intInputsToTrainIndex = -1;
	
	    return;
	  }
	
	  $this->intInputsToTrainIndex++;
	
	  if(!isset($this->arrInputsToTrain[$this->intInputsToTrainIndex]))
	  {
	    shuffle($this->arrInputsToTrain);
	
	    $this->intInputsToTrainIndex = 0;
	  }
	
	  return $this->arrInputsToTrain[$this->intInputsToTrainIndex];
	}
		
	/**
	 * @return integer
	 */
	
	public function getTotalLoops()
	{
	  return $this->intTotalLoops;
	}
	
	/**
	 * @return boolean
	 */
	
	protected function isEpoch()
	{
	  static $countLoop = 0;
	
	  $countLoop++;
	
	  if($countLoop >= $this->intNumberEpoch)
	  {
	    $countLoop = 0;
	
	    return TRUE;
	  }
	
	  return FALSE;
	}
	
	/**
	 * Setting the learning rate
	 *
	 * @param float $floatLearningRate (Default: 0.7) (0.1 .. 0.9)
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	protected function setLearningRate($floatLearningRate = 0.7)
	{
	  if(!is_float($floatLearningRate))
	    throw new Exception('$floatLearningRate should be between 0.1 and 0.9');
	
	  if($floatLearningRate <= 0 || $floatLearningRate >= 1)
	    throw new Exception('$floatLearningRate should be between 0.1 and 0.9');
	
	  $this->floatLearningRate = $floatLearningRate;
	}
	
	/**
	 * @return boolean
	 * @uses getOutputs()
	 */
	
	protected function isTrainingComplete()
	{
	  $arrOutputs = $this->getOutputs();
	  
	  switch($this->intOutputType)
	  {
	    case self::OUTPUT_LINEAR:
	
	      foreach($this->arrOutputs as $intKey1 => $arrOutput)
	        foreach($arrOutput as $intKey2 => $floatValue)
	          if(($floatValue > round($arrOutputs[$intKey1][$intKey2] + $this->floatOutputErrorTolerance, 3)) || ($floatValue < round($arrOutputs[$intKey1][$intKey2] - $this->floatOutputErrorTolerance, 3)))
	            return FALSE;
	
	      return TRUE;
	
	    case self::OUTPUT_BINARY:
	
	      foreach($this->arrOutputs as $intKey1 => $arrOutput)
	        foreach($arrOutput as $intKey2 => $floatValue)
	          if($floatValue != $arrOutputs[$intKey1][$intKey2])
	            return FALSE;
	
	      return TRUE;
	  }
	}
	
	/**
	 * @return boolean
	 */
	
	protected function isTrainingCompleteByEpoch()
	{
	  foreach($this->arrTrainingComplete as $trainingComplete)
	    if(!$trainingComplete)
	      return FALSE;
	    
	  return TRUE;
	}
	
	/**
	 * @param integer $intKeyInput
	 * @return boolean
	 * @uses getOutputsByInputKey()
	 */
	
	protected function isTrainingCompleteByInputKey($intKeyInput)
	{
	  $arrOutputs = $this->getOutputsByInputKey($intKeyInput);
	
	  if(!isset($this->arrOutputs[$intKeyInput]))
	    return TRUE;
	
	  switch($this->intOutputType)
	  {
	    case self::OUTPUT_LINEAR:
	
	        foreach($this->arrOutputs[$intKeyInput] as $intKey => $floatValue)
	          if(($floatValue > round($arrOutputs[$intKey] + $this->floatOutputErrorTolerance, 3)) || ($floatValue < round($arrOutputs[$intKey] - $this->floatOutputErrorTolerance, 3)))
	            return FALSE;
	
	      return TRUE;
	
	    case self::OUTPUT_BINARY:
	
	        foreach($this->arrOutputs[$intKeyInput] as $intKey => $floatValue)
	          if($floatValue != $arrOutputs[$intKey])
	            return FALSE;
	
	      return TRUE;
	  }
	}
	
	/**
	 * @return integer
	 */
	
	protected function getCountInputs()
	{
	  if(isset($this->arrInputs) && is_array($this->arrInputs))
	    return count($this->arrInputs);
	
	  return 0;
	}
	
	/**
	 * @param array $arrOutputs
	 * @uses activate()
	 * @uses Layer::calculateHiddenDeltas()
	 * @uses Layer::adjustWeights()
	 * @uses Layer::calculateOutputDeltas()
	 * @uses getNetworkError()
	 */
	
	protected function training($arrOutputs)
	{
		$this->activate();
		
		$this->objOutputLayer->calculateOutputDeltas($arrOutputs);
	
		for ($i = $this->intNumberOfHiddenLayersDec; $i >= 0; $i--)
			$this->arrHiddenLayers[$i]->calculateHiddenDeltas();
			
		$this->objOutputLayer->adjustWeights();
			
		for ($i = $this->intNumberOfHiddenLayersDec; $i >= 0; $i--)
			$this->arrHiddenLayers[$i]->adjustWeights();
			
		$this->intTotalTrainings++;
	
	  $this->boolNetworkActivated = FALSE;
	}
	
	/**
	 * @return string Filename
	 */
	
	protected static function getDefaultFilename()
	{
	  return preg_replace('/\.php$/', '.dat', basename($_SERVER['PHP_SELF']));
	}
	
	/**
	 * @param integer $intType (Default: Network::OUTPUT_LINEAR)
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	protected function setOutputType($intType = self::OUTPUT_LINEAR)
	{
	  settype($intType, 'integer');
	
	  switch($intType)
	  {
	    case self::OUTPUT_LINEAR:
	    case self::OUTPUT_BINARY:
	      $this->intOutputType = $intType;
	      break;
	
	    default:
	      throw new Exception('$strType must be \\'. __NAMESPACE__ .'\\Network::OUTPUT_LINEAR or \\'. __NAMESPACE__ .'\\Network::OUTPUT_BINARY');
	  }
	}
	
	/**
	 * @uses getCPULimit()
	 * @uses getMaxExecutionTime()
	 * @throws Exception
	 */

	protected function setMaxExecutionTime()
	{
		$intMaxExecutionTime = $this->getMaxExecutionTime();
		
		$intCPULimit = $this->getCPULimit();
		
		if($intMaxExecutionTime == 0)
		{
			$intMaxExecutionTime = $intCPULimit;
		}
		elseif($intCPULimit == 0)
		{
			$intMaxExecutionTime = $intMaxExecutionTime;
		}
		else
		{
			$intMaxExecutionTime = min($intMaxExecutionTime, $intCPULimit);
		}
		
		$this->intMaxExecutionTime = $intMaxExecutionTime;
		
		if($this->intMaxExecutionTime == 0 && !isset($_REQUEST['XDEBUG_SESSION_START']))
			throw new Exception('max_execution_time is 0');
	}
	
	/**
	 * @uses setMaxExecutionTime()
	 */
	
	public function __wakeup()
	{
	  $this->setMaxExecutionTime();
	
	  $this->boolNetworkActivated = FALSE;
	}
	
	/**
	 * @param string $strFilename (Default: null)
	 * @return Network
	 * @uses parent::loadFromFile()
	 * @uses getDefaultFilename()
	 */
	
	public static function loadFromFile($strFilename = null)
	{
	  if($strFilename === null)
	    $strFilename = self::getDefaultFilename();
	  
	  return parent::loadFromFile($strFilename);
	}
	
	/**
	 * @param string $strFilename (Default: null)
	 * @uses parent::saveToFile()
	 * @uses getDefaultFilename()
	 */
	
	public function saveToFile($strFilename = null)
	{
	  if($strFilename === null)
	    $strFilename = self::getDefaultFilename();
	
	  parent::saveToFile($strFilename);
	}
	
	/**
	 * @return integer
	 */
	
	public function getNumberInputs()
	{
	  if(isset($this->arrInputs) && is_array($this->arrInputs))
	    if(isset($this->arrInputs[0]))
	      return count($this->arrInputs[0]);
	
	  return 0;
	}
	
	/**
	 * @return integer
	 */
	
	public function getNumberHiddenLayers()
	{
	  if(isset($this->arrHiddenLayers) && is_array($this->arrHiddenLayers))
	    return count($this->arrHiddenLayers);
	
	  return 0;
	}
	
	/**
	 * @return integer
	 */
	
	public function getNumberHiddens()
	{
	  if(isset($this->arrHiddenLayers) && is_array($this->arrHiddenLayers))
	    if(isset($this->arrHiddenLayers[0]))
	      return $this->arrHiddenLayers[0]->getNeuronsCount();
	
	  return 0;
	}
	
	/**
	 * @return integer
	 */
	
	public function getNumberOutputs()
	{
	  if(isset($this->arrOutputs[0]) && is_array($this->arrOutputs[0]))
	    return count($this->arrOutputs[0]);
	
	  return 0;
	}
	
	/**
	 * Log weights while training in CSV format
	 *
	 * @param string $strFilename
	 * @uses Logging::__construct()
	 * @uses Logging::setFilename()
	 */
	
	public function logWeightsToFile($strFilename)
	{
	  $this->boolLoggingWeights = TRUE;
	
	  $this->objLoggingWeights = new Logging;
	
	  $this->objLoggingWeights->setFilename($strFilename);
	}
	
	/**
	 * Log network errors while training in CSV format
	 *
	 * @param string $strFilename
	 * @uses Logging::__construct()
	 * @uses Logging::setFilename()
	 */
	
	public function logNetworkErrorsToFile($strFilename)
	{
	  $this->boolLoggingNetworkErrors = TRUE;
	
	  $this->objLoggingNetworkErrors = new Logging;
	
	  $this->objLoggingNetworkErrors->setFilename($strFilename);
	}
	
	/**
	 * @uses Layer::getNeurons()
	 * @uses Logging::logData()
	 * @uses Neuron::getWeights()
	 * @uses getNetworkError()
	 */
	
	protected function logWeights()
	{
	  $arrData = array();
	
	  $arrData['E'] = $this->getNetworkError();
	
	  // ****** arrHiddenLayers ****************
	
	  foreach($this->arrHiddenLayers as $intKeyLayer => $objHiddenLayer)
	  {
	    $arrNeurons = $objHiddenLayer->getNeurons();
	
	    foreach($arrNeurons as $intKeyNeuron => $objNeuron)
	      foreach($objNeuron->getWeights() as $intKeyWeight => $weight)
	          $arrData["H$intKeyLayer-N$intKeyNeuron-W$intKeyWeight"] = round($weight, 5);
	  }
	
	  // ****** objOutputLayer *****************
	
	  $arrNeurons = $this->objOutputLayer->getNeurons();
	
	  foreach($arrNeurons as $intKeyNeuron => $objNeuron)
	    foreach($objNeuron->getWeights() as $intKeyWeight => $weight)
	        $arrData["O-N$intKeyNeuron-W$intKeyWeight"] = round($weight, 5);
	
	  // ************************************
	
	  $this->objLoggingWeights->logData($arrData);
	}
	
	/**
	 * @uses getNetworkError()
	 * @uses Logging::logData()
	 */
	
	protected function logNetworkErrors()
	{
	  $arrData = array();
	
	  $arrData['network error'] = number_format($this->getNetworkError(), 8, ',', '');
	
	  $arrData['learning rate'] = $this->floatLearningRate;
	
	  $this->objLoggingNetworkErrors->logData($arrData);
	}
	
	/**
	 * @return float
	 * @uses getOutputs()
	 */
	
	protected function getNetworkError()
	{
	  $floatError = 0;
	
	  $arrNetworkOutputs = $this->getOutputs();
	  
	  foreach($this->arrOutputs as $intKeyOutputs => $arrDesiredOutputs)
	    foreach($arrDesiredOutputs as $intKeyOutput => $floatDesiredOutput)
	      $floatError += pow($arrNetworkOutputs[$intKeyOutputs][$intKeyOutput] - $floatDesiredOutput, 2);
	
	  return $floatError / 2;
	}
	
	/**
	 * @param string $strUsername
	 * @param string $strPassword
	 * @param string $strHost
	 * @return Network
	 * @throws Exception
	 */
	
	public function trainByHost($strUsername, $strPassword, $strHost)
	{
	  if(!extension_loaded('curl'))
	    throw new Exception('Curl extension is not installed or active on this system');
	
	  $handleCurl = curl_init();
	
	  settype($strUsername, 'string');
	  settype($strPassword, 'string');
	  settype($strHost, 'string');
	
	  curl_setopt($handleCurl, CURLOPT_URL, $strHost);
	  curl_setopt($handleCurl, CURLOPT_POST, TRUE);
	  curl_setopt($handleCurl, CURLOPT_POSTFIELDS, "mode=trainbyhost&username=$strUsername&password=$strPassword&network=". serialize($this));
	  curl_setopt($handleCurl, CURLOPT_RETURNTRANSFER, 1);
	
	  $strResult = curl_exec($handleCurl);
	
	  curl_close($handleCurl);
	
	  $objNetwork = @unserialize($strResult);
	
	  if($objNetwork instanceof Network)
	    return $objNetwork;
	}
	
	/**
	 * @param string $strUsername
	 * @param string $strPassword
	 * @param string $strHost
	 * @throws Exception
	 */
	
	public function saveToHost($strUsername, $strPassword, $strHost)
	{
	  if(!extension_loaded('curl'))
	    throw new Exception('Curl extension is not installed or active on this system');
	
	  $handleCurl = curl_init();
	
	  settype($strUsername, 'string');
	  settype($strPassword, 'string');
	  settype($strHost,     'string');
	
	  curl_setopt($handleCurl, CURLOPT_URL, $strHost);
	  curl_setopt($handleCurl, CURLOPT_POST, TRUE);
	  curl_setopt($handleCurl, CURLOPT_POSTFIELDS, "mode=savetohost&username=$strUsername&password=$strPassword&network=". serialize($this));
	
	  curl_exec($handleCurl);
	
	  curl_close($handleCurl);
	}
	
	/**
	 * @param string $strUsername
	 * @param string $strPassword
	 * @param string $strHost
	 * @return Network
	 * @throws Exception
	 */
	
	public static function loadFromHost($strUsername, $strPassword, $strHost)
	{
	  if(!extension_loaded('curl'))
	    throw new Exception('Curl extension is not installed or active on this system');
	
	  $handleCurl = curl_init();
	
	  settype($strUsername, 'string');
	  settype($strPassword, 'string');
	  settype($strHost,     'string');
	
	  curl_setopt($handleCurl, CURLOPT_URL, $strHost);
	  curl_setopt($handleCurl, CURLOPT_POST, TRUE);
	  curl_setopt($handleCurl, CURLOPT_POSTFIELDS, "mode=loadfromhost&username=$strUsername&password=$strPassword");
	  curl_setopt($handleCurl, CURLOPT_RETURNTRANSFER, 1);
	
	  $strResult = curl_exec($handleCurl);
	
	  curl_close($handleCurl);
	
	  $objNetwork = unserialize(trim($strResult));
	
	  if($objNetwork instanceof Network)
	    return $objNetwork;
	}
	
	/**
	 * @uses setOutputType()
	 */
	
	protected function detectOutputType()
	{
		if(empty($this->arrOutputs))
			return;
		
	  foreach($this->arrOutputs as $arrOutputs)
	    foreach($arrOutputs as $floatOutput)
	      if($floatOutput < 1 && $floatOutput > 0)
	      {
	        $this->setOutputType(self::OUTPUT_LINEAR);
	
	        return;
	      }
	
	  $this->setOutputType(self::OUTPUT_BINARY);
	}
	
	/**
	 * Setting the percentage of output error in comparison to the desired output
	 *
	 * @param float $floatOutputErrorTolerance (Default: 0.02)
	 */
	
	public function setOutputErrorTolerance($floatOutputErrorTolerance = 0.02)
	{
	  if($floatOutputErrorTolerance < 0 || $floatOutputErrorTolerance > 0.1)
	    throw new Exception('$floatOutputErrorTolerance must be between 0 and 0.1');
	
	  $this->floatOutputErrorTolerance = $floatOutputErrorTolerance;
	}
	
	/**
	 * @param float $floatMomentum (Default: 0.95) (0 .. 1)
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	public function setMomentum($floatMomentum = 0.95)
	{
	  if(!is_float($floatMomentum) && !is_integer($floatMomentum))
	    throw new Exception('$floatLearningRate should be between 0 and 1');
	
	  if($floatMomentum <= 0 || $floatMomentum > 1)
	    throw new Exception('$floatLearningRate should be between 0 and 1');
	
	  $this->floatMomentum = $floatMomentum;
	}

	/**
	 * @uses \ANN\Controller\ControllerPrintNetwork::__construct()
	 */

	public function printNetwork()
	{
		$objController = new \ANN\Controller\ControllerPrintNetwork($this);
	}	
	
	/**
	 * @param integer $intLevel (Default: 2)
	 * @uses printNetwork()
	 */

	public function __invoke($intLevel = 2)
	{
		$this->printNetwork($intLevel);
	}
	
	/**
	 * @uses getPrintNetwork()
	 * @return string
	 */

	public function __toString()
	{
		return $this->getPrintNetwork();
	}
	
	/**
	 * Dynamic Learning Rate
	 *
	 * Setting learning rate all 1000 loops dynamically
	 *
	 * @param integer $intLoop
	 * @uses setLearningRate()
	 */

	protected function setDynamicLearningRate($intLoop)
	{
	  if($intLoop % 1000)
	    return;
	
	  $floatLearningRate = (mt_rand(5, 7) / 10);
	  
    $this->setLearningRate($floatLearningRate);
	}
	
	/**
	 * @return array
	 * @uses getCPULimit()
	 * @uses getMaxExecutionTime()
	 * @uses getNetworkError()
	 * @uses getNumberInputs()
	 * @uses getTrainedInputsPercentage()
	 */

	public function getNetworkInfo()
	{
		$arrReturn = array();
		
		switch($this->intOutputType)
		{
			case self::OUTPUT_BINARY:
				
				$arrReturn['detected_output_type'] = 'Binary';
				
				break;

			case self::OUTPUT_LINEAR:
				
				$arrReturn['detected_output_type'] = 'Linear';
				
				break;
		}
		
		$arrReturn['activation_function'] = 'Sigmoid';
		
		$arrReturn['momentum'] = $this->floatMomentum;
		
		$arrReturn['learning_rate'] = 'Dynamic';
		
		$arrReturn['network_error'] = $this->getNetworkError();
		
		$arrReturn['output_error_tolerance'] = $this->floatOutputErrorTolerance;
		
		$arrReturn['total_loops'] = $this->intTotalLoops;
				
		$arrReturn['total_trainings'] = $this->intTotalTrainings;
				
		$arrReturn['total_activations'] = $this->intTotalActivations;
				
		$arrReturn['total_activations_requests'] = $this->intTotalActivationsRequests;
				
		$arrReturn['epoch'] = $this->intNumberEpoch;
		
		$arrReturn['training_time_seconds'] = $this->intTrainingTime;
		
		$arrReturn['training_time_minutes'] = round($this->intTrainingTime / 60, 1);
		
		if($this->intTrainingTime > 0)
		{
			$arrReturn['loops_per_second'] = round($this->intTotalLoops / $this->intTrainingTime);
		}
		else
		{
			$arrReturn['loops_per_second'] = round($this->intTotalLoops / 0.1);
		}
		
		$arrReturn['training_finished'] = ($this->boolTrained) ? 'Yes' : 'No';

		$arrReturn['max_execution_time'] = $this->getMaxExecutionTime();
		
		$arrReturn['cpu_limit'] = $this->getCPULimit();
		
		$arrReturn['network']['arrHiddenLayers'] = $this->arrHiddenLayers;
		
		$arrReturn['network']['objOutputLayer'] = $this->objOutputLayer;
		
		$arrReturn['network']['intCountInputs'] = $this->getNumberInputs();
		
		$arrReturn['trained_percentage'] = $this->getTrainedInputsPercentage();
		
		$arrReturn['max_execution_time_network'] = $this->intMaxExecutionTime;
		
		$arrReturn['phpversion'] = phpversion();
		
		$arrReturn['phpinterface'] = php_sapi_name();
		
		return $arrReturn;
	}
	
	/**
	 * @return integer Seconds
	 */

	protected function getMaxExecutionTime()
	{
		return (int)ini_get('max_execution_time');
	}
	
	/**
	 * @return integer Seconds
	 */

	protected function getCPULimit()
	{
		return (int)shell_exec('ulimit -t');
	}
	
	/**
	 * @return float
	 * @uses isTrainingCompleteByInputKey()
	 */

	protected function getTrainedInputsPercentage()
	{
		$boolTrained = 0;
		
	  foreach($this->arrInputs as $intKeyInputs => $arrInputs)
	  {
			if($this->isTrainingCompleteByInputKey($intKeyInputs))
	      $boolTrained++;
	  }
	
	  return round(($boolTrained / @count($this->arrOutputs)) * 100, 1);
	}
}
