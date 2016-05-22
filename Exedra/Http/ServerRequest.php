<?php
namespace Exedra\Http;

/**
 * Implementation of both Psr\Http\Message\RequestInterface and Psr\Http\Message\ServerRequestInterface
 * Along with number of application methods
 */
class ServerRequest extends Message
{
	protected $method;

	protected $uri;

	protected $headers;

	protected $server;

	protected $cookies;

	protected $uploadedFiles = array();

	protected $parsedBody = array();

	protected $queryParams = array();

	protected $attributes = array();

	public function __construct(
		$method, 
		Uri $uri, 
		array $headers, 
		Stream $body, 
		array $server = array(), 
		array $cookies = array(), 
		array $uploadedFiles = array(),
		array $queryParams = array(),
		array $parsedBody= array())
	{
		$this->method = strtoupper($method);

		$this->uri = $uri;
		
		$this->setHeaders($headers);

		$this->body = $body;

		$this->server = $server;

		$this->cookies = $cookies;

		$this->uploadedFiles = $uploadedFiles;

		if($parsedBody)
			$this->parsedBody = $parsedBody;
		else
			if($body->isSeekable())
				parse_str($body->rewind()->getContents(), $this->parsedBody);
			else
				parse_str($body->getContents(), $this->parsedBody);

		if($queryParams)
			$this->queryParams = $queryParams;
		else
			parse_str($this->uri->getQuery(), $this->queryParams);
	}

	protected function setHeaderLines(array $headerLines)
	{
		$this->headerLines = $headerLines;
	}

	/**
	 * Instantiate request from php _SERVER variable
	 * @param array server
	 */
	public static function createFromGlobals(array $server = array())
	{
		$server = !$server ? $_SERVER : $server;

		$uriParts = parse_url($server['REQUEST_URI']);

		$uriParts['host'] = $server['SERVER_NAME'];
		$uriParts['port'] = $server['SERVER_PORT'];
		$uriParts['scheme'] = isset($server['REQUEST_SCHEME']) ? $server['REQUEST_SCHEME'] : ( isset($server['HTTPS']) && $server['HTTPS'] == 'on' ? 'https' : 'http' );

		if(function_exists('getallheaders'))
		{
			// a correct case already
			$apacheHeaders = getallheaders();

			foreach($apacheHeaders as $header => $value)
				$headers[$header] = array_map('trim', explode(',', $value));
		}
		else
		{
			$headers = array();

			// normalize the header key
			foreach($server as $key => $value)
			{
				if(substr($key, 0, 5) != 'HTTP_')
					continue;

				$name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));

				$headers[$name] = array_map('trim', explode(',', $value));
			}
		}

		return new static(
			$server['REQUEST_METHOD'],
			new Uri($uriParts),
			$headers,
			Stream::createFromContents(file_get_contents('php://input')),
			$server,
			$_COOKIE,
			UploadedFile::createFromGlobals($_FILES),
			$_GET,
			$_POST
		);
	}

	/**
	 * Create request from given array
	 * @param array params
	 */
	public static function createFromArray(array $params)
	{
		return new static(
			isset($params['method']) ? $params['method'] : 'GET',
			new Uri(isset($params['uri']) ? $params['uri'] : ''),
			isset($params['headers']) ? $params['headers'] : array(),
			isset($params['body']) ? (is_resource($params['body']) ? new Stream($params['body']) : Stream::createFromContents($params['body'])) : Stream::createFromContents(''),
			isset($params['server']) ? $params['server'] : array(),
			isset($params['cookies']) ? $params['cookies'] : array(),
			isset($params['queryParams']) ? $params['queryParams'] : array(),
			isset($params['parsedBody']) ? $params['parsedBody'] : array()
		);
	}

	public function getRequestTarget()
	{
		if($this->requestTarget)
			return $this->requestTarget;

		$target = $this->uri->getPath();

		if($target === null)
			$target = '/';

		if($query = $this->uri->getQuery())
			$target = '?'.$query;

		return $target;
	}

	public function setRequestTarget($target)
	{
		$this->requestTarget = $target;

		return $this->requestTarget;
	}

	public function withRequestTarget()
	{
		$request = clone $this;

		return $request->setRequestTarget($target);
	}

	public function setMethod($method)
	{
		$this->method = strtoupper($method);
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function withMethod($method)
	{
		$request = clone $this;

		$request->setMethod($method);

		return $request;
	}

	public function getUri()
	{
		return $this->uri;
	}

	public function setUri($uri)
	{
		if(is_string($uri) || is_array($uri))
			$this->uri = new Uri($uri);
		else if($uri instanceof Uri)
			$this->uri = $uri;
		else
			throw new \InvalidArgumentException('Invalid uri. Must be string, array, or Uri');
	}

	public function withUri(Uri $uri, $preserveHost = false)
	{
		$request = clone $this;

		$this->uri = $uri;

		return $this->uri;
	}

	/**
	 * Below this is the implementation for /Psr/Http/ServerRequest
	 */

	public function getServerParams()
	{
		return $this->server;
	}

	public function getCookieParams()
	{
		return $this->cookies;
	}

	public function withCookieParams(array $params)
	{
		$request = clone $this;

		$request->cookies = $params;

		return $request;
	}

	public function getQueryParams()
	{
		return $this->queryParams;
	}

	public function withQueryParams($queryParams)
	{
		$request = clone $this;

		$request->queryParams = $queryParams;

		return $request;
	}

	public function getUploadedFiles()
	{
		return $this->uploadedFiles;
	}

	public function withUploadedFiles(array $uploadedFiles)
	{
		$request = clone $this;

		$request->uploadedFiles = $uploadedFiles;

		return $request;
	}

	public function getParsedBody()
	{
		return $this->parsedBody;
	}

	public function setParsedBody(array $parsedBody)
	{
		$this->parsedBody = $parsedBody;

		return $this;
	}

	public function withParsedBody(array $parsedBody)
	{
		$request = clone $this;

		return $request->setParsedBody($parsedBody);
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function getAttribute($name, $default = null)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
	}

	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;

		return $this;
	}

	public function withAttribute($name, $value)
	{
		$request = clone $this;

		return $request->setAttribute($name, $value);;
	}

	public function removeAttribute($name)
	{
		unset($this->attributes[$name]);

		return $this;
	}

	public function withoutAttribute($name)
	{
		$request = clone $this;

		return $request->removeAttribute($name);
	}

	public function isAjax()
	{
		return strtolower($this->getHeaderLine('x-requested-with')) == 'xmlhttprequest';
	}

	public function header()
	{
		return $this->headers;
	}

	public function isMethod($method)
	{
		return strtolower($method) == strtolower($this->method);
	}

	public function resolveUri()
	{
		return $this->resolveUriPath();
	}

	public function resolveUriPath()
	{
		if(!$this->server)
			return;

		list($requestUri) = explode('?', $this->server['REQUEST_URI']);

		if(strpos($requestUri, $this->server['SCRIPT_NAME']) === 0)
		{
			$basePath = $this->server['SCRIPT_NAME'];
		}
		else
		{
			$basePath = explode('/', $this->server['SCRIPT_NAME']);
			array_pop($basePath);
			$basePath = implode('/', $basePath);
		}

		$requestUri = trim($requestUri, '/');

		$requestUri = '/'.trim(substr($requestUri, strlen(trim($basePath, '/'))), '/');

		$this->uri->setPath($requestUri);
	}
}



?>