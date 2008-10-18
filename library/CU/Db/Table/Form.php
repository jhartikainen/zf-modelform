<?php
/**
 * Class for autogenerating forms based on Doctrine models
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_Db_Table_Form extends Zend_Form
{
	/**
	 * PluginLoader for loading many relation forms
	 */
	const FORM = 'form';
	
	/**
	 * Reference to the model's table class
	 * @var Zend_Db_Table
	 */
	protected $_table;

	/**
	 * Table's info
	 * @var array
	 */
	protected $_tableInfo = array();

	/**
	 * Which Zend_Form element types are associated with which doctrine type?
	 * @var array
	 */
	protected $_columnTypes = array(
		'integer' => 'text',
		'decimal' => 'text',
		'float' => 'text',
		'string' => 'text',
		'varchar' => 'text',
		'boolean' => 'checkbox',
		'timestamp' => 'text',
		'time' => 'text',
		'date' => 'text'
	);

	/**
	 * Default validators for doctrine column types
	 * @var array
	 */
	protected $_columnValidators = array(
		'integer' => 'int',
		'float' => 'float',
		'double' => 'float'
	);

	/**
	 * Prefix fields with this
	 * @var string
	 */	
	protected $_fieldPrefix = 'f_';

	/**
	 * Column names listed in this array will not be shown in the form
	 * @var array
	 */
	protected $_ignoreColumns = array();

	/**
	 * Whether or not to generate fields for many parts of m2o and m2m relations
	 * @var bool
	 */
	protected $_generateManyFields = false;

	/**
	 * Use this to override field types for columns. key = column, value = field type
	 * @var array
	 */
	protected $_fieldTypes = array();

	/**
	 * Field labels. key = column name, value = label
	 * @var array
	 */
	protected $_fieldLabels = array();

	/**
	 * Labels to use with many to many relations.
	 * key = related class name, value = label
	 * @var array
	 */
	protected $_relationLabels = array();

	/**
	 * Name of the model class
	 * @var string
	 */
	protected $_model = '';

	/**
	 * Model instance for editing existing models
	 * @var Zend_Db_Table_Row
	 */
	protected $_instance = null;

	/**
	 * Form PluginLoader
	 * @var Zend_Loader_PluginLoader
	 */
	protected $_formLoader = null;

	/**
	 * Stores form class names for many-relations
	 * @var array
	 */
	protected $_relationForms = array();

	/**
	 * @param array $options Options to pass to the Zend_Form constructor
	 */
	public function __construct($options = null)
	{
		if($this->_model == '')
			throw new Exception('No model defined');

		$this->_table = new $this->_model;
		$this->_tableInfo = $this->_table->info();

		parent::__construct($options);

		$this->_formLoader = new Zend_Loader_PluginLoader(array(
			'App_Form_Model' => 'App/Form/Model/'
		));


		$this->_preGenerate();
		$this->_generateForm();
		$this->_postGenerate();
	}
	
	/**
	 * Override to provide custom pre-form generation logic
	 */
	protected function _preGenerate()
	{
	}

	/**
	 * Override to provide custom post-form generation logic
	 */
	protected function _postGenerate()
	{
	}

	public function getPluginLoader($type = null)
	{
		if($type == self::FORM)
			return $this->_formLoader;

		return parent::getPluginLoader($type);
	}

	/**
	 * Set the model instance for editing existing rows
	 * @param Doctrine_Record $instance
	 */
	public function setInstance($instance)
	{
		$this->_instance = $instance;
		foreach($this->_getColumns() as $name => $definition)
		{
			$this->setDefault($this->_fieldPrefix . $name, $this->_instance->$name);
		}

		foreach($this->_getRelations() as $name => $relation)
		{
			switch($relation->getType())
			{
			case Doctrine_Relation::ONE_AGGREGATE:
				$idColumn = $relation->getTable()->getIdentifier();
				$this->setDefault($this->_fieldPrefix . $relation->getLocal(), $this->_instance->$name->$idColumn);
				break;
			case Doctrine_Relation::MANY_AGGREGATE:
				$formClass = $this->_relationForms[$relation->getClass()];
				foreach($this->_instance->$name as $num => $rec)
				{
					$form = new $formClass;
					$form->setInstance($rec);
					$form->setIsArray(true);
					$form->removeDecorator('Form');
					$form->addElement('submit', $this->_getDeleteButtonName($name, $rec), array(
						'label' => 'Delete'
					));
					$label = $relation->getClass();
					if(isset($this->_relationLabels[$relation->getClass()]))
						$label = $this->_relationLabels[$relation->getClass()];

					$form->setLegend($label . ' ' . ($num + 1))
					     ->addDecorator('Fieldset');
					$this->addSubForm($form, $this->_getFormName($name, $rec));
				}
				break;
			}
		}
	}

	public function getInstance()
	{
		return ($this->_instance != null) ? $this->_instance : new $this->_model;
	}

	/**
	 * Generates the form
	 */
	protected function _generateForm()
	{
		$this->_columnsToFields();	
		$this->_relationsToFields();
	}

	/**
	 * Parses columns to fields
	 */
	protected function _columnsToFields()
	{
		foreach($this->_getColumns() as $name => $definition)
		{
			$type = $this->_columnTypes[strtolower($definition['DATA_TYPE'])];
			if(isset($this->_fieldTypes[$name]))
				$type = $this->_fieldTypes[$name];

			$field = $this->createElement($type, $this->_fieldPrefix . $name);
			$label = $name;
			if(isset($this->_fieldLabels[$name]))
				$label = $this->_fieldLabels[$name];

			if(isset($this->_columnValidators[strtolower($definition['DATA_TYPE'])]))
				$field->addValidator($this->_columnValidators[strtolower($definition['DATA_TYPE'])]);

			if(isset($definition['NULLABLE']) && $definition['NULLABLE'] == false)
				$field->setRequired(true);
				
			$field->setLabel($label);

			$this->addElement($field);
		}
	}

	/**
	 * Parses relations to fields
	 */
	protected function _relationsToFields()
	{
		foreach($this->_getRelations() as $alias => $relation)
		{
			$field = null;

			switch($relation->getType())
			{
			case Doctrine_Relation::ONE_AGGREGATE:
				$table = $relation->getTable();
				$idColumn = $table->getIdentifier();

				$options = array('------');
				foreach($table->findAll() as $row)
				{
					$options[$row->$idColumn] = (string)$row;
				}

				$field = $this->createElement('select', $this->_fieldPrefix . $relation->getLocal());
				$label = $alias;
				if(isset($this->_fieldLabels[$alias]))
					$label = $this->_fieldLabels[$alias];

				$field->setLabel($label);
				$field->addValidator(new CU_Validate_DbRowExists($table));

				$field->setMultiOptions($options);
				break;

			case Doctrine_Relation::MANY_AGGREGATE:
				$class = $this->getPluginLoader(self::FORM)->load($relation->getClass());
				$this->_relationForms[$relation->getClass()] = $class;

				$label = $relation->getClass();
				if(isset($this->_relationLabels[$relation->getClass()]))
					$label = $this->_relationLabels[$relation->getClass()];

				$field = $this->createElement('submit', $this->_getNewButtonName($alias), array(
					'label' => 'Add new '. $label
				));
				break;
			}

			if($field != null)
				$this->addElement($field);
		}
	}

	/**
	 * Returns the name of the new button field for relation alias
	 * @param string $relationAlias alias of the relation
	 * @return string name of the new button
	 */
	protected function _getNewButtonName($relationAlias)
	{
		return $relationAlias . '_new_button';
	}

	/**
	 * Returns the name of the delete button field for relation alias
	 * @param string $relationAlias alias of the relation
	 * @param Doctrine_Record $record if deleting existing records
	 * @return string name of the new button
	 */
	protected function _getDeleteButtonName($relationAlias, Doctrine_Record $record = null)
	{
		$val = 'new';
		$idColumn = $record->getTable()->getIdentifier();
		if($record != null)
			$val = $record->$idColumn;

		return $relationAlias . '_' . $val . '_delete';
	}
	/**
	 * Returns the new form name for relation alias
	 * @param string $relationAlias alias of the relation
	 * @param Doctrine_Record $record if editing existing records
	 * @return string name of the new form
	 */
	protected function _getFormName($relationAlias, Doctrine_Record $record = null)
	{
		$idColumn = $record->getTable()->getIdentifier();
		if($record != null)
			return $relationAlias . '_' . $record->$idColumn;

		return $relationAlias . '_new_form';
	}

	public function isValid($data)
	{
		$ndata = $data;
		if ($this->isArray()) 
		{
			$key = $this->_getArrayName($this->getElementsBelongTo());
			if (isset($data[$key])) 
			{
				$ndata = $data[$key];
			}
	    	}

		foreach($this->_getRelations() as $name => $relation)
		{
			if($relation->getType() != Doctrine_Relation::MANY_AGGREGATE)
				continue;

			if(isset($ndata[$this->_getNewButtonName($name)]) || isset($ndata[$this->_getFormName($name)]))
			{
				if(isset($ndata[$this->_getFormName($name)]) && 
					isset($ndata[$this->_getFormName($name)][$this->_getDeleteButtonName($name)]))
				{
					return false;
				}

				$cls = $this->_relationForms[$relation->getClass()];
				$form = new $cls;
				$form->setIsArray(true);
				$form->removeDecorator('Form');
				$form->addElement('submit',$this->_getDeleteButtonName($name), array(
					'label' => 'Delete'
				));
				$this->addSubForm($form, $this->_getFormName($name));
				if(isset($ndata[$this->_getNewButtonName($name)]))
					return false;
			}

			foreach($this->getInstance()->$name as $rec)
			{
				$formName = $this->_getFormName($name, $rec);
				if(isset($ndata[$formName]) && isset($ndata[$formName][$this->_getDeleteButtonName($name, $rec)]))
				{
					$this->removeSubForm($formName);
					$rec->delete();
					return false;
				}
			}
		}
		
		return parent::isValid($data);
	}

	/**
	 * Get unignored columns
	 * @return array
	 */
	protected function _getColumns()
	{
		$columns = array();
		foreach($this->_tableInfo['cols'] as $name)
		{
			$definition = $this->_tableInfo['metadata'][$name];
			
			if((isset($definition['PRIMARY']) && $definition['PRIMARY']) ||
				!isset($this->_columnTypes[strtolower($definition['DATA_TYPE'])]) || in_array($name, $this->_ignoreColumns))
				continue;

			$columns[$name] = $definition;
		}

		return $columns;
	}

	/**
	 * Returns all un-ignored relations
	 * @return array
	 */
	protected function _getRelations()
	{
		$relations = array();
		/*
		foreach($this->_table->getRelations() as $name => $definition)
		{
			if(in_array($definition->getLocal(), $this->_ignoreColumns) ||
				($this->_generateManyFields == false && $definition->getType() == Doctrine_Relation::MANY_AGGREGATE))
				continue;
				
			$relations[$name] = $definition;
		}
		*/
		return $relations;
	}

	/**
	 * Save the form data
	 * @param bool $persist Save to DB or not
	 * @return Doctrine_Record
	 */
	public function save($persist = true)
	{
		$inst = $this->getInstance();

		foreach($this->_getColumns() as $name => $definition)
		{
			$inst->$name = $this->_doctrineizeValue($this->getUnfilteredValue($this->_fieldPrefix . $name), $definition['type']);
		}

		foreach($this->_getRelations() as $name => $relation)
		{
			$colName = $relation->getLocal();
			switch($relation->getType())
			{
			case Doctrine_Relation::ONE_AGGREGATE:
				$inst->set($colName, $this->getUnfilteredValue($this->_fieldPrefix . $colName));
				break;

			case Doctrine_Relation::MANY_AGGREGATE:
				$idColumn = $relation->getTable()->getIdentifier();
				foreach($inst->$name as $rec)
				{
					$subForm = $this->getSubForm($name . '_' . $rec->$idColumn);

					//Should get saved along with the main instance later
					$subForm->save(false);
				}

				$subForm = $this->getSubForm($name . '_new_form');
				if($subForm)
				{
					$newRec = $subForm->save(false);
					$inst->{$name}[] = $newRec;
				}
						
				break;
			}
		}

		if($persist)
			$inst->save();

		foreach($this->getSubForms() as $subForm)
			$subForm->save($persist);


		return $inst;
	}

	/**
	 * Correct form value types for Doctrine
	 * @param string $value value
	 * @param string $type column type
	 * @return mixed
	 */
	protected function _doctrineizeValue($value, $type)
	{
		switch($type)
		{
			case 'boolean':
				return (boolean)$value;
				break;
			default:
				return $value;
				break;
		}
		trigger_error('This line should never run', E_USER_ERROR);	
	}
}

