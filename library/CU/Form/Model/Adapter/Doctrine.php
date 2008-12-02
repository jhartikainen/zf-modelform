<?php
class CU_Form_Model_Adapter_Doctrine implements CU_Form_Model_Adapter_Interface
{
	protected $_table = null;
	protected $_record = null;
	protected $_model = '';

	public function setTable($table)
	{
		$this->_table = Doctrine::getTable($table);
		$this->_model = $table;
	}

	public function getTable()
	{
		return $this->_table;
	}

	public function setRecord($record)
	{
		if(($record instanceof Doctrine_Record) === false)
			throw new InvalidArgumentException('Record not a Doctrine_Record');

		$this->_record = $record;

	}

	public function getRecord()
	{
		return $this->_record;
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

	/**
	 * Return all columns as an array
	 *
	 * Array must contain 'type' for column type, 'notnull' true/false
	 * for the column's nullability, and 'values' for enum values, 'primary'
	 * true/false for primary key. Key = column's name
	 *
	 * @return array
	 */
	public function getColumns()
	{
		$data = $this->_table->getColumns();
		$cols = array();
		foreach($data as $name => $d)
		{
			$cols[$name] = array(
				'type' => $d['type'],
				'notnull' => (isset($d['notnull'])) ? $d['notnull'] : false,
				'values' => (isset($d['values'])) ? $d['values'] : array(),
				'primary' => (isset($d['primary'])) ? $d['primary'] : false
			);
		}

		return $cols;
	}

	/**
	 * Return relations as an array
	 *
	 * Array must contain 'type' for relation type, 'id' for the name
	 * of the PK column of the related table, 'class' for the related class
	 * name, 'notnull' for nullability. 'local' for the name of the local column
	 * Key must be the alias of the relation column
	 *
	 * @return array
	 */
	public function getRelations()
	{
		if(defined('Doctrine_Relation::ONE_AGGREGATE'))
			$oneType = Doctrine_Relation::ONE_AGGREGATE;
		else
			$oneType = Doctrine_Relation::ONE;

		$rels = $this->_table->getRelations();
		$relations = array();

		foreach($rels as $rel)
		{
			$relation = array();


			if($rel->getType() == $oneType)
				$relation['type'] = CU_Form_Model::RELATION_ONE;
			else
				$relation['type'] = CU_Form_Model::RELATION_MANY;	

			$relation['id'] = $rel->getTable()->getIdentifier();
			$relation['alias'] = $rel->getAlias();
			$relation['class'] = $rel->getClass();
			$relation['local'] = $rel->getLocal();

			$definition = $this->_table->getColumnDefinition($rel->getLocal());
			$relation['notnull'] = (isset($definition['notnull']))
			                     ? $definition['notnull']
								 : false;

			$relations[$rel->getAlias()] = $relation;
		}

		return $relations;
	}

	/**
	 * Return 
	 */
	public function getRelationPkValue($name, $relation)
	{
		return $this->_record->$name->{$relation['id']};
	}

	/**
	 * Return the value of a record's primary key
	 * @param Doctrine_Record $record
	 * @return mixed
	 */
	public function getRecordPkValue($record)
	{
		$col = $record->getTable()->getIdentifier();
		return $record->$col;
	}

	/**
	 * Get the records for a many-relation
	 * @param string $name Name of the relation
	 * @return array
	 */
	public function getManyRecords($name)
	{
		return $this->_record->$name;
	}

	public function addManyRecord($name, $record)
	{
		$this->_record->{$name}[] = $record;
	}

	public function getOneRecords($relation)
	{
		return Doctrine::getTable($relation['class'])->findAll();
	}

	public function deleteRecord($record)
	{
		$record->delete();
	}

	public function getNewRecord()
	{
		return new $this->_model;
	}
}
