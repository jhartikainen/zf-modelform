<?php
/**
 * A Zend_Db_Table based adapter for ModelForm
 *
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_ModelForm_Adapter_DbTable implements CU_ModelForm_Adapter_Interface
{
	protected $_table = null;
	protected $_record = null;

	public function setTable($table)
	{
		$this->_table = new $table;
	}

	public function getTable()
	{
		return $this->_table;
	}

	public function setRecord($record)
	{
		if(($record instanceof Zend_Db_Table_Row) === false)
			throw new RuntimeException('Record not a Zend_Db_Table_Row');

		$this->_record = $record;
	}

	public function getRecord()
	{
		return $this->_record;
	}

	public function getNewRecord()
	{
		return $this->_table->createRow();
	}

	public function saveRecord()
	{
		$this->_record->save();
	}

	public function getRecordValue($name)
	{
		return $this->_record->$name;
	}

	public function setRecordValue($name, $value)
	{
		$this->_record->$name = $value;
	}

	public function getColumns()
	{
		$info = $this->_table->info();

		$columns = array();
		foreach($info['metadata'] as $colData)
		{
			$column = array(
				'name' => $colData['COLUMN_NAME'],
				'type' => strtolower($colData['DATA_TYPE']),
				'notnull' => !$colData['NULLABLE'],
				'primary' => $colData['PRIMARY']
			);

			$columns[$colData['COLUMN_NAME']] = $column;
		}

		return $columns;
	}

	public function getRelations()
	{
		return array();
	}

	public function getRelationPkValue($name, $relation)
	{
		return null;
	}

	public function getRecordPkValue($record)
	{
		$info = $record->getTable()->info();

		if(count($info['primary']) < 1)
			throw new RuntimeException('Cannot work without a primary key');

		return $record->{$info['primary'][0]};
	}

	public function getManyRecords($name)
	{
		return array();
	}

	public function addManyRecord($name, $record)
	{

	}

	public function getOneRecords($relation)
	{
		return array();
	}

	public function deleteRecord($record)
	{

	}
}
