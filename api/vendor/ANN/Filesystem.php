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
 * @access private
 */

abstract class Filesystem
{
	/**
	 * @param string $strFilename (Default: null)
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	public function saveToFile($strFilename = null)
	{
	  if(!($this instanceof InterfaceLoadable))
	    throw new Exception('Current object not instance of Interface \\ANN\\InterfaceLoadable');
	
	  settype($strFilename, 'string');
	
	  if(empty($strFilename))
			throw new Exception('Paramter $strFilename should be a filename');
	
	  $strDir = dirname($strFilename);
	  
	  if(empty($strDir))
	    $strDir = '.';
	    
	  if(!is_dir($strDir))
			throw new Exception("Directory $strDir does not exist");
	
	  if(!is_writable($strDir))
			throw new Exception("Directory $strDir has no writing permission");
			
	  if(is_file($strFilename) && !is_writable($strFilename))
			throw new Exception("File $strFilename does exist but has no writing permission");
	
		try
		{
		  $strSerialized = serialize($this);
	
	    file_put_contents($strFilename, $strSerialized);
	  }
	  catch(Exception $e)
		{
			throw new Exception("Could not open or create $strFilename!");
	  }
	}
	
	/**
	 * @param string $strFilename (Default: null)
	 * @return Network|InputValue|OutputValue|Values|StringValue|Classification|InterfaceLoadable
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	public static function loadFromFile($strFilename = null)
	{
		if(is_file($strFilename) && is_readable($strFilename))
	  {
	    $strSerialized = file_get_contents($strFilename);
	
	  	if (empty($strSerialized))
	      throw new Exception('File '. basename($strFilename) .' could not be loaded (file has no object information stored)');
	
			$objInstance = unserialize($strSerialized);
			
			if(!($objInstance instanceof InterfaceLoadable))
	      throw new Exception('File '. basename($strFilename) .' could not be opened (no ANN format)');
			
			return $objInstance;
		}
	
	  throw new Exception('File '. basename($strFilename) .' could not be opened');
	}
}
