<?php
namespace Exedra\Exedrian\HTTP;

class Request
{
	protected $parameters	= Array();
	protected $cookies		= Array();
	protected $headers	 	= Array();
	protected $server		= Array();
	protected $method		= Array();

	public $post;
	public $get;

	public function __construct($param = Array())
	{
		$this->buildRequest($param);
	}

	private function buildRequest($param = Array())
	{
		# initiate basic request data into properties.
		$this->parameters	= isset($param['parameters'])?$param['parameters']:Array("get"=>$_GET,"post"=>$_POST);
		$this->header		= isset($param['header'])?$param['header']:(function_exists("getallheaders")?getallheaders():null);
		$this->server		= isset($param['server'])?$param['server']:$_SERVER;
		$this->method		= isset($param['method'])?$param['method']:$this->server['REQUEST_METHOD'];
		$this->uri			= isset($param['uri'])?$param['uri']:$this->buildURI($_SERVER['REQUEST_URI']);

		# refer post and get in a new variable.
		$this->post			= &$this->parameters['post'];
		$this->get			= &$this->parameters['get'];
	}

	private function buildURI($request_uri)
	{
		list($request_uri) = explode("?",$request_uri);

		## get base path from php_self
		if(strpos($request_uri,$_SERVER['SCRIPT_NAME']) === 0)
		{
			$base_path = $_SERVER['SCRIPT_NAME'];
		}
		else
		{
			$base_path	= explode("/",$_SERVER['SCRIPT_NAME']);
			array_pop($base_path);
			$base_path	= implode("/",$base_path);
		}

		$request_uri = trim($request_uri,"/");
		## substring base_path
		$request_uri = trim(substr($request_uri,strlen(trim($base_path,"/"))),"/");

		return $request_uri;
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
		return $this->header;
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
		if(!isset($this->header['X_REQUESTED_WITH']))
			return false;
		
		return $this->header['X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

	public function getMethod()
	{
		return strtolower($this->server['REQUEST_METHOD']);
	}
}


?>