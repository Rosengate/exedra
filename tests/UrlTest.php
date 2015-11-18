<?php
require_once "Exedra/Exedra.php";

class UrlTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->exedra = new \Exedra\Exedra(__DIR__);

		$this->app = $this->exedra->build('app', function()
		{

		});

		$this->map = $this->app->map;
	}

	public function testUrlCreate()
	{
		$this->map->addRoutes(array(
			'tester'=>['uri'=> 'tester/[:route]', 'execute'=>function($exe)
				{
					$params = $exe->param('params') ? : array();
					return $exe->url->create($exe->param('route'), $params);
				}],
			'r1'=>['uri'=> 'uri1/uri2', 'execute'=>function(){ }],
			'r2'=>['uri'=> 'uri1/[:param]', 'execute'=>function(){ }]
			));

		// simple uri
		$this->assertEquals('/uri1/uri2', $this->app->execute('tester', array('route'=> 'r1')));

		$this->assertEquals('/uri1/simple-param', $this->app->execute('tester', 
			array(
				'route'=> 'r2',
				'params'=> array('param'=> 'simple-param'))
				));
	}
}



?>