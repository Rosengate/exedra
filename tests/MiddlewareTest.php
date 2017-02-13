<?php
class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Exedra\Application $app
     */
    protected $app;

	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory');

		$this->map = $this->app->map;

		/*$this->app->middleware->register(array(
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
		));*/
	}

	public function testRegistry()
	{
//		$this->map->middleware('global');

//		$this->map['foo']->any('/')->middleware('limiter')->execute(function(){});
//
//		$this->map->addRoutes(array(
//			'bar' => array(
//				'path' => '/foo',
//				'middleware' => 'decorator',
//				'execute' => function(){})
//			));
//
//		$this->assertEquals('global-foo-bar!', $this->app->execute('foo')->response->getBody());
//
//		$this->assertEquals('global-bar-baz!', $this->app->execute('bar')->response->getBody());
	}

	public function testMiddlewareRemoval()
    {
        $this->map->middleware(function()
        {
            return 'with-middleware';
        }, 'foo-middleware');

        $this->map['foo']->get('/')->execute(function()
        {
            return 'without-middleware';
        });

        $finding = $this->app->map->findByName('foo');

        $this->assertEquals('with-middleware', $this->app->exec($finding)->response->getBody());

        $finding->removeMiddleware('foo-middleware');

        $this->assertEquals('without-middleware', $this->app->exec($finding)->response->getBody());
    }
}