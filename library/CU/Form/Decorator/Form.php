<?php
class CU_Form_Decorator_Form extends Zend_Form_Decorator_Form 
{
	public function getOptions()
	{
		$this->setOption('onsubmit','return App.validate(this)');
		
		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
		$this->getElement()->getView()->headScript()->appendFile($baseUrl . '/js/Validator.js');
		
		return parent::getOptions();
	}
}
