<?php
/**
 * Plugin which handles rendering of master views
 */
class CU_Controller_Plugin_MasterView extends Zend_Controller_Plugin_Abstract
{
	/**
	 * Registered master views
	 * @var array
	 */
	protected $_masters = array();
	
	public function __construct()
	{
		Zend_Controller_Action_HelperBroker::getStaticHelper('MasterView')->setPlugin($this);
	}

	/**
	 * Append a master view into the stack
	 * @param string $script
	 */
	public function append($script)
	{
		$this->_masters[] = $script;
	}

	/**
	 * Render masters
	 */
	public function postDispatch(Zend_Controller_Request_Abstract $request)
	{
		$view = Zend_Controller_Action_HelperBroker::getStaticHelper('MasterView')->getView();
		$html = '';
		while(($master = array_shift($this->_masters)) != null)
		{
			$html = $view->render($master);
		}

		$this->getResponse()->setBody($html);

	}
}
