<?php
require_once __DIR__.'/autoload.php';

class ApplicationTest extends BaseTestCase
{
	public function caseSetUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory');

        $this->app->provider->add(\Exedra\Support\Provider\Framework::class);

		$this->app->map['foo']->any('/')->execute(function($exe)
		{
			return 'bar';
		});
	}

	public function testCreate()
	{
		$app = new \Exedra\Application(__DIR__.'/Factory');

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

		$this->assertEquals(__DIR__.'/Factory', $app->getRootDir());

		// $this->assertEquals('TestApp', $app->getNamespace());

		// $this->assertEquals('TestApp\\Foo\\Bar', $app->getNamespace('Foo\\Bar'));

		$this->assertEquals(realpath(__DIR__.'/Factory/public'), realpath($app->path['public']));

		$this->assertEquals(realpath(__DIR__.'/Factory'), realpath($app->getRootDir()));
	}

	public function testExecute()
	{
		$app = $this->app;

        $this->assertTrue($app->execute('foo') instanceof \Exedra\Runtime\Context);

		$this->assertEquals('bar', $app->execute('foo')->response->getBody());
	}

	public function testApplicationContainer()
	{
		$app = $this->app;

		$app['service']['foo'] = function()
		{
			return 'bar';
		};

		$app['service']->add('bar', function()
		{
			return 'baz';
		});

		$app['callable']['foo'] = function($param)
		{
			return 'barz'.$param;
		};

		$app['callable']->add('bar', function($arg)
		{
			return 'baz'.$arg;
		});

		$app['factory']['foo'] = function($param)
		{
			return 'baz'.$param;
		};

		$app['factory']->add('bar', function()
		{
			return 'baz';
		});

		$this->assertEquals('bar', $app->foo);

		$this->assertEquals('baz', $app->bar);

		$this->assertEquals('barzraz', $app->foo('raz'));

		$this->assertEquals('bazraz', $app->bar('raz'));

		$this->assertEquals('bazbar', $app->create('foo', array('bar')));
	}

	public function testSharedService()
	{
		$app = $this->app;

		$app['service']['@foo'] = function()
		{
			return 'bar';
		};

		$app['service']['@baz'] = function()
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

	public function testSharedCallable()
	{
		$app = $this->app;

		$app['callable']['@foo'] = function()
		{
			return 'bar';
		};

		$exe = $app->execute('foo');

		$this->assertEquals($exe->foo(), 'bar');

		$this->assertEquals($app->foo(), $exe->foo());
	}

	public function testSharedFactory()
	{
		$app = $this->app;

		$app['factory']['@foo'] = function($bat = 'nul')
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

	// public function testSharedInvokable()
	// {
	// 	$app = $this->app;

	// 	$app['service']['@qux'] = \App\FooInvokable::class;

	// 	$exe = $app->execute('foo');

	// 	$this->assertEquals($exe['qux'], $app['qux']);

	// 	$this->assertEquals($exe->qux(), $app->qux());

	// 	$this->assertEquals('bar', $app->qux());
	// }

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

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

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

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

		$app->map->any('/foo/bar')->execute(function()
		{
			return 'baz';
		});

		$response = $app->respond($request);

		$this->assertEquals(404, $response->getStatusCode());
	}
}