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

class ContainerTest extends BaseTestCase
{
	public function caseSetUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory');

        $this->app->provider->add(\Exedra\Support\Provider\Framework::class);

		$this->app->path['src']->autoloadPsr4('App', '');

		// build a basic case
		$this->container = new \Exedra\Container\Container;
	}

	public function testService()
	{
		$this->container['service']->add('qux', array('\stdClass'));

		$this->assertEquals('stdClass', get_class($this->container->qux));

		$this->container['service']->add('foo', function()
		{
			return 'foo-bar';
		});

		$this->container['service']['bar'] = function()
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
		$this->container['callable']['getSomething'] = function($foo)
		{
			return 'something-'.$foo;
		};

		$this->assertEquals('something-bar', $this->container->getSomething('bar'));
	}

	public function testFactory()
	{
		$this->container['factory']['form'] = function()
		{
			return new \Exedra\Form\Form;
		};

		$this->assertEquals(\Exedra\Form\Form::CLASS, get_class($this->container->create('form')));
	}

	public function testAutoresolve()
	{
		$this->container['service']->add('foo', 'Foo');

		$this->container['service']->add('bad', function()
		{
			return 'qux';
		});

		$this->container['service']->add('bar', array('Bar', array('service.foo', 'bad')));

		$this->container['service']->add('bor', array('Bar', array('foo', 'bad')));

		$this->assertEquals('Bar', get_class($this->container->bar));

		$this->assertEquals('qux', $this->container->bar->getBas());

		$this->assertEquals('qux', $this->container->bor->getBas());
	}

	public function testMergedFactoryArguments()
	{
		$this->container['factory']['bar'] = array('Bar', array('factory.foo'));

		$this->container['factory']['foo'] = function()
		{
			return new Foo;
		};

		$this->container['factory']->add('bat', 'Bar');

		$bar = $this->container->create('bar', array('baz'));

		$this->assertEquals('baz', $bar->getBas());

		$bat = $this->container->create('bat', array($this->container->create('foo'), 'qux'));

		$this->assertEquals('qux', $bat->getBas());
	}

	public function testInvokableService()
	{
		$this->container['service']->add('foo', function()
		{
			return new \App\FooInvokable;
		});

		$this->assertEquals('bar', $this->container->foo());

		$this->assertEquals('bar', $this->container['foo']());
	}
}