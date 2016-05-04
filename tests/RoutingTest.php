<?php
class RoutingTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		// build a basic case
		$app = $this->app = new \Exedra\Application(__DIR__);

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

	public function testApp()
	{
		$this->assertEquals('Exedra\Application', get_class($this->app));
	}

	public function testEmptyPath()
	{
		$finding = $this->map->find(['uri' => ['path' => '']]);

		$this->assertEquals('empty', $finding->route->getAbsoluteName());
	}

	public function testRouteOneLevel()
	{
		$finding = $this->map->find(['uri' => ['path' => 'path-one']]);

		// confirm by route name.
		$this->assertEquals('one', $finding->route->getAbsoluteName());
	}

	public function testAddOnRoute()
	{
		// add routes on route 'two'
		$this->map->addOnRoute('two', array(
			'three'=>['path' =>'something', 'execute'=> 'controller=hello@world']
			));

		$finding = $this->map->find(['uri' => ['path' => 'path-two/something']]);

		$this->assertEquals('two.three', $finding->route->getAbsoluteName());

		// complex. add routes on route 'two.two.one'
		$this->map->addOnRoute('two.two.one', array(
			'two'=>['path' =>'another-thing', 'execute'=> 'controller=hello@world']
			));

		$finding = $this->map->find(['uri' => ['path' => 'path-two/sub-two/deep-one/another-thing']]);

		$this->assertEquals('two.two.one.two', $finding->route->getAbsoluteName());
	}

	public function testNestedRoute()
	{
		// 2 level
		$finding = $this->map->find(['uri' => ['path' => 'path-two/sub-one']]);
		$this->assertEquals('two.one', $finding->route->getAbsoluteName());

		// 3 level
		$finding = $this->map->find(['uri' => ['path' => 'path-two/sub-two/deep-one']]);
		$this->assertEquals('two.two.one', $finding->route->getAbsoluteName());
	}

	public function testParam()
	{
		$this->map->addRoutes(array('paramtest'=>['path' =>'[:param1]/[:param2]', 'execute'=> 'controller=hello@world']));

		$finding = $this->map->find(['uri' => ['path' => 'ahmad/rahimie']]);
		$param = $finding->parameters;

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
		$finding = $this->map->find(['uri' => ['path' => 'ahmad/rahimie/eimihar']]);
		$param = $finding->parameters;

		// test route r1.sr3.ssr4
		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
		$this->assertEquals('ahmad', $param['param1']);
		$this->assertEquals('eimihar', $param['param3']);

		$finding = $this->map->find(['uri' => ['path' => 'ahmad/rahimie/eimihar/rosengate/path-ssr4/exedra']]);
		$param = $finding->parameters;

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

		$finding = $this->map->find(['uri' => ['path' => 'exedra/segment']]);

		$this->assertEquals('exedra', $finding->parameters['param1']);
	}

	public function testFindByName()
	{
		$this->map->addRoutes(array(
			'r1'=>['path' =>'[:param1]', 'subroutes'=> array(
				'sr2'=> ['path' =>'test', 'execute'=>function(){ }]
				)]
			));

		$finding = $this->map->findByName('r1.sr2');

		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
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
		$exe2 = $this->app->request(\Exedra\Http\ServerRequest::createFromArray(['uri' => ['path' => 'hello/world']]));
		$this->assertEquals('world', $exe2->response->getBody());

		// middleware on r3.sr4
		$exe3 = $this->app->request(\Exedra\Http\ServerRequest::createFromArray(['uri' => ['path' => 'hello/rita/world']]));
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

		$finding = $this->map->find(['uri' => ['path' => 'path1/path2']]);
		
		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
	}
}