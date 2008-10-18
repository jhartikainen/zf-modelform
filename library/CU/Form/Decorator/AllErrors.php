<?php
/**
 * This helper can be used with forms or displaygroups
 * to display all child element errors. Supports PREPEND and
 * APPEND positions.
 *
 */
class CU_Form_Decorator_AllErrors extends Zend_Form_Decorator_Abstract
{
	public function render($content)
	{
		$element = $this->getElement();
		$view = $element->getView();
		if($view == null)
		    return $content;

		$errors = array();
		foreach($element->getElements() as $el)
			$errors = array_merge($errors, $el->getMessages());

		if(count($errors) == 0)
			return $content;

		$separator = $this->getSeparator();
		$placement = $this->getPlacement();
		$errors = $view->formErrors($errors, $this->getOptions()); 

		switch ($placement)
		{
			case self::APPEND:
				return $content . $separator . $errors;
			case self::PREPEND:
				return $errors . $separator . $content;
        }
    }
}
