<?php
require_once 'smarty/libs/Smarty.class.php';
require_once 'smarty/libs/Smarty_Compiler.class.php';
require_once 'CU/View/Factory.php';

class CU_Smarty_Advanced extends Smarty
{
	private $_zendView;

	public function __construct()
	{
		parent::__construct();
		$this->compiler_class = 'CU_Smarty_Advanced_Compiler';
	}

	public function setZendView(Zend_View_Interface $view)
	{
		$this->_zendView = $view;
	}

	public function callViewHelper($name,$args)
	{
		$helper = $this->_zendView->getHelper($name);
		return call_user_func_array(array($helper,$name),$args);
	}
}

class CU_Smarty_Advanced_Compiler extends Smarty_Compiler
{
	private $_zendView;

	public function __construct()
	{
		parent::__construct();
		$this->_zendView = CU_ViewFactory::getInstance()->createView();
	}
	
	function _compile_compiler_tag($tagCommand, $tagArgs, &$output)
	{
		$found = parent::_compile_compiler_tag($tagCommand,$tagArgs,$output);

		if(!$found)
		{
			try 
			{
				//Check if helper exists and create output
				$helper = $this->_zendView->getHelper($tagCommand);	
				
				$helperArgs = array();
				//var_export($tagArgs);die();
				if($tagArgs !== null)
				{
					$params = explode(' ',$tagArgs);
					foreach($params as $p)
					{
						list($key,$value) = explode('=',$p,2);
						$section = '';
						
						if(strpos('.',$key) != -1)
							list($key,$section) = explode('.',$key);

						$value = $this->_parse_var_props($value);
						if($section == '')
						{
							if(array_key_exists($key,$helperArgs))
							{
								if(is_array($helperArgs[$key]))
									$helperArgs[$key][] = $value;
								else
									$helperArgs[$key] = array($helperArgs[$key],$value);

							}
							else
								$helperArgs[$key] = $value;
						}
						else
						{
							if(!is_array($helperArgs[$key]))
								$helperArgs[$key] = array();

							$helperArgs[$key][$section] = $value;
						}
					}
				}
				$output = "<?php echo \$this->callViewHelper('$tagCommand',array(".$this->_createParameterCode($helperArgs).")); ?>";
				$found = true;
			}
			catch(Zend_View_Exception $e)
			{
				$found = false;
			}
		}

		return $found;
	}

	private function _createParameterCode($params)
	{
		$code = '';
		
		$i = 1;
		$pCount = count($params);
		foreach($params as $p)
		{
			if(is_array($p))
				$code .= 'array('.$this->_createParameterCode($p).')';
			else
				$code .= $p;

			if($i != $pCount)
				$code .= ',';

			$i++;
		}

		return $code;
	}
}
?>
