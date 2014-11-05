<?php
namespace Exedra\Application;

class Request
{
	private $data;
	private $httpRequest;

	## httpRequest variable.
	private $cookies;
	private $method;
	private $server;
	private $headers;
	private $uri;
	private $namedParameter;

	public function __construct($httpRequest)
	{
		$this->httpRequest	= $httpRequest;

		## bind the same variable the httpRequest have.
		$this->cookies		= $this->httpRequest->getCookies();
		$this->method		= $this->httpRequest->getMethod();
		$this->server		= $this->httpRequest->getServer();
		$this->headers		= $this->httpRequest->getHeaders();

		## use the same uri and parameter.
		$this->uri			= $this->httpRequest->getUri();
		$this->parameters	= $this->httpRequest->getParameter();
	}

	public function setURI($uri)
	{
		$this->uri	= $uri;
		return $this;
	}

	public function setMethod($method)
	{
		$this->method	= strtolower($method);
		return $this;
	}

	public function getUri()
	{
		return $this->uri;
	}

	public function getParameter($method,$name = null)
	{
		return $this->httpRequest->getParameter($method,$name);
	}

	public function getNamedParameter($name)
	{
		return $this->namedParameter[$name];
	}

	public function getMethod()
	{
		return $this->method;
	}

	
}



?>