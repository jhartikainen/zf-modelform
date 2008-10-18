<?php
/**
 * View helper for creating "blocks" in views
 */
class CU_View_Helper_Block extends Zend_View_Helper_Placeholder_Container_Abstract
{
	public function block($name = '')
	{
		if($name != '')
			$this->_name = $name;

		return $this;
	}

	/**
	 * Start block
	 */
	public function start()
	{
		$this->captureStart(Zend_View_Helper_Placeholder_Container_Abstract::APPEND, $this->_name);
		return '';
	}

	/**
	 * End block
	 * @return string the contents
	 */
	public function end()
	{
		//in case we have old data, it means
		//it's coming from a child view, and
		//we must preserve it
		$old = '';
		if(isset($this->{$this->_name}))
		{
			$old = $this->{$this->_name};
			$this->{$this->_name} = '';
		}

		$this->captureEnd();

		if($old != '')
		{
			$this->{$this->_name} = $old;
			return $old;
		}

		return $this->{$this->_name};
	}

	public function __toString()
	{
		return $this->{$this->_name};
	}
}
