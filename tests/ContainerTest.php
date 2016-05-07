<?php
class Foo
{

}

class Bar
{
	public function __construct(Foo $foo, $bas)
	{
		$this->bas = $bas;
	}

	public function getBas()
	{
		return $this->bas;
	}
}

class ContainerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory/TestApp');

		// build a basic case
		$this->container = new \Exedra\Container\Container;
	}

	public function testService()
	{
		$this->container['services']->add('qux', array('\stdClass'));

		$this->assertEquals('stdClass', get_class($this->container->qux));

		$this->container['services']->add('foo', function()
		{
			return 'foo-bar';
		});

		$this->container['services']['bar'] = function()
		{
			return 'bar-baz';
		};

		$this->assertEquals($this->container->foo, $this->container->foo);

		$this->assertEquals('foo-bar', $this->container->foo);

		$this->assertEquals('foo-bar', $this->container->get('foo'));

		$this->assertEquals('bar-baz', $this->container->bar);

		$this->assertEquals('bar-baz', $this->container['bar']);
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

	public function testAutoresolve()
	{
		$this->container['services']->add('foo', 'Foo');

		$this->container['services']->add('bad', function()
		{
			return 'qux';
		});

		$this->container['services']->add('bar', array('Bar', array('services.foo', 'bad')));

		$this->container['services']->add('bor', array('Bar', array('foo', 'bad')));

		$this->assertEquals('Bar', get_class($this->container->bar));

		$this->assertEquals('qux', $this->container->bar->getBas());

		$this->assertEquals('qux', $this->container->bor->getBas());
	}

	public function testMergedFactoryArguments()
	{
		$this->container['factories']['bar'] = array('Bar', array('factories.foo'));

		$this->container['factories']['foo'] = function()
		{
			return new Foo;
		};

		$this->container['factories']->add('bat', 'Bar');

		$bar = $this->container->create('bar', array('baz'));

		$this->assertEquals('baz', $bar->getBas());

		$bat = $this->container->create('bat', array($this->container->create('foo'), 'qux'));

		$this->assertEquals('qux', $bat->getBas());
	}

	public function testInvokableService()
	{
		$this->container['services']->add('foo', function()
		{
			return new \TestApp\FooInvokable;
		});

		$this->assertEquals('bar', $this->container->foo());

		$this->assertEquals('bar', $this->container['foo']());
	}
}