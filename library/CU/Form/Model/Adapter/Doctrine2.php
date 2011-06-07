<?php
use Doctrine\ORM\EntityManager;

class CU_Form_Model_Adapter_Doctrine2 implements CU_Form_Model_Adapter_Interface {
    /**
     * @var mixed
     */
    private $record;

    /**
     * @var string
     */
    private $table;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $metadata;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * Add a new record to a many-relation
     * @param string $name name of the relation
     * @param mixed $record the new record
     */
    public function addManyRecord($name, $record) {
        // TODO: Implement addManyRecord() method.
    }

    /**
     * Delete a record
     * @param mixed $record
     */
    public function deleteRecord($record) {
        // TODO: Implement deleteRecord() method.
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
    public function getColumns() {
        $this->metadata = $this->em->getClassMetadata($this->table);

        $columns = array();
        foreach($this->metadata->fieldMappings as $name => $data) {
            $columns[$name] = array(
                'name' => $name,
                'type' => $data['type'],
                'notnull' => !$data['nullable'],
                'primary' => isset($data['id']) && $data['id'] === true
            );
        }

        return $columns;
    }

    /**
     * Get the records for a many-relation
     * @param string $name Name of the relation
     * @return array
     */
    public function getManyRecords($name) {
        // TODO: Implement getManyRecords() method.
    }

    /**
     * Return a new instance of the record for this form
     * @return mixed
     */
    public function getNewRecord() {
        // TODO: Implement getNewRecord() method.
    }

    /**
     * Get records for a one-relation
     * @param array $relation the relation's definition
     * @return array array of records
     */
    public function getOneRecords($relation) {
        // TODO: Implement getOneRecords() method.
    }

    /**
     * Return the record
     * @return mixed|null Null on failure
     */
    public function getRecord() {
        return $this->record;
    }

    /**
     * Return the value of a record's unique identifier
     * @param mixed $record
     * @return mixed
     */
    public function getRecordIdentifier($record) {
        // TODO: Implement getRecordIdentifier() method.
    }

    /**
     * Return the value of a column
     * @param string $column name of the column
     * @return string
     */
    public function getRecordValue($column) {
        return $this->metadata->getFieldValue($this->record, $column);
    }

    /**
     * Return a related object, or null if not found
     * @param mixed $record the record where to look at
     * @param string $name name of the relation
     * @return mixed
     */
    public function getRelatedRecord($record, $name) {
        // TODO: Implement getRelatedRecord() method.
    }

    /**
     * Return relations as an array
     *
     * Array must contain 'type' for relation type, 'id' for the name
     * of the PK column of the related table, 'model' for the related model
     * name, 'notnull' for nullability. 'local' for the name of the local column
     * Key must be the alias of the relation column
     *
     * @return array
     */
    public function getRelations() {
        return array();
    }

    /**
     * Returns the table
     * @return mixed
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * Save the record
     */
    public function saveRecord() {
        $this->em->persist($this->record);
    }

    /**
     * set the record
     * @param mixed $instance
     */
    public function setRecord($instance) {
        $this->record = $instance;
    }

    /**
     * Set the value of a column
     * @param string $column column's name
     * @param mixed $value
     */
    public function setRecordValue($column, $value) {
        $this->metadata->setFieldValue($this->record, $column, $value);
    }

    /**
     * Set the table
     * @param string $table
     */
    public function setTable($table) {
        $this->table = $table;
    }
}