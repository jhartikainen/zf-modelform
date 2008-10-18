<?php
require_once 'Zend/Controller/Action/Helper/Abstract.php';
require_once 'Zend/Controller/Action/HelperBroker.php';
require_once 'Zend/Session/Namespace.php';

/**
 * This helper tracks the user's browsing history
 *
 * @copyright 2008 Jani Hartikainen <www.codeutopia.net>
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_Controller_Action_Helper_History extends Zend_Controller_Action_Helper_Abstract 
{
	/**
	 * @var Zend_Session_Namespace
	 */
	private $_namespace;
	
	/**
	 * How many history URLs to track?
	 *
	 * @var int
	 */
	private $_trackAmount = 2;
	
	/**
	 * @param int $trackAmount [optional] How many history URLs to track
	 */
	public function __construct($trackAmount = 2)
	{
		$this->setTrackAmount($trackAmount);
		
		$this->_initSession();
	}
	
	/**
	 * Initialize the history from session
	 */
	private function _initSession()
	{
		$this->_namespace = new Zend_Session_Namespace('CU_Controller_Action_Helper_History');
		
		if(!is_array($this->_namespace->history))
		{
			$this->_namespace->history = array();
			
			if(!empty($_SERVER['HTTP_REFERER']))
				array_unshift($this->_namespace->history, $_SERVER['HTTP_REFERER']);
		}
		else	
			array_splice($this->_namespace->history, $this->_trackAmount);
	}
	
	public function preDispatch()
	{
		$urlHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Url');
		array_unshift($this->_namespace->history, $urlHelper->url());		
	}
	
	
	/**
	 * Set how many history URLs to track
	 *
	 * @param int $trackAmount
	 */
	public function setTrackAmount($trackAmount)
	{
		$this->_trackAmount = $trackAmount;
	}
	
	/**
	 * Redirects the browser back in history
	 *
	 * @param int $amount How many URLs to go back
	 */
	public function goBack($amount = 1)
	{
		Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')
		                                   ->setPrependBase(false)
		                                   ->gotoUrl($this->_namespace->history[$amount]);
	}

	/**
	 * Returns an URL from history
	 *
	 * @param int $amount How many URLs to go back
	 * @return string
	 */
	public function getPreviousUrl($amount = 1)
	{
		return $this->_namespace->history[$amount];
	}
	
	/**
	 * Return all previous URLs
	 *
	 * @return array
	 */
	public function getArray()
	{
		return $this->_namespace->history;
	}
	
	public function getName()
	{
		return 'History';
	}
}
