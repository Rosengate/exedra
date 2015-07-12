<?php
namespace Exedra\HTTP;

/**
 * Simple HTTP Request class
 */

class Request
{
	/**
	 * Request parameters
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Request cookies
	 * @var cookies
	 */
	protected $cookies = array();

	/**
	 * Request header
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Server variable
	 * @var array
	 */
	protected $server = array();

	/**
	 * Request method
	 * @var string
	 */
	protected $method;

	/**
	 * Parameter method, to help dealing on retrieving param by order.
	 * @var string
	 */
	// protected $paramMethod;

	/**
	 * Accessible referenced _GET parameter
	 * @var array
	 */
	public $get;
	
	/**
	 * Accessible referenced _POST parameter
	 * @var array
	 */
	public $post;

	public function __construct(array $param = array())
	{
		$this->buildRequest($param);
	}

	/**
	 * Build request with given parameter.
	 */
	protected function buildRequest(array $param = array())
	{
		// initiate basic request data into properties.
		$this->parameters	= isset($param['parameters']) ? $param['parameters'] : array("get"=>$_GET,"post"=>$_POST);
		
		// refer post and get in a new variable.
		// $this->post			= &$this->parameters['post'];
		// $this->get			= &$this->parameters['get'];
		
		$this->header		= isset($param['header'])?$param['header'] : (function_exists("getallheaders")?getallheaders():null);
		$this->server		= isset($param['server'])?$param['server'] : $_SERVER;
		$this->method		= isset($param['method'])?$param['method'] : (isset($this->server['REQUEST_METHOD'])?$this->server['REQUEST_METHOD'] : null);
		// $this->uri			= isset($param['uri'])?$param['uri'] : (isset($this->server['REQUEST_URI']) ? $this->buildURI($this->server['REQUEST_URI']) : null );

		if(isset($param['uri']))
		{
			// check if in uri there're still a query string. pass as method's parameter.
			$uris = explode('?', $param['uri']);
			$this->uri = $uris[0];
			
			// if has some query string passed on uri, treat it as get parameter.
			if(isset($uris[1]))
				parse_str($uris[1], $this->parameters['get']);
		}
		else
		{
			$this->uri = $this->buildURI($this->server['REQUEST_URI']);
		}

		
	}

	/**
	 * Build URI.
	 * @param string request_uri
	 */
	protected function buildURI($request_uri)
	{
		list($request_uri) = explode("?",$request_uri);

		// get base path from php_self
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
		
		// substring base_path
		$request_uri = trim(substr($request_uri,strlen(trim($base_path,"/"))),"/");

		return $request_uri;
	}

	/**
	 * Get parameter by passed method.
	 * @param string method
	 * @param string key
	 * @param mixed default
	 * @return mixed
	 */
	public function paramBag($method, $key = null, $default = null)
	{
		if($key === null)
			return $this->parameters[$method];
		else
			return isset($this->parameters[$method][$key]) ? $this->parameters[$method][$key] : $default;
	}

	/**
	 * Get _get parameter
	 * @param string key
	 * @param mixed default
	 * @return mixed 
	 */
	public function paramGet($key = null, $default = null)
	{
		return $this->paramBag('get', $key, $default);
	}

	/**
	 * Get _post parameter
	 * @param string key
	 * @param mixed default
	 * @return mixed 
	 */
	public function paramPost($key = null, $default = null)
	{
		return $this->paramBag('post', $key, $default);
	}

	/**
	 * Check has the given parameter(s) or not, with current method as priority
	 * @return boolean
	 */
	public function hasParam($key)
	{
		$method = $this->isMethod('get') ? 'get' : 'post';

		if(isset($this->parameters[$method][$key]))
			return true;
		else if(isset($this->parameters[$inversedMethod = ($method == 'get' ? 'post' : 'get')][$key]))
			return true;

		return false;
	}

	/**
	 * Has the given GET parameter
	 * @return boolean
	 */
	public function hasGet($key)
	{
		return isset($this->parameters['get'][$key]);
	}

	/**
	 * Has the given POST parameter
	 * @return boolean
	 */
	public function hasPost($key)
	{
		return isset($this->parameters['post'][$key]);
	}

	/**
	 * Get request parameter with current method as priority.
	 * @param string key
	 * @param mixed default
	 * @return mixed value
	 */
	public function param($key = null, $default = null)
	{
		$method = $this->isMethod('get') ? 'get' : 'post';
		
		// if no key at all, pass the current method's parameters.
		if(!$key)
			return $this->parameters[$method];

		if(isset($this->parameters[$method][$key]))
			return $this->parameters[$method][$key];
		else if(isset($this->parameters[$inversedMethod = ($method == 'get' ? 'post' : 'get')][$key]))
			return $this->parameters[$inversedMethod][$key];

		return $default;
	}

	/**
	 * Alias to paramGet
	 * @param string key
	 * @param mixed default
	 * @param mixed
	 */
	public function get($key = null, $default = null)
	{
		return $this->paramGet($key, $default);
	}

	/**
	 * Alias to paramPost
	 * @param string key
	 * @param mixed default
	 * @return mixed
	 */
	public function post($key = null, $default = null)
	{
		return $this->paramPost($key, $default);
	}

	/**
	 * Return uri of the request
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Return _cookies
	 * @return array
	 */
	public function getCookies()
	{
		return $this->cookies;
	}

	/**
	 * Return request _header
	 * @return array
	 */
	public function getHeader()
	{
		return $this->header;
	}

	/**
	 * Get server variable
	 * @return array
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Get request parameter by given method.
	 * @param string method
	 * @param string name
	 */
	public function getParameter($method = null,$name = null)
	{
		if(!$method && !$name)
			return $this->parameters;

		$method	= strtolower($method);
		return !isset($this->parameters[$method][$name])?null:$this->parameters[$method][$name];
	}

	/**
	 * Check whether request is secure or not.
	 * @return boolean
	 */
	public function isSecure()
	{
		return isset($this->server['HTTPS']) && $_SERVER['HTTPS'] != 'off';
	}

	/**
	 * Boolean whether the request is ajax, or not.
	 * @return boolean
	 */
	public function isAjax()
	{
		$xRequestedWith = isset($this->server['X_REQUESTED_WITH']) ? $this->server['X_REQUESTED_WITH'] : (!isset($this->header['X-Requested-With']) ? false : $this->header['X-Requested-With']);
		
		return strtolower($xRequestedWith) == 'xmlhttprequest';
	}

	/**
	 * Get request method
	 * @return string (strtolowered)
	 */
	public function getMethod()
	{
		return strtolower($this->method);
	}

	/**
	 * Equate with the current method
	 * @return boolean
	 */
	public function isMethod($method)
	{
		return $this->getMethod() == strtolower($method);
	}
}


?>