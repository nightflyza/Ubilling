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

final class DateInputs
{
	/**#@+
	 * @ignore
	 */
		
	/**
	 * @var string
	 */
	protected $strDate = null;
	
	/**
	 * @var string
	 */
	protected $strHolidaysFilename = 'Holidays.xml';
	
	/**
	 * @var SimpleXMLElement
	 */
	protected $objHolidaysXML = null;
	
	/**#@-*/
	
	/**
	 * @param string $strDate (Default: null)
	 */
		
	public function __construct($strDate = null)
	{
		$this->strDate = $strDate;	
	}
	
	/**
	 * @param string $strDate
	 */
	
	public function setDefaultDate($strDate)
	{
		$this->strDate = $strDate;	
	}
	
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 */
		
	public function getWeek($strDate = null)
	{
		$arrReturn = array();
		
		if(!$strDate)
			$strDate = $this->getDefaultDate();
		
		if($strDate)
		{
			$intWeek = date('W', strtotime($strDate));
		}
		else
		{
			$intWeek = date('W');
		}
		
		for($intIndex = 1; $intIndex <= 53; $intIndex++)
			$arrReturn[$intIndex] = ($intWeek == $intIndex) ? 1 : 0;
	
		return $arrReturn;
	}
		
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 */
		
	public function getWeekDay($strDate = null)
	{
		$arrReturn = array();
		
		if(!$strDate)
			$strDate = $this->getDefaultDate();
		
		if($strDate)
		{
			$intWeekDay = date('w', strtotime($strDate));
		}
		else
		{
			$intWeekDay = date('w');
		}
		
		if($intWeekDay == 0)
			$intWeekDay = 7;
		
		for($intIndex = 1; $intIndex <= 7; $intIndex++)
			$arrReturn[$intIndex] = ($intWeekDay == $intIndex) ? 1 : 0;
	
		return $arrReturn;
	}
		
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 */
		
	public function getYearDay($strDate = null)
	{
		$arrReturn = array();
		
		if(!$strDate)
			$strDate = $this->getDefaultDate();
		
		if($strDate)
		{
			$intYearDay = date('z', strtotime($strDate));
		}
		else
		{
			$intYearDay = date('z');
		}
		
		$intYearDay++;
		
		for($intIndex = 1; $intIndex <= 366; $intIndex++)
			$arrReturn[$intIndex] = ($intYearDay == $intIndex) ? 1 : 0;
	
		return $arrReturn;
	}
		
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 */
		
	public function getMonthWeek($strDate = null)
	{
		$arrReturn = array();
		
		if(!$strDate)
			$strDate = $this->getDefaultDate();
		
		if($strDate)
		{
			$intDay = date('d', strtotime($strDate));
		}
		else
		{
			$intDay = date('d');
		}
		
		$intWeek = (int)($intDay / 7);
		
		$intWeek++;
		
		for($intIndex = 1; $intIndex <= 5; $intIndex++)
			$arrReturn[$intIndex] = ($intWeek == $intIndex) ? 1 : 0;
	
		return $arrReturn;
	}
		
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 */
		
	public function getQuarter($strDate = null)
	{
		$arrReturn = array();
		
		if(!$strDate)
			$strDate = $this->getDefaultDate();
		
		if($strDate)
		{
			$intMonth = date('m', strtotime($strDate));
		}
		else
		{
			$intMonth = date('m');
		}
		
		$intQuarter = (int)($intMonth / 4);
		
		$intQuarter++;
		
		for($intIndex = 1; $intIndex <= 4; $intIndex++)
			$arrReturn[$intIndex] = ($intQuarter == $intIndex) ? 1 : 0;
	
		return $arrReturn;
	}
		
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 */
		
	public function getDaylight($strDate = null)
	{
		$arrReturn = array();
		
		if(!$strDate)
			$strDate = $this->getDefaultDate();
		
		if($strDate)
		{
			$floatSunrise = date_sunrise(strtotime($strDate), SUNFUNCS_RET_DOUBLE);
			
			$floatSunset = date_sunset(strtotime($strDate), SUNFUNCS_RET_DOUBLE);
		}
		else
		{
			$floatSunrise = date_sunrise(time(), SUNFUNCS_RET_DOUBLE);
			
			$floatSunset = date_sunset(time(), SUNFUNCS_RET_DOUBLE);
		}
		
		$floatDaylight = ($floatSunset - $floatSunrise) / 24;
		
		return array($floatDaylight);
	}
		
	/**
	 * @param string $strFilename
	 */
	
	public function setHolidaysFilename($strFilename)
	{
		$this->strHolidaysFilename = $strFilename;
	}
	
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 * @uses getDatesOfWeek()
	 * @uses isHoliday()
	 */
	
	public function getHolidaysInWeek($strDate = null)
	{
		if(!$strDate)
			$strDate = $this->getDefaultDate(TRUE);
		
		$arrDatesOfWeek = $this->getDatesOfWeek($strDate);
		
		foreach($arrDatesOfWeek as $intKey => $strDateOfWeek)
		{
			$arrReturn[$intKey] = ($this->isHoliday($strDateOfWeek)) ? 1 : 0;
		}
		
		return $arrReturn;
	}
	
	/**
	 * @param boolean $boolCurrentDate
	 * @return string
	 */
	
	protected function getDefaultDate($boolCurrentDate = FALSE)
	{
		if($boolCurrentDate && $this->strDate === null)
			return date('Y-m-d');
		
		return $this->strDate;
	}
	
	/**
	 * @param string $strDate (Default: null)
	 * @return boolean
	 * @uses getDefaultDate()
	 * @uses getHolidays()
	 */
	
	protected function isHoliday($strDate = null)
	{
		if(!$strDate)
			$strDate = $this->getDefaultDate(TRUE);
	
		$this->getHolidays();
			
		$arrDate = explode('-', $strDate);
		
		$strDay   = (int)$arrDate[2];
		
		$strMonth = (int)$arrDate[1];
		
		$strYear  = (int)$arrDate[0];
		
		if($this->objHolidaysXML instanceof SimpleXMLElement)
			foreach($this->objHolidaysXML->holiday as $objHoliday)
			{
				if($objHoliday->day == $strDay
				  && $objHoliday->month == $strMonth
				  && $objHoliday->year == $strYear)
						return TRUE;
			}
			
		if($this->objHolidaysXML instanceof SimpleXMLElement)
			foreach($this->objHolidaysXML->holiday as $objHoliday)
			{
				if($objHoliday->day == $strDay
				  && $objHoliday->month == $strMonth
				  && $objHoliday->year == 'any')
						return TRUE;
			}
	}
	
	/**
	 * @uses SimpleXMLElement::__construct()
	 * @throws Exception
	 */
	
	protected function getHolidays()
	{
		if($this->objHolidaysXML instanceof SimpleXMLElement)
			return;
	
		if(!is_file($this->strHolidaysFilename))
			throw new Exception('File '. $this->strHolidaysFilename .' does not exist');
			
		if(!is_readable($this->strHolidaysFilename))
			throw new Exception('File '. $this->strHolidaysFilename .' does not have read permission');
			
		$strXML = @file_get_contents($this->strHolidaysFilename);
		
		try
		{
			$this->objHolidaysXML = new SimpleXMLElement($strXML);
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	
		if(!($this->objHolidaysXML instanceof SimpleXMLElement))
			throw new Exception('XML Object cannot be created');
		
		if(!isset($this->objHolidaysXML->holiday))
			throw new Exception('Missing at least on holiday element');
	
		$intElementIndex = 0;
			
		foreach($this->objHolidaysXML->holiday as $objHoliday)
		{		
			$intElementIndex++;
			
			if(!isset($objHoliday->day))
				throw new Exception('Missing day element in holiday element '. $intElementIndex);
			
			if(!isset($objHoliday->month))
				throw new Exception('Missing month element in holiday element '. $intElementIndex);
			
			if(!isset($objHoliday->year))
				throw new Exception('Missing year element in holiday element '. $intElementIndex);
			
			if(!isset($objHoliday->country))
				throw new Exception('Missing country element in holiday element '. $intElementIndex);
			
			if(!isset($objHoliday->state))
				throw new Exception('Missing state element in holiday element '. $intElementIndex);
			
			if(!isset($objHoliday->description))
				throw new Exception('Missing descrition element in holiday element '. $intElementIndex);
		}
	}
	
	/**
	 * @param string $strDate (Default: null)
	 * @return array
	 * @uses getDefaultDate()
	 * @uses getFirstDayOfWeek()
	 * @uses getNextDayOfWeek()
	 */
	
	protected function getDatesOfWeek($strDate = null)
	{
		if(!$strDate)
			$strDate = $this->getDefaultDate(TRUE);
		
		$strDateMonday    = $this->getFirstDayOfWeek($strDate);
		
		$strDateTuesday   = $this->getNextDayOfWeek($strDateMonday, 1);
		
		$strDateWednesday = $this->getNextDayOfWeek($strDateMonday, 2);
		
		$strDateThursday  = $this->getNextDayOfWeek($strDateMonday, 3);
	
		$strDateFriday    = $this->getNextDayOfWeek($strDateMonday, 4);
		
		$strDateSaturday  = $this->getNextDayOfWeek($strDateMonday, 5);
		
		$strDateSunday    = $this->getNextDayOfWeek($strDateMonday, 6);
		
		$arrReturn = array(
			1 => $strDateMonday,
			2 => $strDateTuesday,
			3 => $strDateWednesday,
			4 => $strDateThursday,
			5 => $strDateFriday,
			6 => $strDateSaturday,
			7 => $strDateSunday
		);
		
		return $arrReturn;
	}
	
	/**
	 * @param string $strDate (Default: null)
	 * @return string
	 * @uses getDefaultDate()
	 */
	
	protected function getFirstDayOfWeek($strDate)
	{
		if(!$strDate)
			$strDate = $this->getDefaultDate(TRUE);
	
		$intDayOfWeek = date('w', strtotime($strDate));
		
		if($intDayOfWeek == 0)
			$intDayOfWeek = 7;
		
		$intUnixTime = strtotime($strDate) - ($intDayOfWeek - 1) * 86400;
		
		return date('Y-m-d', $intUnixTime);
	}
	
	/**
	 * @param string $strDate
	 * @param integer $intDayIncrement
	 * @return string
	 * @uses getDefaultDate()
	 */
	
	protected function getNextDayOfWeek($strDate, $intDayIncrement)
	{
		if(!$strDate)
			$strDate = $this->getDefaultDate(TRUE);
	
		$intUnixTime = strtotime($strDate) + $intDayIncrement * 86400;
		
		return date('Y-m-d', $intUnixTime);
	}
}
