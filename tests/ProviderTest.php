<?php
class ProviderTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory/TestApp');
	}

	public function testRegister()
	{
		$this->app['providers']->add(\TestApp\FooProvider::class);

		$this->app['providers']->register(new \TestApp\ServiceProvider);

		$this->assertEquals('bar', $this->app->get('foo'));

		$this->assertEquals('baz', $this->app->get('bar'));
	}

	public function testLateRegistry()
	{
		$this->app['providers']->flagAsLateRegistry();

		$this->app['providers']->add(\TestApp\FooProvider::class);

		$this->app['providers']->boot();

		$this->assertEquals('bar', $this->app->get('foo'));
	}
}