<?php
class ContainerRegistryTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/app');
	}

	public function testApplicationServiceRegistry()
	{
		$app = $this->app;

		$app->service->set('request', function()
		{
			return \Exedra\Http\ServerRequest::createFromArray(array(
				'method' => 'GET',
				'uri' => '/'
				));
		});

		$this->assertTrue($app->config instanceof \Exedra\Config);

		$this->assertTrue($app->get('routing.factory') instanceof \Exedra\Routing\Factory);

		$this->assertTrue($app->map instanceof \Exedra\Routing\Level);

		// $this->assertTrue($app->runtime instanceof \Exedra\Runtime\Registry);

		$this->assertTrue($app->middleware instanceof \Exedra\Middleware\Registry);

		$this->assertTrue($app->request instanceof \Exedra\Http\ServerRequest);

		$this->assertTrue($app->url instanceof \Exedra\Factory\Url);

		$this->assertTrue($app->wizard instanceof \Exedra\Wizard\Manager);

		// $this->assertTrue($app->module instanceof \Exedra\Module\Registry);

		$this->assertTrue($app->path instanceof \Exedra\Path);

		$this->assertTrue($app->path['public'] instanceof \Exedra\Path);

		$this->assertTrue($app->path['routes'] instanceof \Exedra\Path);

		$this->assertTrue($app->path['app'] instanceof \Exedra\Path);
	}

	public function testRuntimeServiceRegistry()
	{
		$this->app->map->any('/')->execute(function(){ });

		$exe = $this->app->request(\Exedra\Http\ServerRequest::createFromArray(array(
				'method' => 'GET',
				'uri' => '/'
				)));

		$this->assertTrue($exe->url instanceof \Exedra\Factory\Url);

		$this->assertTrue($exe->redirect instanceof \Exedra\Runtime\Redirect);

		// $this->assertTrue($exe->module instanceof \Exedra\Module\Registry && $exe->module === $exe->app->module);

		// $this->assertTrue($exe->view instanceof \Exedra\View\Factory && $exe->view === $exe->module['Application']->view);

		// $this->assertTrue($exe->controller instanceof \Exedra\Factory\Controller && $exe->controller === $exe->module['Application']->controller);
	}

	public function testConfigPriority()
	{
		$app = $this->app;

		$app->config->set('foo-bar', 'app level');

		$getConfig = function($route) use($app)
		{
			return $app->execute($route)->config->get('foo-bar');
		};

		$app->map['foo']->execute(function(){ });

		$this->assertEquals('app level', $getConfig('foo'));

		// route level config
		$app->map['fooo']->config(array('foo-bar' => 'route level'))->execute(function(){ });

		$this->assertEquals('route level', $getConfig('fooo'));

		// runtime level
		$app->map->middleware(function($exe)
		{
			$exe->service->on('config', function($config)
			{
				$config->set('foo-bar', 'runtime level');
			});

			return $exe->next($exe);
		});

		$this->assertEquals('runtime level', $getConfig('fooo'));

		// merge config on module level with runtime.
		// $app->map->middleware(function($exe)
		// {
		// 	$exe['service']->on('config', function($config) use($exe)
		// 	{
		// 		$config->set($exe->getModule()->config->getAll());
		// 	});

		// 	return $exe->next($exe);
		// });

		// $this->assertEquals('module level', $getConfig('fooo'));
	}
}