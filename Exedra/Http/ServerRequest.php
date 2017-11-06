<?php
namespace Exedra\Http;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Implementation of both Psr\Http\Message\RequestInterface and Psr\Http\Message\ServerRequestInterface
 * Along with number of application methods
 */
class ServerRequest extends Message implements ServerRequestInterface
{
    protected $method;

    protected $uri;

    protected $headers;

    protected $server;

    protected $cookies;

    /**
     * @var UploadedFile[]
     */
    protected $uploadedFiles = array();

    protected $parsedBody = array();

    protected $queryParams = array();

    protected $attributes = array();

    protected $params = array();

    protected $requestTarget;

    /**
     * @var array $bodyParsers
     */
    protected $bodyParsers;

    public function __construct(
        $method,
        Uri $uri,
        array $headers,
        Stream $body,
        array $server = array(),
        array $cookies = array(),
        array $uploadedFiles = array())
    {
        $this->method = strtoupper($method);

        $this->uri = $uri;

        $this->setHeaders($headers);

        $this->body = $body;

        $this->server = $server;

        $this->cookies = $cookies;

        $this->uploadedFiles = $uploadedFiles;

        // register common content-type parser
        $this->registerMediaTypeParser('application/json', function($body)
        {
            return json_decode($body, true);
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function($body)
        {
            parse_str($body, $data);

            return $data;
        });
    }

    protected function setHeaderLines(array $headerLines)
    {
        $this->headerLines = $headerLines;
    }

    /**
     * Instantiate request from php _SERVER variable
     * @return static
     */
    public static function createFromGlobals()
    {
        $server = $_SERVER;

        $uriParts = parse_url($server['REQUEST_URI']);

        if(isset($server['HTTP_HOST']))
        {
            list($host, $port) = explode(':', $server['HTTP_HOST']);
            $uriParts['host'] = $host;
            $uriParts['port'] = $port;
        }
        else
        {
            $uriParts['host'] = $server['SERVER_NAME'];
            $uriParts['port'] = $server['SERVER_PORT'];
        }

        $uriParts['scheme'] = isset($server['REQUEST_SCHEME']) ? $server['REQUEST_SCHEME'] : ( isset($server['HTTPS']) && $server['HTTPS'] == 'on' ? 'https' : 'http' );

        $headers = array();

        if(function_exists('getallheaders'))
        {
            // a correct case already
            $apacheHeaders = getallheaders();

            foreach($apacheHeaders as $header => $value)
                $headers[$header] = array_map('trim', explode(',', $value));
        }
        else
        {

            // normalize the header key
            foreach($server as $key => $value)
            {
                if(substr($key, 0, 5) != 'HTTP_')
                    continue;

                $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));

                $headers[$name] = array_map('trim', explode(',', $value));
            }
        }

        $request = new static(
            $server['REQUEST_METHOD'],
            new Uri($uriParts),
            $headers,
            Stream::createFromContents(file_get_contents('php://input')),
            $server,
            $_COOKIE,
            UploadedFile::createFromGlobals($_FILES)
        );

        if($server['REQUEST_METHOD'] == 'POST' && in_array($request->getMediaType(), array('application/x-www-form-urlencoded', 'multipart/form-data')))
            $request->setParsedBody($_POST);

        return $request;
    }

    /**
     * Create request from given array
     * @param array $params
     * @return static
     */
    public static function createFromArray(array $params)
    {
        return new static(
            isset($params['method']) ? $params['method'] : 'GET',
            new Uri(isset($params['uri']) ? $params['uri'] : ''),
            isset($params['headers']) ? $params['headers'] : array(),
            isset($params['body']) ? (is_resource($params['body']) ? new Stream($params['body']) : Stream::createFromContents($params['body'])) : Stream::createFromContents(''),
            isset($params['server']) ? $params['server'] : array(),
            isset($params['cookies']) ? $params['cookies'] : array()
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return static
     */
    public static function createFromServerRequest(ServerRequestInterface $request)
    {
        return new static(
            $request->getMethod(),
            new Uri((string) $request->getUri()),
            $request->getHeaders(),
            Stream::createFromContents($request->getBody()->getContents()),
            $request->getServerParams(),
            $request->getCookieParams(),
            $request->getUploadedFiles()
        );
    }

    /**
     * @return string
     */
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

    /**
     * @param $target
     * @return $this
     */
    public function setRequestTarget($target)
    {
        $this->requestTarget = $target;

        return $this;
    }

    /**
     * @param mixed $target
     * @return ServerRequest
     */
    public function withRequestTarget($target)
    {
        $request = clone $this;

        return $request->setRequestTarget($target);
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return ServerRequest
     */
    public function withMethod($method)
    {
        $request = clone $this;

        $request->setMethod($method);

        return $request;
    }

    /**
     * @return Uri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param $uri
     */
    public function setUri($uri)
    {
        if(is_string($uri) || is_array($uri))
            $this->uri = new Uri($uri);
        else if($uri instanceof Uri)
            $this->uri = $uri;
        else
            throw new \InvalidArgumentException('Invalid uri. Must be string, array, or Uri');
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     * @return ServerRequest
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $request = clone $this;

        $request->uri = $uri;

        return $request;
    }

    /**
     * Get server params
     * @return array
     */
    public function getServerParams()
    {
        return $this->server;
    }

    /**
     * Get server value
     * @param string $name
     * @param null|string $default
     * @return string|null
     */
    public function server($name, $default = null)
    {
        return array_key_exists($name, $this->server) ? $this->server[$name] : $default;
    }

    /**
     * Get cookie params
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Get cookie
     * @param string $name
     * @param null|string default
     * @return null|string
     */
    public function cookie($name, $default = null)
    {
        return array_key_exists($name, $this->cookies) ? $this->cookies[$name] : $default;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setCookieParams(array $params)
    {
        $this->cookies = $params;

        return $this;
    }

    /**
     * @param array $params
     * @return ServerRequest
     */
    public function withCookieParams(array $params)
    {
        $request = clone $this;

        $request->cookies = $params;

        return $request;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        if($this->queryParams)
            return $this->queryParams;

        parse_str($this->uri->getQuery(), $this->queryParams);

        return $this->queryParams;
    }

    /**
     * @param array $queryParams
     * @return $this
     */
    public function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * @param array $queryParams
     * @return ServerRequest
     */
    public function withQueryParams(array $queryParams)
    {
        $request = clone $this;

        $request->queryParams = $queryParams;

        return $request;
    }

    /**
     * @return array|UploadedFile[]
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Get UploadedFile from given key
     *
     * @param string $key
     * @return UploadedFile|null
     */
    public function getUploadedFile($key)
    {
        if(isset($this->uploadedFiles[$key]))
            return $this->uploadedFiles[$key];

        return null;
    }

    /**
     * @param array $uploadedFiles
     * @return $this
     */
    public function setUploadedFiles(array $uploadedFiles)
    {
        $this->uploadedFiles = $uploadedFiles;

        return $this;
    }

    /**
     * @param array $uploadedFiles
     * @return ServerRequest
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $request = clone $this;

        $request->uploadedFiles = $uploadedFiles;

        return $request;
    }

    /**
     * @return array
     * @throws \Exedra\Exception\Exception
     */
    public function getParsedBody()
    {
        if($this->parsedBody)
            return $this->parsedBody;

        $mediaType = $this->getMediaType();

        if(!isset($this->bodyParsers[$mediaType]))
            return $this->parsedBody;

        $this->parsedBody = $this->bodyParsers[$mediaType]((string) $this->body);

        if(!in_array(gettype($this->parsedBody), array('NULL', 'array', 'object')))
            throw new \Exedra\Exception\Exception('Registered media type parser return type must be a null, array, or object');

        return $this->parsedBody;
    }

    /**
     * @return string|null
     */
    public function getMediaType()
    {
        $contentType = $this->getHeaderLine('Content-Type');

        if(!$contentType)
            return null;

        // credit to https://github.com/slimphp/Slim-Http/blob/master/src/Request.php
        $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

        return $contentTypeParts[0];
    }

    /**
     * @param $type
     * @param \Closure $callable
     * @return $this
     */
    public function registerMediaTypeParser($type, \Closure $callable)
    {
        $this->bodyParsers[$type] = $callable->bindTo($this);

        return $this;
    }

    /**
     * @param $parsedBody
     * @return $this
     */
    public function setParsedBody($parsedBody)
    {
        $this->parsedBody = $parsedBody;

        return $this;
    }

    /**
     * @param array|null|object $parsedBody
     * @return ServerRequest
     */
    public function withParsedBody($parsedBody)
    {
        $request = clone $this;

        return $request->setParsedBody($parsedBody);
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return ServerRequest
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;

        return $request->setAttribute($name, $value);;
    }

    /**
     * @param $name
     * @return $this
     */
    public function removeAttribute($name)
    {
        unset($this->attributes[$name]);

        return $this;
    }

    /**
     * @param string $name
     * @return ServerRequest
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;

        return $request->removeAttribute($name);
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return strtolower($this->getHeaderLine('x-requested-with')) == 'xmlhttprequest';
    }

    /**
     * @return mixed
     */
    public function header()
    {
        return $this->headers;
    }

    /**
     * @param $method
     * @return bool
     */
    public function isMethod($method)
    {
        return strtolower($method) == strtolower($this->method);
    }

    /**
     * Get a merged query params and parsed body
     * @param string $name
     * @param null|string $default
     * @return mixed|string
     */
    public function param($name, $default = null)
    {
        if(!$this->params)
            $this->params = $this->getMethod() == 'GET' ? $this->getQueryParams() : array_merge($this->getQueryParams(), $this->getParsedBody());

        return array_key_exists($name, $this->params) ? $this->params[$name] : $default;
    }

    /**
     * Get merged query params and parsed body
     * @return array
     */
    public function getParams()
    {
        if(!$this->params)
            $this->params = $this->getMethod() == 'GET' ? $this->getQueryParams() : array_merge($this->getQueryParams(), $this->getParsedBody());

        return $this->params;
    }

    /**
     * Alias to resolveUriPath()
     * @return null
     */
    public function resolveUri()
    {
        return $this->resolveUriPath();
    }

    /**
     * Apache only functionality, to resolve uri to current folder the apps is located
     * @return null
     */
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