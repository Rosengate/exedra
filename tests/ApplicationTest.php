<?php
class ApplicationTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory/TestApp');

		$this->app->map->any('/')->name('foo')->execute(function($exe)
		{
			return 'bar';
		});
	}

	public function testCreate()
	{
		$app = new \Exedra\Application(__DIR__.'/Factory/TestApp');

		$this->assertEquals(__DIR__.'/Factory/TestApp', $app->getDir());

		$this->assertEquals('TestApp', $app->getNamespace());

		$this->assertEquals('TestApp\\Foo\\Bar', $app->getNamespace('Foo\\Bar'));

		$this->assertEquals(realpath(__DIR__.'/Factory/public'), realpath($app->getPublicDir()));

		$this->assertEquals(realpath(__DIR__.'/Factory'), realpath($app->getRootDir()));
	}

	public function testExecute()
	{
		$app = $this->app;

		$this->assertEquals(\Exedra\Application\Execution\Exec::CLASS, get_class($app->execute('foo')));

		$this->assertEquals('bar', $app->execute('foo')->response->getBody());
	}

	public function testApplicationContainer()
	{
		$app = $this->app;

		$app['services']['foo'] = function()
		{
			return 'bar';
		};

		$app['services']->add('bar', function()
		{
			return 'baz';
		});

		$app['callables']['foo'] = function($param)
		{
			return 'barz'.$param;
		};

		$app['callables']->add('bar', function($arg)
		{
			return 'baz'.$arg;
		});

		$app['factories']['foo'] = function($param)
		{
			return 'baz'.$param;
		};

		$app['factories']->add('bar', function()
		{
			return 'baz';
		});

		$this->assertEquals('bar', $app->foo);

		$this->assertEquals('baz', $app->bar);

		$this->assertEquals('barzraz', $app->foo('raz'));

		$this->assertEquals('bazraz', $app->bar('raz'));

		$this->assertEquals('bazbar', $app->create('foo', array('bar')));
	}

	public function testSharedServices()
	{
		$app = $this->app;

		$app['services']['@foo'] = function()
		{
			return 'bar';
		};

		$app['services']['@baz'] = function()
		{
			return new stdClass;
		};

		$exe = $app->execute('foo');

		// assert if both application dependency is shared.
		$this->assertEquals('bar', $app->foo);

		$this->assertEquals('bar', $exe->foo);

		// assert if both application is truly shared.
		$app->baz->bat = 'qux';

		$this->assertEquals('qux', $exe->baz->bat);

		$exe->baz->bat = 'quux';

		$this->assertEquals('quux', $app->baz->bat);
	}

	public function testSharedCallables()
	{
		$app = $this->app;

		$app['callables']['@foo'] = function()
		{
			return 'bar';
		};

		$exe = $app->execute('foo');

		$this->assertEquals($exe->foo(), 'bar');

		$this->assertEquals($app->foo(), $exe->foo());
	}

	public function testSharedFactories()
	{
		$app = $this->app;

		$app['factories']['@foo'] = function($bat = 'nul')
		{
			$obj = new stdClass;

			$obj->bar = $bat;

			return $obj;
		};

		$exe = $app->execute('foo');

		$obj = $app->create('foo', array('qux'));

		$this->assertEquals($obj->bar, 'qux');

		$this->assertEquals(get_class($app->create('foo')), get_class($exe->create('foo')));
	}

	public function testDispatch()
	{
		$request = \Exedra\Http\ServerRequest::createFromArray(array(
			'method' => 'GET',
			'uri' => 'http://google.com:80/foo/bar?baz=bad#fragman',
			'headers' => array(
				'referer' => array('http://foo.com')
				)
			));

		$app = new \Exedra\Application(__DIR__);

		$app->map->any('/foo/bar')->execute(function()
		{
			return 'baz';
		});

		$response = $app->respond($request);

		// $this->assertEquals(200, $response->getStatusCode());

		$this->assertEquals('baz', $response->getBody());
	}

	public function testFailedDispatch()
	{
		$request = \Exedra\Http\ServerRequest::createFromArray(array(
			'method' => 'GET',
			'uri' => 'http://google.com:80/foo/bar/bat?baz=bad#fragman',
			'headers' => array(
				'referer' => array('http://foo.com')
				)
			));

		$app = new \Exedra\Application(__DIR__);

		$app->map->any('/foo/bar')->execute(function()
		{
			return 'baz';
		});

		$response = $app->respond($request);

		$this->assertEquals(404, $response->getStatusCode());

		$this->assertEquals('Route is not found', $response->getBody());
	}
}