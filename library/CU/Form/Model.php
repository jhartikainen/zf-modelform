<?php
/**
 * Class for autogenerating forms based on Doctrine models
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_Form_Model extends Zend_Form
{
	const RELATION_ONE = 'one';
	const RELATION_MANY = 'many';

	protected static $_defaultAdapter = null;

	protected $_adapter = null;

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
		parent::__construct($options);

		if($this->_model == '')
			throw new Exception('No model defined');

		$this->_adapter = self::$_defaultAdapter;
		if($this->_adapter == null)
			$this->_adapter = new CU_Form_Model_Adapter_Doctrine();

		$this->_adapter->setTable($this->_model);

		$this->_formLoader = new Zend_Loader_PluginLoader(array(
			'App_Form_Model' => 'App/Form/Model/'
		));


		$this->_preGenerate();
		$this->_generateForm();
		$this->_postGenerate();
	}

	public static function setDefaultAdapter(CU_Form_Model_Adapter_Interface $adapter)
	{
		self::$_defaultAdapter = $adapter;
	}
	
	public function setOptions(array $options)
	{
		if(isset($options['model']))
			$this->_model = $options['model'];
		
		if(isset($options['ignoreColumns']))
			$this->ignoreColumns($options['ignoreColumns']);
		
		if(isset($options['columnTypes']))
			$this->setColumnTypes($options['columnTypes']);
			
		if(isset($options['fieldLabels']))
			$this->setFieldLabels($options['fieldLabels']);

		parent::setOptions($options);
	}
	
	public function setFieldLabels(array $labels)
	{
		$this->_fieldLabels = $labels;
	}
	
	public function setColumnTypes(array $types)
	{
		$this->_columnTypes = $types;
	}
	
	public function ignoreColumns(array $columns)
	{
		$this->_ignoreColumns = $columns;
	}
	
	public static function create(array $options = array())
	{
		$form = new CU_Form_Model($options);
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

	public function getTable()
	{
		return $this->_adapter->getTable();
	}

	/**
	 * Set the model instance for editing existing rows
	 * @param Doctrine_Record $instance
	 */
	public function setRecord($instance)
	{
		$this->_adapter->setRecord($instance);
		foreach($this->_adapter->getColumns() as $name => $definition)
		{
			if($this->_isIgnoredColumn($name, $definition))
				continue;

			$this->setDefault($this->getColumnElementName($name), $this->_adapter->getRecordValue($name));
		}

		foreach($this->_adapter->getRelations() as $name => $relation)
		{
			if($this->_isIgnoredRelation($relation))
				continue;

			switch($relation['type'])
			{
			case CU_Form_Model::RELATION_ONE:
				$this->setDefault($this->getRelationElementName($relation['alias']), $this->_adapter->getRelationPkValue($name, $relation));
				break;
			case CU_Form_Model::RELATION_MANY:
				$formClass = $this->_relationForms[$relation->getClass()];
				foreach($this->_adapter->getManyRecords($name) as $num => $rec)
				{
					$form = new $formClass;
					$form->setRecord($rec);
					$form->setIsArray(true);
					$form->removeDecorator('Form');
					$form->addElement('submit', $this->_getDeleteButtonName($name, $rec), array(
						'label' => 'Delete'
					));
					$label = $relation['class'];
					if(isset($this->_relationLabels[$relation['class']]))
						$label = $this->_relationLabels[$relation['class']];

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
		$inst = $this->_adapter->getRecord();
		if($inst == null)
		{
			$inst = $this->_adapter->getNewRecord();
			$this->_adapter->setRecord($inst);
		}

		return $inst;
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
		foreach($this->_adapter->getColumns() as $name => $definition)
		{
			if($this->_isIgnoredColumn($name, $definition))
				continue;

			$type = $this->_columnTypes[$definition['type']];
			if(isset($this->_fieldTypes[$name]))
				$type = $this->_fieldTypes[$name];

			$field = $this->createElement($type, $this->getColumnElementName($name));
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
		foreach($this->_adapter->getRelations() as $alias => $relation)
		{
			if($this->_isIgnoredRelation($relation))
				continue;

			$field = null;

			switch($relation['type'])
			{
			case CU_Form_Model::RELATION_ONE:
				$options = array('------');
				foreach($this->_adapter->getOneRecords($relation) as $row)
				{
					$options[$this->_adapter->getRecordPkValue($row)] = (string)$row;
				}

				$field = $this->createElement('select', $this->getRelationElementName($alias));
				$label = $relation['class'];
				if(isset($this->_fieldLabels[$alias]))
					$label = $this->_fieldLabels[$alias];

				$field->setLabel($label);
				
				if($relation['notnull'] == true)
					$field->addValidator(new CU_Validate_DbRowExists($table));

				$field->setMultiOptions($options);
				break;

			case Doctrine_Relation::MANY_AGGREGATE:
				$relCls = $relation['class'];
				$class = $this->getPluginLoader(self::FORM)->load($relCls);
				$this->_relationForms[$relCls] = $class;

				$label = $relCls;
				if(isset($this->_relationLabels[$relCls]))
					$label = $this->_relationLabels[$relCls];

				$field = $this->createElement('submit', $this->_getNewButtonName($alias), array(
					'label' => 'Add new '. $label
				));
				break;
			}

			if($field != null)
				$this->addElement($field);
		}
	}

	protected function _isIgnoredRelation($definition)
	{
			if(in_array($definition['local'], $this->_ignoreColumns) ||
				($this->_generateManyFields == false && $definition['type'] == CU_Form_Model::RELATION_MANY))
				return true;

			return false;
	}

	protected function _isIgnoredColumn($name, $definition)
	{
		if((isset($definition['primary']) && $definition['primary']) ||
			!isset($this->_columnTypes[$definition['type']]) || in_array($name, $this->_ignoreColumns))
			return true;

		return false;
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
		if($record != null)
			$val = $this->_adapter->getRecordPkValue($record);

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
			$val = $this->_adapter->getRecordPkValue($record);
			return $relationAlias . '_' . $val;
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

		foreach($this->_adapter->getRelations() as $name => $relation)
		{
			if($this->_isIgnoredRelation($relation))
				continue;

			if($relation['type'] != CU_Form_Model::RELATION_MANY)
				continue;

			if(isset($ndata[$this->_getNewButtonName($name)]) || isset($ndata[$this->_getFormName($name)]))
			{
				if(isset($ndata[$this->_getFormName($name)]) && 
					isset($ndata[$this->_getFormName($name)][$this->_getDeleteButtonName($name)]))
				{
					return false;
				}

				$cls = $this->_relationForms[$relation['class']];
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

			$record = $this->getRecord();
			foreach($this->_adapter->getOneRecords($record, $name) as $rec)
			{
				$formName = $this->_getFormName($name, $rec);
				if(isset($ndata[$formName]) && isset($ndata[$formName][$this->_getDeleteButtonName($name, $rec)]))
				{
					$this->removeSubForm($formName);
					$this->_adapter->deleteRecord($rec);
					return false;
				}
			}
		}
		
		return parent::isValid($data);
	}

	/**
	 * Return name of element for column
	 * @param string $name Name of column
	 * @return string
	 */
	public function getColumnElementName($name)
	{
		return $this->_fieldPrefix . $name;
	}

	/**
	 * Return name of element for relation
	 * @param string $name Alias of the relation
	 * @return string
	 */
	public function getRelationElementName($name)
	{
		$elName = $this->_fieldPrefix . $relation['local'] . '-' . $relation['id'];

		return $elName;
	}

	/**
	 * Return element for column
	 * @param string $name Name of column
	 * @return Zend_Form_Element
	 */
	public function getElementForColumn($name)
	{
		return $this->getElement($this->getColumnElementName($name));
	}

	/**
	 * Return element for relation
	 * @param string $name Alias of the relation
	 * @return Zend_Form_Element
	 */
	public function getElementForRelation($relation)
	{
		return $this->getElement($this->getRelationElementName($relation));
	}

	/**
	 * Save the form data
	 * @param bool $persist Save to DB or not
	 * @return Doctrine_Record
	 */
	public function save($persist = true)
	{
		$inst = $this->getRecord();

		foreach($this->_adapter->getColumns() as $name => $definition)
		{
			if($this->_isIgnoredColumn($name, $definition))
				continue;

			$value = $this->getUnfilteredValue($this->getColumnElementName($name));
			$this->_adapter->setRecordValue($name, $value);
		}

		foreach($this->_adapter->getRelations() as $name => $relation)
		{
			if($this->_isIgnoredRelation($relation))
				continue;

			$colName = $relation['local'];
			switch($relation['type'])
			{
			case CU_Form_Model::RELATION_ONE:
				//Must use null if value=0 so integrity actions won't fail
				$val = $this->getUnfilteredValue($this->getRelationElementName($relation));
				if($val == 0)
					$val = null;

				if(isset($this->_columnHooks[$colName]))
					$val = call_user_func($this->_columnHooks[$colName], $val);

				$this->_adapter->setRecordValue($colName, $val);
				break;

			case CU_Form_Model::RELATION_MANY:
				$idColumn = $relation['id'];
				foreach($this->_adapter->getManyRecords($name) as $rec)
				{
					$subForm = $this->getSubForm($name . '_' . $this->_adapter->getRecordPkValue($rec));

					//Should get saved along with the main instance later
					$subForm->save(false);
				}

				$subForm = $this->getSubForm($name . '_new_form');
				if($subForm)
				{
					$newRec = $subForm->save(false);
					$this->_adapter->addManyRecord($name, $newRec);
				}
						
				break;
			}
		}

		if($persist)
			$this->_adapter->saveRecord();

		foreach($this->getSubForms() as $subForm)
			$subForm->save($persist);

		$this->_postSave($persist);
		return $inst;
	}
}


