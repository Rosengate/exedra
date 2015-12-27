<?php
namespace Exedra\HTTP;

/**
 * Simple HTTP Request class
 * To be overhauled and follow PSR-7 
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
	 * @return void
	 */
	protected function buildRequest(array $param = array())
	{
		// initiate basic request data into properties.
		$this->parameters	= isset($param['parameters']) ? $param['parameters'] : array("get" => $_GET, "post" => $_POST);
		$this->server		= isset($param['server']) ? $param['server'] : $_SERVER;
		$this->headers		= isset($param['header']) ? $param['header'] : (function_exists("getallheaders")? getallheaders() : $this->buildHeadersFromServer());
		$this->method		= isset($param['method']) ? $param['method'] : (isset($this->server['REQUEST_METHOD'])?$this->server['REQUEST_METHOD'] : null);

		if(isset($param['path']))
		{
			// check if in uri there're still a query string. pass as method's parameter.
			$paths = explode('?', $param['path']);
			$this->uriPath = $paths[0];
			
			// if has some query string passed on uri, treat it as get parameter.
			if(isset($paths[1]))
				parse_str($paths[1], $this->parameters['get']);
		}
		else
		{
			if(isset($this->server['REQUEST_URI']))
				$this->buildUriPath();
		}
	}

	/**
	 * Alias to resolveUriPath
	 * To be removed in 0.3.0 once adapted PSR-7
	 * @return void
	 */
	public function resolveUri()
	{
		return $this->resolveUriPath();
	}

	/**
	 * This functionality may be useful when your application files located farther from root directory.
	 * For example let say, you have request uri like : /www/myapps/exedra-web/
	 * You can use this method to resolve and base your URI accordingly
	 * Actually i wrote this for XAMPP-like build.
	 * @return void
	 */
	public function resolveUriPath()
	{
		list($request_uri) = explode("?",$this->server['REQUEST_URI']);

		// get base path from php_self
		if(strpos($request_uri, $_SERVER['SCRIPT_NAME']) === 0)
		{
			$base_path = $_SERVER['SCRIPT_NAME'];
		}
		else
		{
			$base_path	= explode("/", $_SERVER['SCRIPT_NAME']);
			array_pop($base_path);
			$base_path	= implode("/",$base_path);
		}

		$request_uri = trim($request_uri,"/");
		
		// remove base_path
		$request_uri = trim(substr($request_uri,strlen(trim($base_path,"/"))),"/");

		$this->setUriPath($request_uri);
	}

	/**
	 * Build header from $_SERVER variable
	 * http://stackoverflow.com/questions/13224615/get-the-http-headers-from-current-request-in-php
	 * @return array
	 */
	protected function buildHeadersFromServer()
	{
		if (!is_array($this->server))
            return array();

        $headers = array();

        foreach ($this->server as $name => $value)
            if (substr($name, 0, 5) == 'HTTP_')
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;

        return $headers;
	}

	/**
	 * Build URI path.
	 */
	protected function buildUriPath()
	{
		list($request_uri) = explode('?', $this->server['REQUEST_URI']);

		$this->setUriPath(trim($request_uri, '/'));
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
	 * Set uri path of the request
	 * @return void
	 */
	public function setUriPath($uriPath)
	{
		$this->uriPath = $uriPath;
	}

	/**
	 * Return uri of the request
	 * @return string
	 */
	public function getUriPath()
	{
		return $this->uriPath;
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
	public function getHeader($key = null)
	{
		if(!$key)
			return $this->headers;

		if(!isset($this->headers[$key]))
			return;

		return $this->headers[$key];
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
		return isset($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
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