<?php
class CU_View_Helper_MasterView
{
	/**
	 * Master view
	 * @var CU_Controller_Action_Helper_MasterView
	 */
	protected $_master = null;
	
	public function __construct()
	{
		$this->_master = Zend_Controller_Action_HelperBroker::getStaticHelper('MasterView');
	}
	
	public function setView($view)
	{
		$this->_master->setView($view);
	}

	public function masterView($script = '')
	{
		$this->_master->append($script);	
	}
}
