<?php
namespace Exedra\Exedrian;

class HTTPRequest
{
	protected $parameters	= Array();
	protected $cookies		= Array();
	protected $headers	 	= Array();
	protected $server		= Array();
	protected $method		= Array();

	public function __construct()
	{
		# initiate basic request data into properties.
		$this->parameters	= Array(
						"get"=>$_GET,
						"post"=>$_POST,
									);

		$this->headers		= function_exists("getallheaders")?getallheaders():null;
		$this->server		= $_SERVER;

		# method
		$this->method		= $this->server['REQUEST_METHOD'];
	}

	public function getUri()
	{
		return $this->uri;
	}

	## cookie getter.
	public function getCookies()
	{
		return $this->cookies;
	}

	## header getter.
	public function getHeaders()
	{
		return $this->headers;
	}

	## server variable getter.
	public function getServer()
	{
		return $this->server;
	}

	public function getParameter($method = null,$name = null)
	{
		if(!$method && !$name)
			return $this->parameters;

		$method	= strtolower($method);
		return !isset($this->parameters[$method][$name])?null:$this->parameters[$method][$name];
	}

	public function isSecure()
	{
		
	}

	public function isAjax()
	{
		return $this->headers['X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

	public function getMethod()
	{
		return strtolower($this->server['REQUEST_METHOD']);
	}
}


?>