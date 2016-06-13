<?php
class MiddlewareTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory');

		$this->map = $this->app->map;

		$this->app->middleware->register(array(
			'global' => function($exe)
			{
				$exe->text = 'global';

				return $exe->next($exe);
			},
			'limiter' => \App\Middleware\RateLimiter::CLASS,
			'decorator' => function($exe)
			{
				return $exe->text.'-bar-baz!';
			}
		));
	}

	public function testRegistry()
	{
		$this->map->middleware('global');

		$this->map['foo']->any('/')->middleware('limiter')->execute(function(){});

		$this->map->addRoutes(array(
			'bar' => array(
				'path' => '/foo',
				'middleware' => 'decorator',
				'execute' => function(){})
			));

		$this->assertEquals('global-foo-bar!', $this->app->execute('foo')->response->getBody());

		$this->assertEquals('global-bar-baz!', $this->app->execute('bar')->response->getBody());
	}
}