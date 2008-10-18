<?php
require_once 'Zend/Controller/Action/Helper/ViewRenderer.php';

class CU_Controller_Action_Helper_ViewRenderer_Layout extends Zend_Controller_Action_Helper_ViewRenderer
{
	protected $_layoutFileName = 'layout.tpl';

	public function setLayoutFileName($name)
	{
		$this->_layoutFileName = $name;
	}

	public function renderScript($script,$name=null)
	{
		if($name===null)
			$name = $this->getResponseSegment();

		$this->view->assign('LAYOUT_CONTENT',$script);

		$this->getResponse()->appendBody(
			$this->view->render($this->_layoutFileName),
			$name
		);
		$this->setNoRender();
	}

	public function getName()
	{
		return 'ViewRenderer';
	}
}
?>
