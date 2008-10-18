<?php
require_once('Zend/Auth/Adapter/Interface.php');

/**
 * Zend_Auth_Adapter for ADOdb
 */
class CU_Auth_Adapter_Adodb implements Zend_Auth_Adapter_Interface
{
	private $db;
	private $tableName;
	private $nameField;
	private $passwordField;

	private $name;
	private $password;

	private $resultRow;

	public function __construct($db)
	{
		$this->db = $db;
		$this->tableName = 'users';
		$this->nameField = 'nick'; 
		$this->passwordField = 'password';
	}

	public function setTableName($tableName)
	{
		$this->tableName = $tableName;
		return $this;
	}

	public function setIdentityColumn($nameField)
	{
		$this->nameField = $nameField;
		return $this;
	}

	public function setCredentialColumn($passwordField)
	{
		$this->passwordField = $passwordField;
		return $this;
	}

	public function setIdentity($name)
	{
		$this->name = $name;
		return $this;
	}

	public function setCredential($password)
	{
		$this->password = $password;
		return $this;
	}

	public function authenticate()
	{
		$sql = 'SELECT *
			FROM '.$this->tableName.'
			WHERE '.$this->nameField.' = ? AND
			      '.$this->passwordField.' = ?';

		$result = $this->db->GetAll($sql,array($this->name,md5($this->password)));
		$authResult = array(
				'code' => Zend_Auth_Result::FAILURE,
				'identity' => $this->name,
				'messages' => array()
				);

		if(count($result) == 0)
		{
			$authResult['code'] = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
			$authResult['messages'][] = 'Login failed.';
		}
		else if(count($result) > 1)
		{
			$authResult['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
			$authResult['messages'][] = 'Login failed.';
		}
		else
		{
			$authResult['code'] = Zend_Auth_Result::SUCCESS;
			$authResult['messages'][] = 'Login succesful.';
			$this->resultRow = $result[0];
		}


		return new Zend_Auth_Result($authResult['code'],$authResult['identity'],$authResult['messages']);
	}

	public function getResultRowObject($returnColumns = null, $omitColumns = null)
	{
		$rObj = new stdClass();

		if(null !== $returnColumns)
		{
			$availCols = array_keys($this->resultRow);

			foreach($returnColumns as $col)
			{
				if(in_array($col,$availCols))
					$rObj->{$col} = $this->resultRow[$col];
			}
		}
		else if($omitColumns !== null)
		{
			$omitColumns = (array)$omitColumns;
			foreach($this->resultRow as $key => $value)
			{
				if(!in_array($key,$omitColumns))
					$rObj->{$key} = $value;
			}
		}
		else
		{
			foreach($this->resultRow as $key => $value)
			{
				$rObj->{$key} = $value;
			}
		}

		return $rObj;
	}
}

?>
