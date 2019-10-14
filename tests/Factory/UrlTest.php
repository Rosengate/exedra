<?php

/**
 * @property \Exedra\Application $app
 */
class FactoryUrlTest extends \BaseTestCase
{
	public function caseSetUp()
	{
		$this->app = new \Exedra\Application(__DIR__);

		$this->map = $this->app->map;
	}

	public function testUrlCreate()
	{
		$this->map->addRoutes(array(
			'tester'=>['path'=> 'tester/[:route]', 'execute'=>function($exe)
				{
					$params = $exe->param('params') ? : array();
					return $exe->url->route($exe->param('route'), $params);
				}],
			'r1'=>['path'=> 'uri1/uri2', 'execute'=>function(){ }],
			'r2'=>['path'=> 'uri1/[:param]', 'execute'=>function(){ }]
			));

		// simple uri
		$this->assertEquals('/uri1/uri2', (string) $this->app->execute('tester', array('route'=> 'r1'))->response->getBody());

		$this->assertEquals('/uri1/simple-param', $this->app->execute('tester', 
			array(
				'route'=> 'r2',
				'params'=> array('param'=> 'simple-param'))
				)->response->getBody());
	}

	public function testCurrent()
	{
		$this->app['service']['request'] = function()
		{
			return \Exedra\Http\ServerRequest::createFromArray(array(
			'method' => 'GET',
			'uri' => 'http://example.com/hello/world/current'
			));
		};

		$this->assertEquals('http://example.com/hello/world/current', $this->app->url->current());
	}

	public function testPrevious()
	{
		$this->app->request = \Exedra\Http\ServerRequest::createFromArray(array(
			'method' => 'GET',
			'uri' => 'http://example.com/hello/world',
			'headers' => array(
				'Referer' => array('http://example.com/previous')
				)
			));

		$this->assertEquals('http://example.com/previous', $this->app->url->previous());
	}

	public function testBaseAndAsset()
	{
		$this->app->config->set(array(
			'app.url' => 'http://example.com/foo',
			'asset.url' => 'http://example.com/foo/assets'
			));

		$this->app['service']['request'] = function()
		{
			return null;
		};

		$this->assertEquals('http://example.com/foo/bar/baz', $this->app->url->base('bar/baz'));
		
		$this->assertEquals('http://example.com/foo/bar/baz', $this->app->url->to('bar/baz'));
	}

	public function testAddCallable()
	{
		$this->app->config->set(array(
			'app.url' => 'http://example.com/foo',
			'asset.url' => 'http://example.com/foo/assets'
			));

		$this->app['service']['request'] = function()
		{
			return null;
		};

		$this->app->url->addCallable('foo', function(\Exedra\Url\UrlFactory $urlFactory, $var)
		{
			return $urlFactory->base('bar/'.$var);
		});

		$this->assertEquals('http://example.com/foo/bar/baz', $this->app->url->foo('baz'));

		$this->app->map['foo']->any('/')->execute(function(){ });
	}

    public function testBaseUri()
    {
        $this->app->map['foo']->uri('http://localhost:9000/baz')
            ->group(function(\Exedra\Routing\Group $group) {
                $group['bar']->get('/foo/bar')
                    ->execute(function() {
                    });
            });

        $this->assertEquals('http://localhost:9000/baz/foo/bar', $this->app->url->route('@foo.bar'));
	}
}