<?php
class ContainerRegistryTest extends BaseTestCase
{
	public function caseSetUp()
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

		$this->assertTrue($app->get('routingFactory') instanceof \Exedra\Routing\Factory);

		$this->assertTrue($app->map instanceof \Exedra\Routing\Group);

		// $this->assertTrue($app->runtime instanceof \Exedra\Runtime\Registry);

		$this->assertTrue($app->request instanceof \Exedra\Http\ServerRequest);

		$this->assertTrue($app->url instanceof \Exedra\Url\UrlFactory);

		// $this->assertTrue($app->module instanceof \Exedra\Module\Registry);

		$this->assertTrue($app->path instanceof \Exedra\Path);
	}

	public function testRuntimeServiceRegistry()
	{
		$this->app->map->any('/')->execute(function(){ });

		$exe = $this->app->request(\Exedra\Http\ServerRequest::createFromArray(array(
				'method' => 'GET',
				'uri' => '/'
				)));

		$this->assertTrue($exe->url instanceof \Exedra\Url\UrlFactory);

		$this->assertTrue($exe->redirect instanceof \Exedra\Runtime\Redirect);

		// $this->assertTrue($exe->module instanceof \Exedra\Module\Registry && $exe->module === $exe->app->module);

		// $this->assertTrue($exe->view instanceof \Exedra\View\UrlFactory && $exe->view === $exe->module['Application']->view);

		// $this->assertTrue($exe->controller instanceof \Exedra\UrlFactory\Controller && $exe->controller === $exe->module['Application']->controller);
	}

	public function testConfigPriority()
	{
		$app = $this->app;

		$app->config->set('foo-bar', 'app group');

		$getConfig = function($route) use($app)
		{
			return $app->execute($route)->config->get('foo-bar');
		};

		$app->map['foo']->execute(function(){ });

		$this->assertEquals('app group', $getConfig('foo'));

		// route group config
		$app->map['fooo']->config(array('foo-bar' => 'route group'))->execute(function(){ });

		$this->assertEquals('route group', $getConfig('fooo'));

		// runtime group
		$app->map->middleware(function($exe)
		{
			$exe->service->on('config', function($config)
			{
				$config->set('foo-bar', 'runtime group');

				return $config;
			});

			return $exe->next($exe);
		});

		$this->assertEquals('runtime group', $getConfig('fooo'));

		// merge config on module group with runtime.
		// $app->map->middleware(function($exe)
		// {
		// 	$exe['service']->on('config', function($config) use($exe)
		// 	{
		// 		$config->set($exe->getModule()->config->getAll());
		// 	});

		// 	return $exe->next($exe);
		// });

		// $this->assertEquals('module group', $getConfig('fooo'));
	}
}