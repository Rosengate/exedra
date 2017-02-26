<?php
class ResponseTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__);

        $this->app->provider->add(\Exedra\Support\Provider\Framework::class);
	}

	public function testResponse()
	{
		$response = \Exedra\Http\Response::createEmptyResponse();

		$this->assertEquals(\Exedra\Http\Response::CLASS, get_class($response));

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertEquals('OK', $response->getReasonPhrase());
	}

	public function testExecutedRedirectRefreshAndToRoute()
	{
		$test = $this;

		$this->app->map['foo']->any('/')->execute(function($exe) use($test)
		{
			$exe->redirect->url('http://example.com');

			$test->assertEquals('http://example.com', $exe->response->getHeaderLine('location'));

			$exe->redirect->refresh();

			$test->assertEquals(0, $exe->response->getHeaderLine('refresh'));

			$exe->redirect->route('@bar');

			$test->assertEquals('/foo', $exe->response->getHeaderLine('location'));
		});

		$this->app->map['bar']->any('/foo')->execute(function()
		{
			
		});

		$this->app->execute('foo');
	}
}