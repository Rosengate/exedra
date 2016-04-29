<?php
class ApplicationTest extends PHPUnit_Framework_TestCase
{
	public function testCreate()
	{
		$app = new \Exedra\Application(__DIR__.'/Factory/TestApp');

		$this->assertEquals(__DIR__.'/Factory/TestApp', $app->getDir());

		$this->assertEquals('TestApp', $app->getNamespace());

		$this->assertEquals(realpath(__DIR__.'/Factory/public'), realpath($app->getPublicDir()));

		$this->assertEquals(realpath(__DIR__.'/Factory'), realpath($app->getRootDir()));
	}

	public function testExecute()
	{
		$app = new \Exedra\Application(__DIR__.'/Factory/TestApp');

		$app->map->any('/')->name('foo')->execute(function($exe)
		{
			return 'bar';
		});

		$this->assertEquals(\Exedra\Application\Execution\Exec::CLASS, get_class($app->execute('foo')));

		$this->assertEquals('bar', $app->execute('foo')->response->getBody());
	}

	public function testApplicationRegistries()
	{
		$app = new \Exedra\Application(__DIR__.'/Factory/TestApp');

		$app['services']['foo'] = function()
		{
			return 'bar';
		};

		$app['callables']['foo'] = function($param)
		{
			return 'barz'.$param;
		};

		$app['factories']['foo'] = function($param)
		{
			return 'baz'.$param;
		};

		$this->assertEquals('bar', $app->foo);

		$this->assertEquals('barzraz', $app->foo('raz'));

		$this->assertEquals('bazbar', $app->create('foo', array('bar')));
	}
}