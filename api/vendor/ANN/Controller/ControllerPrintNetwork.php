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
 
namespace ANN\Controller;
 
/**
 * @package ANN
 * @access private
 */

class ControllerPrintNetwork extends Controller
{
	/**
	 * @var \ANN\Network
	 */

	protected $objNetwork;
	
	/**
	 * @var \ANN\Views\View
	 */
	
	protected $objViewLayer;

	/**
	 * @var \ANN\Views\View
	 */
	
	protected $objViewNeuron;

	/**
	 * @param \ANN\Network $objNetwork
	 * @uses parent::__construct()
	 */
	
	public function __construct(\ANN\Network $objNetwork)
	{
		$this->objNetwork = $objNetwork;
		
		parent::__construct();
	}

	/**
	 * \ANN\Views\View::__construct()
	 * parent::Header()
	 */
	
	protected function Header()
	{
		parent::Header();
		
		$strFilenameNetwork = $this->strDirectoryTemplates . DIRECTORY_SEPARATOR .'tpl.network.html';

		$strFilenameLayer = $this->strDirectoryTemplates . DIRECTORY_SEPARATOR .'tpl.layer.html';

		$strFilenameNeuron = $this->strDirectoryTemplates . DIRECTORY_SEPARATOR .'tpl.neuron.html';

		if(!is_file($strFilenameNetwork))
		  throw new \ANN\Exception('File '. $strFilenameNetwork .' does not exist');

		if(!is_file($strFilenameLayer))
		  throw new \ANN\Exception('File '. $strFilenameLayer .' does not exist');

		if(!is_file($strFilenameNeuron))
		  throw new \ANN\Exception('File '. $strFilenameNeuron .' does not exist');

		if(!is_readable($strFilenameNetwork))
		  throw new \ANN\Exception('File '. $strFilenameNetwork .' is not readable');

		if(!is_readable($strFilenameLayer))
		  throw new \ANN\Exception('File '. $strFilenameLayer .' is not readable');

		if(!is_readable($strFilenameNeuron))
		  throw new \ANN\Exception('File '. $strFilenameNeuron .' is not readable');

		$this->objViewContent = new \ANN\Views\View($strFilenameNetwork);

		$this->objViewLayer = new \ANN\Views\View($strFilenameLayer);
	
		$this->objViewNeuron = new \ANN\Views\View($strFilenameNeuron);
	}

	/**
	 * @uses getNeurons()
	 * @uses \ANN\Network::getNetworkInfo()
	 * @uses \ANN\Views\View::setArray()
	 * @uses \ANN\Views\View::setVar()
	 */

	protected function Content()
	{
		$arrNetworkInfo = $this->objNetwork->getNetworkInfo();
		
		$this->objViewContent->setArray($arrNetworkInfo);
		
		$this->objViewContent->setVar('neurons', $this->getNeurons());
		
		$intMemoryPeak = (int)memory_get_peak_usage(TRUE) / 1024;
		
		$this->objViewContent->setVar('memory_peak', $intMemoryPeak);
	}
	
	/**
	 * @return string
	 * @uses \ANN\Network::getNetworkInfo()
	 * @uses \ANN\Layer::getDelta()
	 * @uses \ANN\Layer::getNeurons()
	 * @uses \ANN\Views\View::getView()
	 * @uses \ANN\Views\View::resetView()
	 * @uses \ANN\Views\View::setIf()
	 * @uses \ANN\Views\View::setVar()
	 */

	protected function getNeurons()
	{
		$strReturn = '';
		
		$arrNetworkInfo = $this->objNetwork->getNetworkInfo();
		
		$intCountInputs = $arrNetworkInfo['network']['intCountInputs'];
		
		$strNeuron = '<h1>Inputs</h1>';
		
		$strNeuron .= $intCountInputs; 
		
		$this->objViewNeuron->setVar('neuron', $strNeuron);
		
		$strReturn .= $this->objViewNeuron->getView();
		
		$this->objViewNeuron->resetView();
		
		$arrHiddenLayers = $arrNetworkInfo['network']['arrHiddenLayers'];
		
		foreach($arrHiddenLayers as $intIndex => $objLayer)
		{
			$arrNeurons = $objLayer->getNeurons();
			
			$this->objViewNeuron->setIf('newline', TRUE);
				
			foreach($arrNeurons as $objNeuron)
			{
				$strNeuron = '<h1>Neuron</h1>';
				
				$strNeuron .= $objNeuron->getDelta();
							
				$this->objViewNeuron->setVar('neuron', $strNeuron);
				
				$strReturn .= $this->objViewNeuron->getView();
				
				$strReturn .= "\n";
				
				$this->objViewNeuron->resetView();
			}
		}
		
		/* @var $objOutputLayer \ANN\Layer */
		
		$objOutputLayer = $arrNetworkInfo['network']['objOutputLayer'];
		
		$arrOutputNeurons = $objOutputLayer->getNeurons();
		
		$this->objViewNeuron->setIf('newline', TRUE);
		
		foreach($arrOutputNeurons as $objNeuron)
		{
			$strNeuron = '<h1>Output</h1>';
			
			$strNeuron .= $objNeuron->getDelta();
			
			$this->objViewNeuron->setVar('neuron', $strNeuron);
			
			$strReturn .= $this->objViewNeuron->getView();
			
			$strReturn .= "\n";
			
			$this->objViewNeuron->resetView();
		}
		
		return $strReturn;
	}
}
