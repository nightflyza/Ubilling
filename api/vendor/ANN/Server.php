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

class Server
{
	/**#@+
	 * @ignore
	 */

	/**
	 * @var boolean
	 */
	protected $boolLogin = FALSE;

	/**
	 * @var Network
	 */
	protected $objNetwork = null;

	/**
	 * @var string
	 */
	protected $strNetworkSerialized = null;

	/**
	 * @var string
	 */
	protected $strDir = '';
	
	/**#@-*/
	
	/**
	 * @param string $strDir (Default: 'networks')
	 * @uses Exception::__construct()
	 * @uses onPost()
	 * @throws Exception
	 */
	
	public function __construct($strDir = 'networks')
	{
	  if(!is_dir($strDir) && is_writable($strDir))
	    throw new Exception('Directory '. $strDir .' does not exists or has no writing permissions');
	
	  $this->strDir = $strDir;
	
	  if(isset($_POST) && count($_POST))
	    $this->OnPost();
	}
	
	/**
	 * @uses loadFromHost()
	 * @uses checkLogin()
	 * @uses saveToHost()
	 * @uses trainByHost()
	 */
	
	protected function onPost()
	{
	  if(!isset($_POST['username']))
	    $_POST['username'] = '';
	
	  if(!isset($_POST['password']))
	    $_POST['password'] = '';
	    
	  settype($_POST['username'], 'string');
	
	  settype($_POST['password'], 'string');
	
	  $this->boolLogin = $this->checkLogin($_POST['username'], $_POST['password']);
	
	  if(!$this->boolLogin)
	    return;
	
	  if(isset($_POST['mode']))
	    switch($_POST['mode'])
	    {
	      case 'savetohost':
	
	        $this->strNetworkSerialized = $_POST['network'];
	
	        $this->saveToHost();
	
	        break;
	
	      case 'loadfromhost':
	
	        $this->loadFromHost();
	
	        break;
	
	      case 'trainbyhost':
	
	        $this->strNetworkSerialized = $_POST['network'];
	
	        $this->trainByHost();
	
	        break;
	    }
	}
	
	/**
	 * @param string $strUsername
	 * @param string $strPassword
	 * @return boolean
	 */
	
	protected function checkLogin($strUsername, $strPassword)
	{
	  return TRUE;
	}
	
	/**
	 * @uses Network::saveToFile()
	 */
	
	protected function saveToHost()
	{
	  $this->objNetwork = unserialize($this->strNetworkSerialized);
	  
	  if($this->objNetwork instanceof Network)
	    $this->objNetwork->saveToFile($this->strDir .'/'. $_POST['username'] .'.dat');
	}
	
	/**
	 * @uses Network::loadFromFile()
	 */
	
	protected function loadFromHost()
	{
	  $this->objNetwork = Network::loadFromFile($this->strDir .'/'. $_POST['username'] .'.dat');
	}
	
	/**
	 * @uses Network::saveToFile()
	 * @uses Network::train()
	 * @uses saveToHost()
	 */
	
	protected function trainByHost()
	{
	  $this->saveToHost();
	
	  if($this->objNetwork instanceof Network)
	  {
	    $this->objNetwork->saveToFile($this->strDir .'/'. $_POST['username'] .'.dat');
	
	    $this->objNetwork->train();
	  }
	}
	
	protected function printNetwork()
	{
	  header('Content-Type: text/plain');
	
	  print serialize($this->objNetwork);
	}
	
	/**
	 * @uses printNetwork()
	 */
	
	public function __destruct()
	{
	  if(isset($_POST['mode']))
	    switch($_POST['mode'])
	    {
	      case 'loadfromhost':
	      case 'trainbyhost':
	        $this->printNetwork();
	    }
	}
}
