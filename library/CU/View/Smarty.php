<?php
require_once 'smarty/libs/Smarty.class.php';
require_once 'Zend/View.php';

class CU_View_Smarty extends Zend_View_Abstract
{
	protected $_smarty;

	public function __construct($config = array())
	{
		if(isset($config['smartyClass']))
			$this->_smarty = new $config['smartyClass'];
		else
			$this->_smarty = new Smarty();

		if(!isset($config['compileDir']))
			throw new Exception('compileDir is not set for '.get_class($this));
		else
			$this->_smarty->compile_dir = $config['compileDir'];

		if(isset($config['configDir']))
			$this->_smarty->config_dir = $config['configDir'];

		if(isset($config['pluginDir']))
			$this->_smarty->plugin_dir[] = $config['pluginDir'];

		parent::__construct($config);
	}


	public function getEngine()
	{
		return $this->_smarty;
	}

	public function __set($key,$val)
	{
		$this->_smarty->assign($key,$val);
	}

	public function __get($key)
	{
		$var = $this->_smarty->get_template_vars($key);
		if($var === null)
			return parent::__get($key);

		return $var;
	}

	public function __isset($key)
	{
		$var = $this->_smarty->get_template_vars($key);
		if($var)
			return true;
		
		return false;
	}

	public function __unset($key)
	{
		$this->_smarty->clear_assign($key);
	}

	public function assign($spec,$value = null)
	{
		if($value === null)
			$this->_smarty->assign($spec);
		else
			$this->_smarty->assign($spec,$value);
	}

	public function getVars()
	{
		return $this->_smarty->get_template_vars();
	}

	public function clearVars()
	{
		$this->_smarty->clear_all_assign();
	}

	protected function _run()
	{
		$this->strictVars(true);

		$this->_smarty->assign_by_ref('this',$this);

		$templateDirs = $this->getScriptPaths();

		$file = substr(func_get_arg(0),strlen($templateDirs[0]));
		$this->_smarty->template_dir = $templateDirs[0];
		$this->_smarty->compile_id = $templateDirs[0];
		echo $this->_smarty->fetch($file);
	}
}
?>
