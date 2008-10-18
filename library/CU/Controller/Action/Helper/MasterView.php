<?php
/**
 * This class talks between controller, view and the plugin
 */
class CU_Controller_Action_Helper_MasterView extends Zend_Controller_Action_Helper_Abstract
{
	/**
	 * @var Zend_View
	 */
	protected $_view = null;

	/**
	 * @var CU_Controller_Plugin_MasterView
	 */
	protected $_plugin = null;

	/**
	 * Set the view
	 * @param Zend_View $view
	 */
	public function setView($view)
	{
		$this->_view = $view;
	}

	/**
	 * Set the plugin
	 * @param CU_Controller_Plugin_MasterView $plugin
	 */
	public function setPlugin($plugin)
	{
		$this->_plugin = $plugin;
	}
	
	/**
	 * Append a master view to the stack
	 * @param string $script
	 */
	public function append($script)
	{
		$this->_plugin->append($script);
	}

	/**
	 * Get the view
	 * @return Zend_View
	 */
	public function getView()
	{
		return $this->_view;
	}
}
