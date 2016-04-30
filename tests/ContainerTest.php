<?php
class ContainerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		// build a basic case
		$this->container = new \Exedra\Container\Container;
	}

	public function testService()
	{
		$this->container['services']->add('foo', function()
		{
			return 'foo-bar';
		});

		$this->container['services']['bar'] = function()
		{
			return 'bar-baz';
		};

		$this->assertEquals('foo-bar', $this->container->foo);

		$this->assertEquals('foo-bar', $this->container->get('foo'));

		$this->assertEquals('bar-baz', $this->container->bar);
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
			return new \Exedra\Application\Factory\Form\Form;
		};

		$this->assertEquals(\Exedra\Application\Factory\Form\Form::CLASS, get_class($this->container->create('form')));
	}
}