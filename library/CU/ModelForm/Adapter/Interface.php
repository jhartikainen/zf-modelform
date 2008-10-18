<?php
interface CU_ModelForm_Adapter_Interface
{
	/**
	 * Return all columns as an array
	 *
	 * Array must contain 'type' for column type, 'notnull' true/false
	 * for the column's nullability, and 'values' for enum values, 'primary'
	 * true/false for primary key. Key = column's name
	 *
	 * @return array
	 */
	public function getColumns();

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
	public function getRelations();

	/**
	 * Return the value of the PK in the relation
	 * @param string $name name of the relation
	 * @param array $relation definition
	 * @return mixed
	 */
	public function getRelationPkValue($name, $relation);

	/**
	 * Return the value of a record's PK
	 * @param mixed $record
	 * @return mixed
	 */
	public function getRecordPkValue($record);

	/**
	 * Get the records for a many-relation
	 * @param string $name Name of the relation
	 * @return array
	 */
	public function getManyRecords($name);

	/**
	 * Get records for a one-relation
	 * @param array $relation the relation's definition
	 * @return array array of records
	 */
	public function getOneRecords($relation);

	/**
	 * Add a new record to a many-relation
	 * @param string $name name of the relation
	 * @param mixed $record the new record
	 */
	public function addManyRecord($name, $record);

	/**
	 * Delete a record
	 * @param mixed $record
	 */
	public function deleteRecord($record);

	/**
	 * Set the table
	 * @param string $table
	 */
	public function setTable($table);

	/**
	 * Returns the table
	 * @return mixed
	 */
	public function getTable();

	/**
	 * set the record
	 * @param mixed $instance
	 */
	public function setRecord($instance);

	/**
	 * Return the record
	 * @return mixed
	 */
	public function getRecord();

	/**
	 * Return a new instance of the record for this form
	 * @return mixed
	 */
	public function getNewRecord();

	/**
	 * Save the record
	 */
	public function saveRecord();

	/**
	 * Return the value of a column
	 * @param string $column name of the column
	 * @return string
	 */
	public function getRecordValue($column);

	/**
	 * Set the value of a column
	 * @param string $column column's name
	 * @param mixed $value
	 */
	public function setRecordValue($column, $value);


}
