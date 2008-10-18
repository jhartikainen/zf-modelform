<?php
/**
 * Generates JS validation rules for form fields
 *
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_Form_Decorator_JsValidation extends Zend_Form_Decorator_Abstract 
{
	/**
	 * The name of the form
	 * @var string
	 */
	protected $_formName;
	
	public function render($content)
	{
		$form = $this->getElement();
		$view = $form->getView();
		$this->_formName = $form->getName();
		
		if(!$this->_formName)
			$this->_formName = 'form';
		
		$script = "var Forms = Forms || { };\r\n"
				. "Forms." . $this->_formName . " = { };\r\n";

		foreach($form as $element)
		{
			$validators = $element->getValidators();
			
			if(count($validators) > 0)
				$script .= $this->_buildValidationRules($element);	
		}
		
		$view->inlineScript()->captureStart();
		echo $script;
		$view->inlineScript()->captureEnd();
		
		return $content;
	}

	/**
	 * Generate the JavaScript code for the validation rules
	 * @param Zend_Form_Element $element
	 * @return string
	 */
	protected function _buildValidationRules(Zend_Form_Element $element)
	{
		$name = $element->getName();
		$formName = $this->_formName;
		$validators = $element->getValidators();
		
		
		$rules = array();
		foreach($validators as $validator)
		{
			$class = get_class($validator);
			$params = $this->_buildValidatorParameters($class, $validator);
			$rules[] = "{ name: '$class', parameters: $params }";
		}
		
		if(count($rules) > 0)
			$script = "Forms." . $this->_formName . ".$name = [ " . implode(', ', $rules) . " ];\r\n";
		
		return $script;
	}
	
	/**
	 * Generate parameters for a validator rule
	 * @param string $class The name of the validator class
	 * @param Zend_Validate_Interface $validator the validator
	 * @return string
	 */
	protected function _buildValidatorParameters($class, Zend_Validate_Interface $validator)
	{
		$params = '{}';
		switch($class)
		{
			case 'Zend_Validate_Alnum':
			case 'Zend_Validate_Alpha':
				$params = '{ allowWhiteSpace: ' . (($validator->allowWhiteSpace) ? 'true' : 'false') . ' } ';
				break;
			
			case 'Zend_Validate_Between':
				$params = '{ min: ' . $validator->getMin() . ', max: ' . $validator->getMax() . ' } ';
				break;
		}
		
		return $params;
	}
}
