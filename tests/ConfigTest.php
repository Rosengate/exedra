<?php
require_once "Exedra/Exedra.php";

class ConfigTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->config = new \Exedra\Application\Config;
	}

	public function testSetGet()
	{
		$this->config->set('foo', 'bar');

		$this->assertEquals('bar', $this->config->get('foo'));
	}

	public function testMultidimensionalSetGet()
	{
		$this->config->set('foo.bar', 'baz');

		$foo = $this->config->get('foo');

		$this->assertTrue(is_array($foo));

		$this->assertEquals('baz', $foo['bar']);

		$this->assertTrue($this->config->has('foo.bar') && $this->config->has('foo'));
	}

	public function testOffset()
	{
		$this->config['foo'] = 'bar';

		$this->assertEquals('bar', $this->config['foo']);

		$this->assertEquals('bar', $this->config->get('foo'));

		$this->config->set('foo', array());

		$this->config['foo']['bar'] = array('baz' => 'bao');

		$this->assertEquals('bao', $this->config->get('foo.bar.baz'));

		$this->assertTrue($this->config->has('foo.bar.baz'));

		$this->assertTrue(isset($this->config['foo']['bar']['baz']));
	}
}