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
		$result = $this->map->find(['uri'=> '']);

		$this->assertEquals('empty', $result['route']->getAbsoluteName());
	}

	public function testRouteOneLevel()
	{
		$result = $this->map->find(['uri'=> 'uri-one']);

		// confirm by route name.
		$this->assertEquals('one', $result['route']->getAbsoluteName());
	}

	public function testNestedRoute()
	{
		// 2 level
		$result = $this->map->find(['uri'=> 'uri-two/sub-one']);
		$this->assertEquals('two.one', $result['route']->getAbsoluteName());

		// 3 level
		$result = $this->map->find(['uri'=> 'uri-two/sub-two/deep-one']);
		$this->assertEquals('two.two.one', $result['route']->getAbsoluteName());
	}

	public function testParam()
	{
		$this->map->addRoute(array('paramtest'=>['uri'=>'[:param1]/[:param2]', 'execute'=> 'controller=hello@world']));

		$result = $this->map->find(['uri'=> 'ahmad/rahimie']);
		$param = $result['parameter'];

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
		$result = $this->map->find(['uri'=> 'ahmad/rahimie/eimihar']);
		$param = $result['parameter'];

		// test route r1.sr3.ssr4
		$this->assertEquals('r1.sr2', $result['route']->getAbsoluteName());
		$this->assertEquals('ahmad', $param['param1']);
		$this->assertEquals('eimihar', $param['param3']);

		$result = $this->map->find(['uri'=> 'ahmad/rahimie/eimihar/rosengate/uri-ssr4/exedra']);
		$param = $result['parameter'];

		$this->assertEquals('r1.sr3.ssr4', $result['route']->getAbsoluteName());
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

		$route = $this->map->findByName('r1.sr2');

		$this->assertEquals('r1.sr2', $route->getAbsoluteName());
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
			'r3'=>['uri'=>'[:huga]/rita','bind:middleware'=> function($exe){

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
}