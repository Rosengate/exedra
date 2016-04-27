<?php
require_once "Exedra/Exedra.php";

class ContainerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		// build a basic case
		$this->container = new \Exedra\Application\Container;
	}

	public function testService()
	{
		$this->container['services']['db'] = function()
		{
			return 'foo-bar';
		};

		$this->assertEquals('foo-bar', $this->container->db);
	}

	public function testCallable()
	{
		$this->container['callables']['getSomething'] = function($foo)
		{
			return 'something-'.$foo;
		};

		$this->assertEquals('something-bar', $this->container->getSomething('bar'));
	}

	public function testFactory()
	{
		$this->container['factories']['form'] = function()
		{
			return new \Exedra\Application\Builder\Form\Form;
		};

		$this->assertEquals(\Exedra\Application\Builder\Form\Form::CLASS, get_class($this->container->create('form')));
	}
}