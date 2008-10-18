<?php
/**
 * Factory object for creating views. Implements singleton
 */
class CU_View_Factory
{
	private $_config;
	private $_viewClass;

	private static $_instance;

	private function __construct()
	{
	}

	/**
	 * Returns an instance of the factory
	 *
	 * @returns CU_View_Factory
	 */
	public static function getInstance()
	{
		if(!CU_View_Factory::$_instance)
			CU_View_Factory::$_instance = new CU_View_Factory();

		return CU_View_Factory::$_instance;
	}

	/**
	 * Used to set the configuration options for created views
	 *
	 * @param array $config
	 * @returns CU_View_Factory provides fluid interface
	 */
	public function setConfig($config)
	{
		$this->_config = $config;
		return $this;
	}

	/**
	 * Used to set which view class to use
	 *
	 * @param string $viewClass Name of the view class to use, eg. Zend_View
	 * @returns CU_View_Factory provides fluid interface
	 */
	public function setViewClass($viewClass)
	{
		$this->_viewClass = $viewClass;
		return $this;
	}

	/**
	 * Creates a view object
	 *
	 * @returns Zend_View_Interface the new view object
	 */
	public function createView()
	{
		$view = new $this->_viewClass($this->_config);
		$view->assign('PATH',dirname($_SERVER['SCRIPT_NAME']));
		return $view;
	}
}
?>
