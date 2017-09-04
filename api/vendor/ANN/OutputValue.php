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
 */

final class OutputValue extends Filesystem implements InterfaceLoadable
{
	/**#@+
	 * @ignore
	 */

	/**
	 * @var float
	 */
	protected $floatMin;

	/**
	 * @var float
	 */
	protected $floatMax;

	/**
	 * @var boolean
	 */
	protected $boolOverride = FALSE;
	
	/**#@-*/
	
	/**
	 * @param float $floatMin
	 * @param float $floatMax
	 * @param boolean $boolOverride (Default: FALSE)
	 * @throws Exception
	 *
	 * If $boolOverride is FALSE, an exception will be thrown if getOutputValue() will
	 * be called with outranged values. If $boolOverride is TRUE, no exception will be
	 * thrown in this case, but lower values are replaced by $floatMin and upper values
	 * are replaced by $floatMax.
	 */
	
	public function __construct($floatMin, $floatMax, $boolOverride = FALSE)
	{
	  if(!is_float($floatMin) && !is_integer($floatMin))
	    throw new Exception('Constraints: $floatMin must be a float number');
	
	  if(!is_float($floatMax) && !is_integer($floatMax))
	    throw new Exception('Constraints: $floatMin must be a float number');
	
	  if($floatMin > $floatMax)
	    throw new Exception('Constraints: $floatMin should be lower than $floatMax');
	
	  if(!is_bool($boolOverride))
	    throw new Exception('Constraints: $boolOverride must be boolean');
	
	  $this->floatMin = $floatMin;
	  
	  $this->floatMax = $floatMax;
	  
	  $this->boolOverride = $boolOverride;
	}
	
	/**
	 * @param float $floatValue
	 * @return float (0..1)
	 * @uses calculateOutputValue()
	 * @throws Exception
	 */
	
	public function getOutputValue($floatValue)
	{
	  if(!$this->boolOverride && $floatValue < $this->floatMin)
	    throw new Exception('Constraints: $floatValue should be between '. $this->floatMin .' and '. $this->floatMax);
	
	  if(!$this->boolOverride && $floatValue > $this->floatMax)
	    throw new Exception('Constraints: $floatValue should be between '. $this->floatMin .' and '. $this->floatMax);
	
	  if($this->boolOverride && $floatValue < $this->floatMin)
	    $floatValue = $this->floatMin;
	
	  if($this->boolOverride && $floatValue > $this->floatMax)
	    $floatValue = $this->floatMax;
	
	  if($floatValue >= $this->floatMin && $floatValue <= $this->floatMax)
	    return $this->calculateOutputValue($floatValue);
	}
	
	/**
	 * @param float $floatValue (0..1)
	 * @return float
	 * @throws Exception
	 */
	
	public function getRealOutputValue($floatValue)
	{
	  if($floatValue < 0 || $floatValue > 1)
	    throw new Exception('Constraints: $floatValue should be between 0 and 1');
	
	  return $floatValue * ($this->floatMax - $this->floatMin) + $this->floatMin;
	}
	
	/**
	 * @param float $floatValue
	 * @return float
	 */
	
	protected function calculateOutputValue($floatValue)
	{
	  return ($floatValue - $this->floatMin) / ($this->floatMax - $this->floatMin);
	}
}
