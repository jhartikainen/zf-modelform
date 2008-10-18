<?php
class CU_ExampleForm extends CU_ModelForm
{
	//Use Article as the model
	protected $_model = 'Article';

	//Let's ignore columns views and page_id
	protected $_ignoreFields = array('views','page_id');

	//Let's use textarea as the field type for content
	protected $_fieldTypes = array('content' => 'textarea');

	//Labels for the fields
	protected $_fieldLabels = array(
			'name' => 'Article name',
			'content' => 'Article content'
	);
}
