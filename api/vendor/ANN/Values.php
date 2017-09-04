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
 * @author Thomas Wien <info_at_thwien_dot_de>
 * @version ANN Version 2.2 by Thomas Wien
 * @copyright Copyright (c) 2007-2012 by Thomas Wien
 * @package ANN
 */

namespace ANN;

/**
 * @package ANN
 * @access public
 * @since 2.0.6
 */

class Values extends Filesystem implements InterfaceLoadable
{
	/**#@+
	 * @ignore
	 */

	/**
	 * @var array
	 */
	protected $arrInputs  = array();

	/**
	 * @var array
	 */
	protected $arrOutputs = array();

	/**
	 * @var boolean
	 */
	protected $boolLastActionInput = FALSE;

	/**
	 * @var boolean
	 */
	protected $boolTrain = FALSE;

	/**
	 * @var integer
	 */
	protected $intCountInputs = null;

	/**
	 * @var integer
	 */
	protected $intCountOutputs = null;
	
	/**#@-*/
	
	/**
	 * Input values
	 *
	 * List all input values comma separated
	 *
	 * <code>
	 * $objValues = new \ANN\Values;
	 *
	 * $objValues->train()
	 *           ->input(0.12, 0.11, 0.15)
	 *           ->output(0.56);
	 * </code>
	 *
	 * <code>
	 * $objValues = new \ANN\Values;
	 *
	 * $objValues->input(0.12, 0.11, 0.15)
	 *           ->input(0.13, 0.12, 0.16)
	 *           ->input(0.14, 0.13, 0.17);
	 * </code>
	 *
	 * @return Values
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	public function input()
	{
	  if($this->boolTrain && $this->boolLastActionInput)
	    throw new Exception('After calling input() method output() should be called');
	
	  $arrParameters = func_get_args();
	
	  $arrInputParameters = array();
	  
	  foreach($arrParameters as $mixedParameter)
		  if(is_array($mixedParameter))
		  {
				$arrInputParameters = array_merge($arrInputParameters, $mixedParameter);
		  }
		  elseif(is_numeric($mixedParameter))
		  {
		  	$arrInputParameters[] = $mixedParameter;
		  }
	  
	  $intCountParameters = func_num_args();
	  
	  foreach($arrInputParameters as $floatParameter)
	    if(!is_float($floatParameter) && !is_integer($floatParameter))
	      throw new Exception('Each parameter should be float');
	      
	  if($this->intCountInputs === null)
	    $this->intCountInputs =  $intCountParameters;
	    
	  if($this->intCountInputs != $intCountParameters)
	    throw new Exception('There should be '. $this->intCountInputs .' parameter values for input()');
	
	  $this->arrInputs[] = $arrInputParameters;
	  
	  $this->boolLastActionInput = TRUE;
	  
	  return $this;
	}
	
	/**
	 * Output values
	 *
	 * List all output values comma separated. Before you can call this method you
	 * have to call input(). After calling output() you cannot call the same method
	 * again. You have to call input() again first.
	 *
	 * <code>
	 * $objValues = new \ANN\Values;
	 *
	 * $objValues->train()
	 *           ->input(0.12, 0.11, 0.15)
	 *           ->output(0.56);
	 * </code>
	 *
	 * @return Values
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	public function output()
	{
	  if(!$this->boolLastActionInput)
	    throw new Exception('After calling output() method input() should be called');
	
	  if(!$this->boolTrain)
	    throw new Exception('Calling output() is just allowed for training. Call train() if values for training.');
	
	  $arrParameters = func_get_args();
	
	  // If Classification is used
	  
	  if(isset($arrParameters[0]) && is_array($arrParameters[0]))
			$arrParameters = $arrParameters[0];
	  
		$intCountParameters = func_num_args();
	
	  foreach($arrParameters as $floatParameter)
	    if(!is_float($floatParameter) && !is_integer($floatParameter))
	      throw new Exception('Each parameter should be float');
	
	  if($this->intCountOutputs === null)
	    $this->intCountOutputs =  $intCountParameters;
	
	  if($this->intCountOutputs != $intCountParameters)
	    throw new Exception('There should be '. $this->intCountOutputs .' parameter values for output()');
	
	  $this->arrOutputs[] = $arrParameters;
	
	  $this->boolLastActionInput = FALSE;
	
	  return $this;
	}
	
	/**
	 * @return Values
	 */
	
	public function train()
	{
	  $this->boolTrain = TRUE;
	  
	  return $this;
	}
	
	/**
	 * Get internal saved input array
	 *
	 * Actually there is no reason to call this method in your application. This
	 * method is used by \ANN\Network only.
	 *
	 * @return array
	 */
	
	public function getInputsArray()
	{
	  return $this->arrInputs;
	}
	
	/**
	 * Get internal saved output array
	 *
	 * Actually there is no reason to call this method in your application. This
	 * method is used by Network only.
	 *
	 * @return array
	 */
	
	public function getOutputsArray()
	{
	  return $this->arrOutputs;
	}
	
	/**
	 * Unserializing \ANN\Values
	 *
	 * After calling unserialize the train mode is set to false. Therefore it is
	 * possible to use a saved object of \ANN\Values to use inputs not for training
	 * purposes.
	 *
	 * You would not use unserialize in your application but you can call loadFromFile()
	 * to load the saved object to your application.
	 */
	
	public function __wakeup()
	{
	  $this->boolTrain = FALSE;
	}
	
	/**
	 * Reset saved input and output values
	 *
	 * All internal saved input and output values will be deleted after calling reset().
	 * If train() was called before, train state does not change by calling reset().
	 *
	 * <code>
	 * $objValues = new \ANN\Values;
	 *
	 * $objValues->train()
	 *           ->input(0.12, 0.11, 0.15)
	 *           ->output(0.56)
	 *           ->reset()
	 *           ->input(0.12, 0.11, 0.15)
	 *           ->output(0.56);
	 * </code>
	 *
	 * @return Values
	 */
	
	public function reset()
	{
	  $this->arrInputs = array();
	
	  $this->arrOutputs = array();
	  
	  return $this;
	}
}
