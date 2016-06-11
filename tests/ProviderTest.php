<?php
class ProviderTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory');
	}

	public function testRegister()
	{
		$this->app['provider']->add(\App\FooProvider::class);

		$this->app['provider']->register(new \App\ServiceProvider);

		$this->assertEquals('bar', $this->app->get('foo'));

		$this->assertEquals('baz', $this->app->get('bar'));
	}

	public function testLateRegistry()
	{
		$this->app['provider']->flagAsLateRegistry();

		$this->app['provider']->add(\App\FooProvider::class);

		$this->app['provider']->boot();

		$this->assertEquals('bar', $this->app->get('foo'));
	}

	public function testAttributeMutability()
	{
		$this->app['foo'] = 'bar';

		try
		{
			$this->app['foo'] = 'baz';

			$thrown = false;
		}
		catch(\Exedra\Exception\Exception $e)
		{
			$thrown = true;
		}

		$this->assertTrue($thrown);

		$this->app->setMutables(['foo']);
		
		try
		{
			$this->app['foo'] = 'bad';

			$thrown = false;
		}
		catch(\Exedra\Exception\Exception $e)
		{
			$thrown = true;
		}

		$this->assertFalse($thrown);
	}

	public function testDeferredProvider()
	{
		$this->app['provider']->add(\App\FooProvider::class, array('foo'));

		$this->assertEquals('bar', $this->app->foo);
	}
}