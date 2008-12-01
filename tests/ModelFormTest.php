<?php
require_once 'TestHelper.php';

class CU_Form_ModelTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		parent::setUp();
		Doctrine_Manager::connection('sqlite::memory:', 'grouport');
		Doctrine::createTablesFromModels();
	}

	public function tearDown()
	{
		parent::tearDown();
		Doctrine_Manager::getInstance()->closeConnection(Doctrine_Manager::connection());
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

	public function testTableLoading()
	{
		$form = new CU_Form_Model(array(
			'model' => 'User'
		));

		$this->assertEquals('User', $form->getTable()->getComponentName());
	}

	public function testColumnIgnoring()
	{
		$form = new CU_Form_Model(array(
			'model' => 'User',
			'ignoreColumns' => array('login')
		));

		$this->assertNull($form->getElementForColumn('login'));
		$this->assertNotNull($form->getElementForColumn('password'));
	}

	public function testPrimaryKeyIgnored()
	{
		$form = new CU_Form_Model(array(
			'model' => 'User'
		));
	
		$this->assertNull($form->getElementForColumn('id'));
	}

	public function testZendFormParametersPass()
	{
		$form = new CU_Form_Model(array(
			'model' => 'User',
			'action' => 'test'
		));

		$this->assertEquals('test', $form->getAction());
	}

	public function testRecordLoading()
	{
		$form = new CU_Form_Model(array(
			'model' => 'User'
		));

		$this->assertFalse($form->getRecord()->exists());

		$record = new User();
		$record->login = 'Test';

		$form->setRecord($record);

		$this->assertEquals('Test', $form->getRecord()->login);
		$this->assertEquals('Test', $form->getElementForColumn('login')->getValue());
	}

	public function testRecordSaving()
	{
		$form = new CU_Form_Model(array(
			'model' => 'User'
		));

		$form->getElementForColumn('login')->setValue('Test');
		$form->getElementForColumn('password')->setValue('Test');

		$form->save(false);
		
		$this->assertFalse($form->getRecord()->exists());

		$record = $form->save();

		$this->assertTrue($form->getRecord()->exists());
		$this->assertNotNull($record);
		$this->assertEquals($record->id, $form->getRecord()->id);

		$record->delete();
		$this->assertFalse($form->getRecord()->exists());
	}

	public function testEventHooks()
	{
		$form = new CU_Form_ModelTest_Form(array(
			'model' => 'User'
		));

		$this->assertTrue($form->preGenerated);
		$this->assertTrue($form->postGenerated);
		$this->assertFalse($form->postSaved);

		$form->save(false);

		$this->assertTrue($form->postSaved);
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
