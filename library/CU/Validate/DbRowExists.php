<?php
class CU_Validate_DbRowExists extends Zend_Validate_Abstract
{
	const NOT_FOUND = 'notFound';

	protected $_table;

	protected $_messageTemplates = array(
		self::NOT_FOUND => 'Value was not found'
	);

	public function __construct($table)
	{
		$this->_table = $table;
	}

	public function isValid($value)
	{
		$this->_setValue($value);
		$row = $this->_table->find($value);	

		if($row == false)
		{
			$this->_error();
			return false;
		}

		return true;
	}
}
