<?php
use Exedra\Http\ServerRequest;
use Exedra\Routing\GroupHandlers\PathHandler;

class RoutingTest extends \BaseTestCase
{
    /**
     * @var \Exedra\Routing\Group $map
     */
    protected $map;

	public function caseSetUp()
	{
		// build a basic case
		$app = $this->app = new \Exedra\Application(__DIR__.'/Factory');

		$app->path->register('routes', $app->path->to('app/routes'), true);

        $app->routingFactory->addGroupHandler(new PathHandler($app->path['routes']));

        $app->map->addRoutes(array(
			'one'=>['path' =>'path-one', 'execute'=> 'controller=hello@world'],
			'two'=>['path' =>'path-two', 'subroutes'=> array(
				'one'=> ['path' =>'sub-one', 'execute'=> 'controller=hello@world'],
				'two'=> ['path' =>'sub-two', 'subroutes'=> array(
					'one'=> ['path' =>'deep-one', 'execute'=> 'controller=hello@world'],
					)]
				)]
			));

		$app->map->addRoutes(array(
			'empty'=>['path' =>'', 'execute'=> 'controller=hello@world']
			));

		$this->map = $this->app->map;
	}

	public function createRequest(array $params)
	{
		return ServerRequest::createFromArray($params);
	}

    public function sendUriRequest($uri, $method = 'GET')
    {
        return $this->app->request(ServerRequest::createFromArray(['method' => $method, 'uri' => $uri]))->response->getBody();
    }

	public function testApp()
	{
		$this->assertEquals('Exedra\Application', get_class($this->app));
	}

	public function testEmptyPath()
	{
		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => '']]));

		$this->assertEquals('empty', $finding->route->getAbsoluteName());
	}

	public function testRouteOneLevel()
	{
		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'path-one']]));

		// confirm by route name.
		$this->assertEquals('one', $finding->route->getAbsoluteName());
	}

	public function testAddOnRoute()
	{
		// add routes on route 'two'
		$this->map->addOnRoute('two', array(
			'three'=>['path' =>'something', 'execute'=> 'controller=hello@world']
			));

		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'path-two/something']]));

		$this->assertEquals('two.three', $finding->route->getAbsoluteName());

		// complex. add routes on route 'two.two.one'
		$this->map->addOnRoute('two.two.one', array(
			'two'=>['path' =>'another-thing', 'execute'=> 'controller=hello@world']
			));

		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'path-two/sub-two/deep-one/another-thing']]));

		$this->assertEquals('two.two.one.two', $finding->route->getAbsoluteName());
	}

	public function testNestedRoute()
	{
		// 2 group
		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'path-two/sub-one']]));
		$this->assertEquals('two.one', $finding->route->getAbsoluteName());

		// 3 group
		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'path-two/sub-two/deep-one']]));
		$this->assertEquals('two.two.one', $finding->route->getAbsoluteName());
	}

	public function testParam()
	{
		$this->map->addRoutes(array('paramtest'=>['path' =>'[:param1]/[:param2]', 'execute'=> 'controller=hello@world']));

		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'ahmad/rahimie']]));
		$param = $finding->getParameters();

		$this->assertEquals(array('ahmad', 'rahimie'), array($param['param1'], $param['param2']));
	}

	public function testNestedParam()
	{
		$this->map->addRoutes(array(
		'r1'=>['path' =>'[:param1]/[:param2]', 'subroutes'=> array(
			'sr2'=>['path' =>'[:param3]'],
			'sr3'=>['path' =>'[:param4]/[:param5]', 'subroutes'=> array(
				'ssr4'=>['path' =>'path-ssr4/[:param6]']
				)]
			)]));

		// test route r1.sr2
		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'ahmad/rahimie/eimihar']]));
		$param = $finding->getParameters();

		// test route r1.sr3.ssr4
		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
		$this->assertEquals('ahmad', $param['param1']);
		$this->assertEquals('eimihar', $param['param3']);

		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'ahmad/rahimie/eimihar/rosengate/path-ssr4/exedra']]));
		$param = $finding->getParameters();

		$this->assertEquals('r1.sr3.ssr4', $finding->route->getAbsoluteName());
		$this->assertEquals('rosengate', $param['param5']);
		$this->assertEquals('exedra', $param['param6']);
	}

	public function testFarNestedParam()
	{
		$this->map->addRoutes(array(
			'r1' => ['path' => '[:param1]', 'subroutes' => array(
				'sr2' => ['subroutes' => array(
					'sr3' => ['subroutes' => array(
						'sr4' => ['subroutes' => array(
							'sr5' => ['path' => 'segment']
							)]
						)]
					)]
				)]
			));

		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'exedra/segment']]));

		$this->assertEquals('exedra', $finding->getParameters()['param1']);
	}

	public function testFindByName()
	{
		$this->map->addRoutes(array(
			'r1'=>['path' =>'[:param1]', 'subroutes'=> array(
				'sr2'=> ['path' =>'test', 'execute'=>function(){ }]
				)]
			));

		$route = $this->map->findRoute('r1.sr2');

		$this->assertEquals('r1.sr2', $route->getAbsoluteName());
	}

	public function testExecution()
	{
		$this->map->addRoutes(array(
			'r1'=>['path' =>'[:param1]', 'execute'=> function($exe)
				{
					return $exe->param('param1');
				}],
			'r2'=>['path' =>'[:param1]', 'subroutes'=> array(
				'sr3'=>['path' =>'[:test]', 'execute'=>function($exe)
					{
						return $exe->param('test');
					}]
				)],
			'r3'=>['path' =>'[:huga]/rita','middleware'=> function($exe){

				$exe->somethingFromMiddleware = 'something';

				return $exe->next($exe);
			}, 'subroutes'=> array(
				'sr4'=>['path' =>'[:teracotta]', 'execute'=> function($exe)
					{
						return $exe->somethingFromMiddleware;
					}]
				)]
			));
		// route r1 (name based route)
		$exe = $this->app->execute('r1', array('param1'=> 'something'));
		$this->assertEquals('something', $exe->response->getBody());

		// route r2.sr3 (request based route)
		$exe2 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => 'hello/world']]));
		$this->assertEquals('world', $exe2->response->getBody());

		// middleware on r3.sr4
		$exe3 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => 'hello/rita/world']]));
		$this->assertEquals('something', $exe3->response->getBody());
	}

	public function testPrioritizeExecution()
	{
		$this->map->addRoutes(array(
			'r1'=> ['path' => 'path1', 'subroutes'=> array(
				'sr2'=> ['path' => 'path2', 'execute'=> 'controller=somewhere@something', 'subroutes'=> array(
					'ssr3'=> ['path' => 'path3', 'execute'=>'controller=something@somewhere']
					)]
				)]
			));

		$finding = $this->map->findByRequest($this->createRequest(['uri' => ['path' => 'path1/path2']]));
		
		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
	}

	public function testSpecifiedNamedParam()
	{
		$app = new \Exedra\Application(__DIR__);

		$app->map->any('/[foo|bar:name]/[baz|bad:type]')->execute(function($exe)
		{
			return $exe->param('name').' '.$exe->param('type');
		});

		$this->assertEquals($app->respond(ServerRequest::createFromArray(['uri' => ['path' => '/foo/baz']]))->getBody(), 'foo baz');

		$this->assertEquals($app->respond(ServerRequest::createFromArray(['uri' => ['path' => '/bar/bad']]))->getBody(), 'bar bad');

        $this->assertInstanceOf(\Exedra\Routing\Finding::class, $app->map->findByRequest(ServerRequest::createFromArray(['uri' => ['path' => '/foo/bad']])));

        $this->expectException(\Exedra\Exception\RouteNotFoundException::class);

        $app->map->findByRequest(ServerRequest::createFromArray(['uri' => ['path' => '/bas/bad']]));

        $app->map->findByRequest(ServerRequest::createFromArray(['uri' => ['path' => '/foo/qux']]));
	}

	public function testMultioptional()
	{
		$this->map['foo']->get('mult/:foo?/:bar?/:baz?')->execute(function($exe)
		{
			return 'qux'.$exe->param('foo', 'hug').$exe->param('bar', 'tiz').$exe->param('baz', 'rel');
		})->group(function($mult)
		{
			$mult->get('/opt/[:dst?]/[:tst?]/[:rdt?]')->execute(function($exe)
			{
				$pre = implode('', $exe->params(['foo', 'bar', 'baz']));

				return $pre.'lud'.$exe->param('dst', 'jux').$exe->param('tst', 'jid').$exe->param('rdt', 'kit');
			});
		});

		$exe1 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult']]));

		$exe2 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz']]));

		$exe3 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz/bad']]));

		$exe4 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz/bad/lux']]));


		$exe5 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz/bad/lux/opt']]));

		$exe6 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz/bad/lux/opt/nop']]));

		$exe7 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz/bad/lux/opt/nop/top']]));

		$exe8 = $this->app->request(ServerRequest::createFromArray(['uri' => ['path' => '/mult/baz/bad/lux/opt/nop/top/qef']]));


		$this->assertEquals('quxhugtizrel', $exe1->response->getBody());

		$this->assertEquals('quxbaztizrel', $exe2->response->getBody());

		$this->assertEquals('quxbazbadrel', $exe3->response->getBody());

		$this->assertEquals('quxbazbadlux', $exe4->response->getBody());


		$this->assertEquals('bazbadluxludjuxjidkit', $exe5->response->getBody());

		$this->assertEquals('bazbadluxludnopjidkit', $exe6->response->getBody());

		$this->assertEquals('bazbadluxludnoptopkit', $exe7->response->getBody());

		$this->assertEquals('bazbadluxludnoptopqef', $exe8->response->getBody());
	}

	public function testLookupBasedSubroutes()
	{
		$this->map['fooo']->get('/')->group('bar.php');

		$this->assertEquals($this->app->execute('fooo.bad')->response->getBody(), 'baz');
	}

    public function testBaseURIRouting()
    {
        $this->map['hello']->uri(new \Exedra\Http\Uri('http://www.rosengate.com'))
            ->get('/helo')
            ->execute(function(){return 'w';});

        $this->map['foo']->uri(new \Exedra\Http\Uri('http://{sub}.rosengate.com/:hello'))
            ->group(function (\Exedra\Routing\Group $group) {
                $group['bar']->get('/:world/z')->execute(function(\Exedra\Runtime\Context $context) {
                    return $context->param('world') . ' ' . $context->param('hello') . ' ' . $context->param('sub');
                });
            });

        $request = $this->createRequest(['uri' => new \Exedra\Http\Uri('http://www.rosengate.com/helo')]);

        $this->assertEquals('w', $this->app->request($request)->response->getBody());
        $this->assertEquals('bar foo www', $this->sendUriRequest('http://www.rosengate.com/foo/bar/z'));
	}

    public function testURIDomainRouting()
    {
        $this->map->domain('test.rosengate.com')->get('/:foo')->group(function (\Exedra\Routing\Group $group) {
            $group->any('/:bar')->execute(function(\Exedra\Runtime\Context $context) {
                return $context->param('foo') . ' ' . $context->param('bar');
            });
        });

        $this->map->domain('192.168.0.100:16512')->get('/:foo')->group(function (\Exedra\Routing\Group $group) {
            $group->any('/:bar')->execute(function(\Exedra\Runtime\Context $context) {
                return $context->param('foo') . ' ' . $context->param('bar');
            });
        });

        $this->assertEquals('f b', $this->sendUriRequest('http://test.rosengate.com/f/b'));
        $this->assertEquals('d e', $this->sendUriRequest('http://192.168.0.100:16512/d/e'));
    }

    public function testURIDomainParameter()
    {
        $this->map->domain('{hello}.rosengate.com')->get('/:foo')->group(function (\Exedra\Routing\Group $group) {
            $group->any('/:bar')->execute(function(\Exedra\Runtime\Context $context) {
                return $context->param('hello') . ' ' . $context->param('foo') . ' ' . $context->param('bar');
            });
        });

        $this->assertEquals('test f b', $this->sendUriRequest('http://test.rosengate.com/f/b'));
	}
}