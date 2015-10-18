<?php
require_once "Exedra/Exedra.php";

class SessionTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->storage = array();

		$this->session = new \Exedra\Application\Session\Session($this->storage);

		$this->flash = new \Exedra\Application\Session\Flash($this->session);
	}

	public function testAssign()
	{
		$this->session->set('test_key', 'test_value');

		$this->assertEquals($this->storage['test_key'], 'test_value');

		$this->assertEquals($this->storage['test_key'], $this->session->get('test_key'));
	}

	public function testNestedAssign()
	{
		$this->session->set('nested_test_key.nested_test_key2', 'test_value');

		$this->session->set('nested_test_key.nested_test_key3', array(
			'nested_test_key4' => 'test_value2'
			));

		$this->assertEquals(isset($this->storage['nested_test_key']), true);

		$this->assertEquals($this->storage['nested_test_key']['nested_test_key2'], 'test_value');

		$this->assertEquals($this->storage['nested_test_key']['nested_test_key2'], $this->session->get('nested_test_key.nested_test_key2'));

		$this->assertEquals($this->storage['nested_test_key']['nested_test_key3']['nested_test_key4'], 'test_value2');
	}

	public function testPrefix()
	{
		$this->session->setPrefix('test_prefix');

		$this->session->set('test_key', 'test_value');

		$this->assertEquals($this->storage['test_prefix']['test_key'], $this->session->get('test_key'));
	}

	public function testNestedPrefix()
	{
		$this->session->setPrefix('test_prefix.test_prefix2');

		$this->session->get('test_key', 'test_value');

		$this->assertEquals($this->storage['test_prefix']['test_prefix2'], $this->session->get('test_key'));
	}

	public function testDestroy()
	{
		$this->testAssign();

		$this->session->destroy('test_key');

		$this->assertEquals(!isset($this->storage['test_key']), true);
	}

	public function testDestroyNested()
	{
		$this->testNestedAssign();

		$this->session->destroy('nested_test_key.nested_test_key2');

		$this->assertEquals(!isset($this->storage['nested_test_key']['nested_test_key2']), true);

		$this->assertEquals($this->session->has('nested_test_key.nested_test_key3.nested_test_key4'), true);

		$this->session->destroy('nested_test_key.nested_test_key3');

		$this->assertEquals(!isset($this->storage['nested_test_key']['nested_test_key3']['nested_test_key4']), true);
	}

	public function testFlash()
	{
		$this->flash->set('test_key', 'test_value');

		$baseKey = $this->flash->getBaseKey();

		$this->assertEquals($this->flash->get('test_key'), 'test_value');

		$this->assertEquals($this->storage[$baseKey]['test_key'], $this->session->get("$baseKey.test_key"));

		$this->assertEquals($this->storage[$baseKey]['test_key'], 'test_value');

		$this->session->setPrefix($baseKey);

		$this->assertEquals($this->session->get('test_key'), 'test_value');
	}
}