<?php
class MiddlewareTest extends \BaseTestCase
{
    /**
     * @var \Exedra\Application $app
     */
    protected $app;

    /**
     * @var \Exedra\Routing\Group
     */
    protected $map;

	public function caseSetUp()
	{
		$this->app = new \Exedra\Application(__DIR__.'/Factory');

		$this->map = $this->app->map;
	}

	public function testRegistry()
	{
	    $this->map->middleware(function(\Exedra\Runtime\Context $context) {
	        return 'first-' . $context->next($context);
        }, ['name' => 'first']);

	    $this->map->middleware(function(\Exedra\Runtime\Context $context) {
	        return 'second-' . $context->next($context);
        }, ['name' => 'second']);

		$this->map['foo']->any('/')->execute(function(){
		    return 'foo';
        });

		$this->map->addRoutes(array(
			'bar' => array(
				'path' => '/foo',
				'execute' => function(){
				    return 'bar';
                })
			));

		$this->assertEquals('first-second-foo', $this->app->execute('foo')->response->getBody());

		$this->assertEquals('first-second-bar', $this->app->execute('bar')->response->getBody());

		$finding = $this->map->findRoute('foo');

	}

	/*public function testMiddlewareRemoval()
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

        $this->assertEquals('with-middleware', $this->app->run($finding)->response->getBody());

        $finding->getCallStack()->removeCall('foo-middleware');

        $this->assertEquals('without-middleware', $this->app->run($finding)->response->getBody());
    }*/
}