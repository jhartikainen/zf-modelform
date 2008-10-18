<?php
/**
 * Class for autogenerating forms based on Doctrine models
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_ModelForm extends Zend_Form
{
	/**
	 * PluginLoader for loading many relation forms
	 */
	const FORM = 'form';
	
	/**
	 * Reference to the model's table class
	 * @var Doctrine_Table
	 */
	protected $_table;
	
	/**
	 * Instance of the Zend_Form based form used
	 * @var Zend_Form
	 */
	protected $_form;

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
		'date' => 'text',
		'enum' => 'select'
	);

	/**
	 * Array of hooks that are called before saving the column
	 * @var array
	 */
	protected $_columnHooks = array();

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
	 * @var Doctrine_Record
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

		$this->_table = Doctrine::getTable($this->_model);

		parent::__construct($options);

		$this->_formLoader = new Zend_Loader_PluginLoader(array(
			'App_Form_Model' => 'App/Form/Model/'
		));


		$this->_preGenerate();
		$this->_generateForm();
		$this->_postGenerate();
	}
	
	public static function create(array $options = array())
	{
		$form = new CU_ModelForm($options);
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

	/**
	 * Override to provide custom post-save logic
	 */
	protected function _postSave($persist)
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
	public function setRecord($instance)
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
					$form->setRecord($rec);
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

	public function getRecord()
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
			$type = $this->_columnTypes[$definition['type']];
			if(isset($this->_fieldTypes[$name]))
				$type = $this->_fieldTypes[$name];

			$field = $this->createElement($type, $this->_fieldPrefix . $name);
			$label = $name;
			if(isset($this->_fieldLabels[$name]))
				$label = $this->_fieldLabels[$name];

			if(isset($this->_columnValidators[$definition['type']]))
				$field->addValidator($this->_columnValidators[$definition['type']]);

			if(isset($definition['notnull']) && $definition['notnull'] == true)
				$field->setRequired(true);
				
			$field->setLabel($label);

			if($type == 'select' && $definition['type'] == 'enum')
			{				
				foreach($definition['values'] as $text)
				{
					$field->addMultiOption($text, ucwords($text));
				}
			}

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
				$label = (string)Doctrine_Manager::connection()->getTable($alias);
				if(isset($this->_fieldLabels[$alias]))
					$label = $this->_fieldLabels[$alias];

				$field->setLabel($label);
				
				$definition = $this->_table->getColumnDefinition($relation->getLocal());
				if(isset($definition['notnull']) && $definition['notnull'] == true)
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
		if($record != null)
		{
			$idColumn = $record->getTable()->getIdentifier();
			return $relationAlias . '_' . $record->$idColumn;
		}

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

			foreach($this->getRecord()->$name as $rec)
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
		foreach($this->_table->getColumns() as $name => $definition)
		{
			if((isset($definition['primary']) && $definition['primary']) ||
				!isset($this->_columnTypes[$definition['type']]) || in_array($name, $this->_ignoreColumns))
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

		foreach($this->_table->getRelations() as $name => $definition)
		{
			if(in_array($definition->getLocal(), $this->_ignoreColumns) ||
				($this->_generateManyFields == false && $definition->getType() == Doctrine_Relation::MANY_AGGREGATE))
				continue;
				
			$relations[$name] = $definition;
		}

		return $relations;
	}

	/**
	 * Save the form data
	 * @param bool $persist Save to DB or not
	 * @return Doctrine_Record
	 */
	public function save($persist = true)
	{
		$inst = $this->getRecord();

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
				//Must use null if value=0 so integrity actions won't fail
				$val = $this->getUnfilteredValue($this->_fieldPrefix . $colName);
				if($val == 0)
					$val = null;

				if(isset($this->_columnHooks[$colName]))
					$val = call_user_func($this->_columnHooks[$colName], $val);

				$inst->set($colName, $val);
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

		$this->_postSave($persist);
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

