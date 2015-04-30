<?php
require_once "Exedra/Exedra.php";

class RoutingTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->exedra = new \Exedra\Exedra(__DIR__);

		// build a basic case
		$this->app = $this->exedra->build('app', function($app)
		{
			$app->map->addRoute(array(
				'one'=>['uri'=>'uri-one', 'execute'=> 'controller=hello@world'],
				'two'=>['uri'=>'uri-two', 'subroute'=> array(
					'one'=> ['uri'=>'sub-one', 'execute'=> 'controller=hello@world'],
					'two'=> ['uri'=>'sub-two', 'subroute'=> array(
						'one'=> ['uri'=>'deep-one', 'execute'=> 'controller=hello@world'],
						)]
					)]
				));

			$app->map->addRoute(array(
				'empty'=>['uri'=>'', 'execute'=> 'controller=hello@world']
				));
		});

		$this->map = $this->app->map;
	}

	public function testApp()
	{
		$this->assertEquals('Exedra\Exedra', get_class($this->exedra));

		$this->assertEquals('Exedra\Application\Application', get_class($this->app));
	}

	public function testEmptyUri()
	{
		$finding = $this->map->find(['uri'=> '']);

		$this->assertEquals('empty', $finding->route->getAbsoluteName());
	}

	public function testRouteOneLevel()
	{
		$finding = $this->map->find(['uri'=> 'uri-one']);

		// confirm by route name.
		$this->assertEquals('one', $finding->route->getAbsoluteName());
	}

	public function testAddOnRoute()
	{
		// add routes on route 'two'
		$this->map->addOnRoute('two', array(
			'three'=>['uri'=>'something', 'execute'=> 'controller=hello@world']
			));

		$finding = $this->map->find(['uri'=> 'uri-two/something']);

		$this->assertEquals('two.three', $finding->route->getAbsoluteName());

		// complex. add routes on route 'two.two.one'
		$this->map->addOnRoute('two.two.one', array(
			'two'=>['uri'=>'another-thing', 'execute'=> 'controller=hello@world']
			));

		$finding = $this->map->find(['uri'=> 'uri-two/sub-two/deep-one/another-thing']);

		$this->assertEquals('two.two.one.two', $finding->route->getAbsoluteName());
	}

	public function testNestedRoute()
	{
		// 2 level
		$finding = $this->map->find(['uri'=> 'uri-two/sub-one']);
		$this->assertEquals('two.one', $finding->route->getAbsoluteName());

		// 3 level
		$finding = $this->map->find(['uri'=> 'uri-two/sub-two/deep-one']);
		$this->assertEquals('two.two.one', $finding->route->getAbsoluteName());
	}

	public function testParam()
	{
		$this->map->addRoute(array('paramtest'=>['uri'=>'[:param1]/[:param2]', 'execute'=> 'controller=hello@world']));

		$finding = $this->map->find(['uri'=> 'ahmad/rahimie']);
		$param = $finding->parameters;

		$this->assertEquals(array('ahmad', 'rahimie'), array($param['param1'], $param['param2']));
	}

	public function testNestedParam()
	{
		$this->map->addRoute(array(
		'r1'=>['uri'=>'[:param1]/[:param2]', 'subroute'=> array(
			'sr2'=>['uri'=>'[:param3]'],
			'sr3'=>['uri'=>'[:param4]/[:param5]', 'subroute'=> array(
				'ssr4'=>['uri'=>'uri-ssr4/[:param6]']
				)]
			)]));

		// test route r1.sr2
		$finding = $this->map->find(['uri'=> 'ahmad/rahimie/eimihar']);
		$param = $finding->parameters;

		// test route r1.sr3.ssr4
		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
		$this->assertEquals('ahmad', $param['param1']);
		$this->assertEquals('eimihar', $param['param3']);

		$finding = $this->map->find(['uri'=> 'ahmad/rahimie/eimihar/rosengate/uri-ssr4/exedra']);
		$param = $finding->parameters;

		$this->assertEquals('r1.sr3.ssr4', $finding->route->getAbsoluteName());
		$this->assertEquals('rosengate', $param['param5']);
		$this->assertEquals('exedra', $param['param6']);
	}

	public function testFindByName()
	{
		$this->map->addRoute(array(
			'r1'=>['uri'=>'[:param1]', 'subroute'=> array(
				'sr2'=> ['uri'=>'test', 'execute'=>function(){ }]
				)]
			));

		$finding = $this->map->findByName('r1.sr2');

		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
	}

	public function testExecution()
	{
		$this->map->addRoute(array(
			'r1'=>['uri'=>'[:param1]', 'execute'=> function($exe)
				{
					return $exe->param('param1');
				}],
			'r2'=>['uri'=>'[:param1]', 'subroute'=> array(
				'sr3'=>['uri'=>'[:test]', 'execute'=>function($exe)
					{
						return $exe->param('test');
					}]
				)],
			'r3'=>['uri'=>'[:huga]/rita','middleware'=> function($exe){

				$exe->somethingFromMiddleware = 'something';

				return $exe->next($exe);
			}, 'subroute'=> array(
				'sr4'=>['uri'=>'[:teracotta]', 'execute'=> function($exe)
					{
						return $exe->somethingFromMiddleware;
					}]
				)]
			));

		// route r1 (name based route)
		$response = $this->app->execute('r1', array('param1'=> 'something'));
		$this->assertEquals('something', $response);

		// route r2.sr3 (query based route)
		$response2 = $this->app->execute(['uri'=> 'hello/world']);
		$this->assertEquals('world', $response2);

		// middleware on r3.sr4
		$response3 = $this->app->execute(['uri'=> 'hello/rita/world']);
		$this->assertEquals('something', $response3);
	}

	public function testPrioritizeExecution()
	{
		$this->map->addRoute(array(
			'r1'=> ['uri'=> 'uri1', 'subroute'=> array(
				'sr2'=> ['uri'=> 'uri2', 'execute'=> 'controller=somewhere@something', 'subroute'=> array(
					'ssr3'=> ['uri'=> 'uri3', 'execute'=>'controller=something@somewhere']
					)]
				)]
			));

		$finding = $this->map->find(['uri'=> 'uri1/uri2']);
		
		$this->assertEquals('r1.sr2', $finding->route->getAbsoluteName());
	}
}