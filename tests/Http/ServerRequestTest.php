<?php
class HttpServerRequestTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{

	}

	public function testCreateFromArray()
	{
		$request = \Exedra\Http\ServerRequest::createFromArray(array(
			'method' => 'GET',
			'uri' => 'http://google.com',
			'headers' => array(
				'Referer' => array('http://foo.com'),
				'X-Requested-With' => array('XMLHttpRequest')
				)
			));

		$this->assertEquals('GET', $request->getMethod());

		$this->assertEquals('http://google.com', (string) $request->getUri());

		$this->assertEquals('http://foo.com', $request->getHeaderLine('referer'));

		$this->assertTrue($request->isAjax());
	}

	public function testUriParts()
	{
		$request = \Exedra\Http\ServerRequest::createFromArray(array(
			'method' => 'GET',
			'uri' => 'http://google.com:80/foo/bar?baz=bad#fragman',
			'headers' => array(
				'referer' => array('http://foo.com')
				)
			));

		$uri = $request->getUri();

		$this->assertEquals('http://google.com:80/foo/bar?baz=bad#fragman', (string) $uri);

		$this->assertEquals('google.com', $uri->getHost());

		$this->assertEquals('http', $uri->getScheme());

		$this->assertEquals(80, $uri->getPort());

		$this->assertEquals('google.com:80', $uri->getAuthority());

		$this->assertEquals('/foo/bar', $uri->getPath());

		$this->assertEquals('baz=bad', $uri->getQuery());

		$this->assertEquals('fragman', $uri->getFragment());

		$this->assertEquals($uri->getQuery(), http_build_query($request->getQueryParams()));

	}
}