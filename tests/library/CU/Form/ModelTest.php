<?php
require_once 'TestHelper.php';

class CU_Form_ModelTest extends PHPUnit_Framework_TestCase
{
	private $_adapter;

	private $_columns = array();
	private $_relations = array();

	public function setUp()
	{
		parent::setUp();

		$this->_columns['User'] = array(
			'id' => array(
				'type' => 'integer',
				'notnull' => true,
				'values' => array(),
				'primary' => true
			),
			'login' => array(
				'type' => 'string',
				'notnull' => true,
				'values' => array(),
				'primary' => false
			),
			'password' => array(
				'type' => 'string',
				'notnull' => true,
				'values' => array(),
				'primary' => false
			)
		);

		$this->_relations['User'] = array();

		$this->_columns['Comment'] = array(
			'id' => array(
				'type' => 'integer',
				'notnull' => true,
				'values' => array(),
				'primary' => true
			),
			'sender' => array(
				'type' => 'string',
				'notnull' => true,
				'values' => array(),
				'primary' => false
			),
			'article_id' => array(
				'type' => 'integer',
				'notnull' => true,
				'values' => array(),
				'primary' => false
			)
		);

		$this->_relations['Comment'] = array(
			'Article' => array(
				'type' => CU_Form_Model::RELATION_ONE,
				'id' => 'id',
				'model' => 'Article',
				'notnull' => true,
				'local' => 'article_id'
			)
		);

		$this->_columns['Article'] = array(
			'id' => array(
				'type' => 'integer',
				'notnull' => true,
				'values' => array(),
				'primary' => true
			),
			'name' => array(
				'type' => 'string',
				'notnull' => true,
				'values' => array(),
				'primary' => false
			)
		);

		$this->_relations['Article'] = array(
			'Article' => array(
				'type' => CU_Form_Model::RELATION_MANY,
				'id' => 'id',
				'model' => 'Article',
				'notnull' => false,
				'local' => 'article_id'
			)
		);
	}

	public function testNoModelFails()
	{
		try
		{
			$form = new CU_Form_Model();
		}
		catch(Exception $e)
		{
			return;
		}	

		$this->fail();
	}

	private function _initAdapter($table)
	{
		$this->_adapter = $this->getMock('CU_Form_Model_Adapter_Interface');

		$this->_adapter->expects($this->any())
		               ->method('setTable')
					   ->with($this->equalTo($table));

		//Should be called on $form->getTable()
		$this->_adapter->expects($this->any())
			           ->method('getTable')
                       ->will($this->returnValue($table));

		$this->_adapter->expects($this->any())
		               ->method('getColumns')
					   ->will($this->returnValue($this->_columns[$table]));

		$this->_adapter->expects($this->any())
		               ->method('getRelations')
					   ->will($this->returnValue($this->_relations[$table]));
	}

	public function testTableLoading()
	{
		$this->_initAdapter('User');	

		$form = new CU_Form_Model(array(
			'model' => 'User',
			'adapter' => $this->_adapter
		));

		$this->assertEquals('User', $form->getTable());
	}

	public function testColumnIgnoring()
	{
		$this->_initAdapter('User');

		$form = new CU_Form_Model(array(
			'model' => 'User',
			'adapter' => $this->_adapter,
			'ignoreColumns' => array('login')
		));

		$this->assertNull($form->getElementForColumn('login'));
		$this->assertNotNull($form->getElementForColumn('password'));
	}

	public function testPrimaryKeyIgnored()
	{
		$this->_initAdapter('User');

		$form = new CU_Form_Model(array(
			'model' => 'User',
			'adapter' => $this->_adapter
		));
	
		$this->assertNull($form->getElementForColumn('id'));
	}

	public function testZendFormParametersPass()
	{
		$this->_initAdapter('User');

		$form = new CU_Form_Model(array(
			'model' => 'User',
			'action' => 'test',
			'adapter' => $this->_adapter
		));

		$this->assertEquals('test', $form->getAction());
	}

	public function testRecordLoading()
	{
		$this->_initAdapter('User');

		$this->_adapter->expects($this->any())
		               ->method('getRecord')
					   ->will($this->onConsecutiveCalls(false, true));

		$this->_adapter->expects($this->once())
		               ->method('getNewRecord')
					   ->will($this->returnValue(false));

		$form = new CU_Form_Model(array(
			'model' => 'User',
			'adapter' => $this->_adapter
		));

		//First getRecord is set up to return false
		$this->assertFalse($form->getRecord());

		$user = array(
			'login' => 'Login',
			'password' => 'Password'
		);

		$this->_adapter->expects($this->once())
		               ->method('setRecord')
                       ->with($this->equalTo($user));

		//NOTE: will cause a wrong value to be inputted into the password field!
		$this->_adapter->expects($this->any())
		               ->method('getRecordValue')
					   ->will($this->returnValue('Login'));

		$form->setRecord($user);

		//Second getRecord is set up to return true
		$this->assertTrue($form->getRecord());

		$this->assertEquals('Login', $form->getElementForColumn('login')->getValue());
	}

	public function testRecordSaving()
	{
		$this->_initAdapter('User');

		$form = new CU_Form_Model(array(
			'model' => 'User',
			'adapter' => $this->_adapter
		));

		$form->getElementForColumn('login')->setValue('Test');
		$form->getElementForColumn('password')->setValue('Test');

		//Should not get called if persist param is false
		$this->_adapter->expects($this->never())
		               ->method('saveRecord');

		$form->save(false);

		$this->_initAdapter('User');
		$form->setAdapter($this->_adapter);

		$this->_adapter->expects($this->once())
		               ->method('saveRecord');

		//Should get called twice as we set two values
		$this->_adapter->expects($this->exactly(2))
		               ->method('setRecordValue');

		$record = $form->save();
	}

	public function testEventHooks()
	{
		$this->_initAdapter('User');

		$form = new CU_Form_ModelTest_Form(array(
			'model' => 'User',
			'adapter' => $this->_adapter
		));

		$this->assertTrue($form->preGenerated);
		$this->assertTrue($form->postGenerated);
		$this->assertFalse($form->postSaved);

		$form->save(false);

		$this->assertTrue($form->postSaved);
	}

	public function testCreatingFormWithOneRelation()
	{
		$this->_initAdapter('Comment');

		$this->_adapter->expects($this->once())
		               ->method('getOneRecords')
					   ->with($this->_relations['Comment']['Article'])
					   ->will($this->returnValue(array()));
		               

		$form = new CU_Form_Model(array(
			'model' => 'Comment',
			'adapter' => $this->_adapter
		));

		$name = $form->getRelationElementName('Article');
		$elem = $form->getElementForRelation('Article');
		$this->assertNotNull($elem);
		$this->assertNotEquals('', $name);
		$this->assertEquals($elem->getName(), $name);
	}

	public function testCreatingFormWithManyRelation()
	{
		$this->_initAdapter('Article');

		$form = new CU_Form_Model(array(
			'model' => 'Article',
			'generateManyFields' => true,
			'adapter' => $this->_adapter
		));

		$forms = $form->getSubForms();
		$this->assertTrue(count($forms) == 0);
	}

	public function testNotNullColumnsAreRequired()
	{
		$this->_initAdapter('Comment');

		$this->_adapter->expects($this->any())
		               ->method('getOneRecords')
					   ->will($this->returnValue(array()));

		$form = new CU_Form_Model(array(
			'model' => 'Comment',
			'adapter' => $this->_adapter
		));

		$this->assertFalse($form->getElementForColumn('sender')->isValid(''));
		$this->assertFalse($form->getElementForRelation('Article')->isValid(''));
	}

	public function testOneRelationSaving()
	{
		$this->_initAdapter('Comment');

		$article = array(
			'id' => 1,
			'name' => 'Test'
		);

		$this->_adapter->expects($this->once())
		               ->method('getOneRecords')
					   ->will($this->returnValue(array($article)));

		$form = new CU_Form_Model(array(
			'model' => 'Comment',
			'adapter' => $this->_adapter
		));

		$form->getElementForRelation('Article')->setValue(1);
		$form->getElementForColumn('sender')->setValue('Sender');

		$this->_adapter->expects($this->any())
		               ->method('setRecordValue');

		$form->save();
	}
}

class CU_Form_ModelTest_Form extends CU_Form_Model
{
	public $preGenerated = false;
	public $postGenerated = false;
	public $postSaved = false;

	protected function _preGenerate()
	{
		$this->preGenerated = true;
	}

	protected function _postGenerate()
	{
		$this->postGenerated = true;
	}

	protected function _postSave($persist)
	{
		$this->postSaved = true;
	}
}
