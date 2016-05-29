<?php
class ModuleTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__);
	}

	public function testDefaultModule()
	{
		$foo = $this->app['module']->get('Frontend')->view->create('foo');

		$controller = $this->app->module['Frontend']->controller;

		$this->assertEquals('bar', $foo->render());

		$this->assertTrue($controller instanceof \Exedra\Factory\Controller);
	}

	public function testConfigure()
	{
		$this->app['module']->configure('Backend', function($module)
		{
			$module['services']->add('foo', function()
			{
				return 'bar';
			});
		});

		$this->assertEquals('bar', $this->app['module']->get('Backend')->foo);
	}

	public function testRegister()
	{
		$this->app['module']->register('Foo', 'App\Module\Foo');

		$fooModule = $this->app->module['Foo'];

		$this->assertTrue($fooModule instanceof \App\Module\Foo);

		$this->assertEquals('baz', $fooModule->bar);
	}
}