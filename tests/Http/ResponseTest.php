<?php
class HttpResponseTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->response = \Exedra\Http\Response::createEmptyResponse();
	}

	public function testStatus()
	{
		$this->response->setStatus(404);

		$this->assertEquals(404, $this->response->getStatusCode());

		$this->assertEquals('Not Found', $this->response->getReasonPhrase());
	}

	public function testRedirectHeader()
	{
		$this->response->redirect('http://google.com');

		$this->assertEquals('http://google.com', $this->response->getHeaderLine('location'));
	}

	public function testRefresh()
	{
		$this->response->refresh(10);

		$this->assertEquals(10, $this->response->getHeaderLine('refresh'));
	}
}