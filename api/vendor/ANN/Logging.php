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

class Logging
{
	/**#@+
	 * @ignore
	 */

	/**
	 * @var string
	 */
	protected $strFilename;

	/**
	 * @var resource
	 */
	protected $handleFile;

	/**
	 * @var boolean
	 */
	protected $boolHeader = FALSE;
	
	/**#@-*/
	
	const SEPARATOR = ';';
	
	/**
	 * @param string $strFilename
	 * @uses Exception::__construct()
	 * @throws Exception
	 */
	
	public function setFilename($strFilename)
	{
	  $this->strFilename = $strFilename;
	
	  $this->handleFile = @fopen($strFilename, 'w+');
	
	  if(!is_resource($this->handleFile))
	    throw new Exception('File '. basename($strFilename). ' cannot be created');
	}
	
	/**
	 * @param array $arrData
	 * @uses isHeader()
	 * @uses logHeader()
	 */
	
	public function logData($arrData)
	{
	  if(!$this->isHeader())
	    $this->logHeader($arrData);
	
	  $strData = implode(self::SEPARATOR, $arrData);
	
	  if(is_resource($this->handleFile))
	    @fwrite($this->handleFile, $strData, strlen($strData));
	
	  @fwrite($this->handleFile, "\r\n", strlen("\r\n"));
	}
	
	public function __destruct()
	{
	  if(is_resource($this->handleFile))
	    @fclose($this->handleFile);
	}
	
	/**
	 * @return boolean
	 */
	
	protected function isHeader()
	{
	  return $this->boolHeader;
	}
	
	/**
	 * @param array $arrData
	 */
	
	protected function logHeader($arrData)
	{
	  $strData = implode(self::SEPARATOR, array_keys($arrData));
	
	  if(is_resource($this->handleFile))
	    @fwrite($this->handleFile, $strData, strlen($strData));
	
	  @fwrite($this->handleFile, "\r\n", strlen("\r\n"));
	
	  $this->boolHeader = TRUE;
	}
}
