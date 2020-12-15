<?php namespace Exedra\Routing;

use Exedra\Contracts\Routing\Registrar;
use Exedra\Contracts\Routing\ParamValidator;
use Exedra\Exception\Exception;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\RoutingException;
use Exedra\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Route implements Registrar
{
    /**
     * Route name
     * @var string name
     */
    protected $name;

    /**
     * An absolute string full name
     * @var string absolute name
     */
    protected $absoluteName = null;

    /**
     *
     * An array list of routes to this route.
     * Initialized on getFullRoutes() called
     * @var array|null fullRoutes
     */
    protected $fullRoutes = null;

    /**
     * @var array properties
     * - method
     * - path
     * - execute
     * - middleware
     * - subroutes
     * - config
     * - requestable
     */
    protected $properties = array(
        'uri' => null,
        'path' => '',
        'requestable' => true,
        'middleware' => array(),
        'execute' => null,
        'param_validators' => array()
    );

    /**
     * The Group this route is bound to
     * @var Group $group
     */
    protected $group;

    /**
     * Route notation stored in static form
     * @var string notation
     */
    public static $notation = '.';

    /**
     * List of default aliases
     * @var array aliases
     */
    protected static $aliases = array(
        'bind:middleware' => 'middleware',
        'group' => 'subroutes',
        'handler' => 'execute',
        'verb' => 'method',
        'attr' => 'attribute',
        'inject' => 'dependencies'
    );

    /**
     * @var array
     */
    protected static $classCaches = array();

    /**
     * Route attributes
     * @deprecated use states
     * @var array $attributes
     */
    protected $attributes = array();

    /**
     * Route states
     * Replacement for route attributes
     * @var array states
     */
    protected $states = array();

    /**
     * Route flags
     * @var mixed[]
     */
    protected $flags = array();

    /**
     * Decorators
     * @var array
     */
    protected $decorators = array();

    public function __construct(Group $group, $name, array $properties = array())
    {
        $this->name = $name;

        $this->group = $group;

        $this->refreshAbsoluteName();

        if (count($properties) > 0)
            $this->setProperties($properties);
    }

    /**
     * Set multiple properties for this route
     * @param array $properties
     * @return self
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $key => $value)
            $this->parseProperty($key, $value);

        return $this;
    }

    /**
     * Manual setter based on string.
     * @param string $key
     * @param mixed $value
     * @return $this;
     */
    public function parseProperty($key, $value)
    {
        if (isset(self::$aliases[$key]))
            $key = self::$aliases[$key];

        $method = 'set' . ucwords($key);

        $this->{$method}($value);

        return $this;
    }

    /**
     * Get name of this route, relative to the current group
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get route fullname.
     * @return string with dotted notation.
     */
    public function getAbsoluteName()
    {
        return $this->absoluteName;
    }

    /**
     * Get an absolutely resolved uri path all of the related routes to this,
     * with replaced named parameter.
     * @param array $params
     * @return string uri path of
     *
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function getAbsolutePath($params = array())
    {
        $routes = $this->getFullRoutes();

        $paths = array();

        foreach ($routes as $route) {
            $path = $route->getParameterizedPath($params);

            if ($path)
                $paths[] = $path;
        }

        return trim(implode('/', $paths), '/');
    }

    /**
     * Get uri path property for this route.
     * @param boolean $absolute
     * @return string
     */
    public function getPath($absolute = false)
    {
        if (!$absolute)
            return $this->properties['path'];

        $routes = $this->getFullRoutes();

        $paths = array();

        foreach ($routes as $route) {
            $path = $route->getProperty('path');

            if ($path == '')
                continue;

            $paths[] = $path;
        }

        return trim(implode('/', $paths), '/');
    }

    /**
     * Return all of the related routes.
     * @return array|Route[]
     */
    public function getFullRoutes()
    {
        // if has saved already, return that.
        if ($this->fullRoutes !== null)
            return $this->fullRoutes;

        $routes = array();

        $routes[] = $this;

        $group = $this->group;

        /** @var Route $route */
        while ($route = $group->getUpperRoute()) {
            $routes[] = $route;

            // recursively refer to upperRoute's group
            $group = $route->getGroup();
        }

        $this->fullRoutes = array_reverse($routes);

        return $this->fullRoutes;
    }

    /**
     * Recursively find all the fail route reversely
     * @return string|null
     */
    public function getFailRouteName()
    {
        if ($this->group->hasFailRoute())
            return $this->group->getFailRoute();

        $group = $this->group;

        while ($route = $group->getUpperRoute()) {
            /** @var Group $group */
            $group = $route->getGroup();

            if ($group->hasFailRoute())
                return $group->getFailRoute();
        }

        return null;
    }

    /**
     * set this route as a fail route for the current group.
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function setAsFailRoute($bool = true)
    {
        if (!$bool)
            return $this;

        $name = $this->name;

        if (!$name)
            throw new InvalidArgumentException('This route has to be named first.');

        $this->group->setFailRoute($name);

        return $this;
    }

    /**
     * Alias to setAsFailRoute
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function asFailRoute($bool = true)
    {
        return $this->setAsFailRoute($bool);
    }

    /**
     * Get parent route name after substracted the current route name.
     * @return string|null
     */
    public function getParentRouteName()
    {
        $absoluteRoute = $this->getAbsoluteName();

        $absoluteRoutes = explode('.', $absoluteRoute);

        if (count($absoluteRoutes) == 1)
            return null;

        array_pop($absoluteRoutes);

        $parentRoute = implode('.', $absoluteRoutes);

        return $parentRoute;
    }

    /**
     * Get a replaced uri path parameter.
     * @param array $data
     * @return string of a replaced path
     *
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function getParameterizedPath(array $data)
    {
        $path = $this->properties['path'];

        $segments = explode('/', $path);

        $newSegments = array();

        $missingParameters = array();

        foreach ($segments as $segment) {
//			if(strpos($segment, '[') === false && strpos($segment, ']') === false)
            if (strpos($segment, ':') === false) {
                $newSegments[] = $segment;
                continue;
            }

            // strip.
            $segment = trim($segment, '[]');
            list($key, $segment) = explode(':', $segment);

            $isOptional = $segment[strlen($segment) - 1] == '?' ? true : false;
            $segment = $isOptional ? substr($segment, 0, strlen($segment) - 1) : $segment;

            // is mandatory, but no parameter passed.
            if (!$isOptional && !isset($data[$segment])) {
                $missingParameters[] = $segment;
                continue;
            }

            // trailing capture.
            if ($key == '**') {
                if (is_array($data[$segment])) {
                    $data[$segment] = implode('/', $data[$segment]);
                }
            }

            if (!$isOptional)
                $newSegments[] = $data[$segment];
            else
                if (isset($data[$segment]))
                    $newSegments[] = $data[$segment];
                else
                    $newSegments[] = '';
        }

        if (count($missingParameters) > 0)
            throw new \Exedra\Exception\InvalidArgumentException("Route parameter(s) is missing [" . implode(', ', $missingParameters) . "].");

        return implode('/', $newSegments);
    }

    /**
     * @param UriInterface $uri
     * @param UriInterface $requestUri
     * @return array|false
     */
    protected function matchUri(UriInterface $uri , UriInterface $requestUri)
    {
        $requestPort = $requestUri->getPort() ? : 80;

        $params = [];

        // now only check port if it's available
        if (($basePort = $uri->getPort()) && $basePort != $requestPort) {
            return false;
        }

        if (strpos($uri->getHost(), '{') === 0) {
            $uriHost = explode('.', $uri->getHost());
            $requestHost = explode('.', $requestUri->getHost());

            $param = trim(array_shift($uriHost), '{}');
            $value = array_shift($requestHost);

            if (implode('.', $uriHost) != implode('.', $requestHost))
                return false;

            $params[$param] = $value;

            return array(
                'uri' => $uri,
                'params' => $params
            );
        } else if ($uri->getHost() == $requestUri->getHost()) {
            return array(
                'uri' => $uri,
                'params' => []
            );
        }
    }

    /**
     * Validate uri path against the request
     * @param ServerRequestInterface $request
     * @param string $path
     * @return array
     */
    public function match(ServerRequestInterface $request, $path)
    {
        $routePath = $this->properties['path'];

        $params = array();

        if (isset($this->properties['method'])) {
            if (!in_array(strtolower($request->getMethod()), $this->properties['method']))
                return array(
                    'route' => false,
                    'parameter' => false,
                    'continue' => false
                );
        }

        if ($this->properties['uri']) {
            // allow for deeper checking, skip /path checking
            if ($this->properties['uri'] === true)
                return array(
                    'route' => $this,
                    'parameter' => array(),
                    'continue' => true
                );

            /** @var Uri $baseUri */
            $uriResult = $this->matchUri($this->properties['uri'], $request->getUri());

            if ($uriResult === false)
                return array(
                    'route' => false,
                    'parameter' => false,
                    'continue' => false
                );

            $resultPath = trim($this->properties['uri']->getPath(), '/');

            if ($resultPath)
                $routePath = trim($resultPath . '/' . $routePath, '/');

            $params = $uriResult['params'];
        }

        // path validation
        $result = $this->matchPath($path, $routePath);

        if (!$result)
            return array(
                'route' => false,
                'parameter' => array(),
                'continue' => false
            );

        if ($params)
            $result['parameter'] = array_merge($result['parameter'], $params);

        if (!$result['matched'])
            return array(
                'route' => false,
                'parameter' => $result['parameter'],
                'continue' => $result['continue']
            );

        if ($this->properties['param_validators']) {
            $flag = $this->matchValidators($request, $path, $result['parameter']);

            if ($flag === false)
                return array(
                    'route' => false,
                    'parameter' => false,
                    'continue' => false
                );
        }

        return array(
            'route' => $this,
            'parameter' => $result['parameter'],
            'continue' => $result['continue']
        );
/*
        return array(
            'route' => false,
            'parameter' => array(),
            'continue' => false
        );*/
    }

    /**
     * Do a custom validation matching
     * @param ServerRequestInterface $request
     * @param $path
     * @param array $parameters
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function matchValidators(ServerRequestInterface $request, $path, array &$parameters = array())
    {
        foreach ($this->properties['param_validators'] as $validation) {
            if (is_string($validation)) {
                if (!isset(static::$classCaches[$validation])) {
                    /** @var ParamValidator $validationObj */
                    $validationObj = new $validation;

                    if (!($validationObj instanceof ParamValidator))
                        throw new InvalidArgumentException('The [' . $validation . '] validator must be type of [' . ParamValidator::class . '].');
                } else {
                    $validationObj = static::$classCaches[$validation];
                }

                $flag = $validationObj->validate($parameters, $this, $request, $path);
            } else if (is_object($validation) && ($validation instanceof \Closure)) {
                $flag = $validation($parameters, $this, $request, $path);
            } else {
                throw new InvalidArgumentException('The validator must be type of [' . ParamValidator::class . '] or [' . \Closure::class . ']');
            }

            if (!$flag || $flag === false)
                return false;
        }

        return true;
    }

    /**
     * Validate given uri path
     * Return array of matched flag, and parameter.
     * @param string $path
     * @param string $routePath
     * @return array|bool
     */
    protected function matchPath($path, $routePath)
    {
        $continue = true;

        if ($this->properties['requestable'] === false)
            return false;

        if ($routePath === false)
            return false;

        if ($routePath === '') {
            return array(
                'matched' => ($path === '' ? true : false),
                'parameter' => array(),
                'continue' => $continue
            );
        }

        // route check
        $segments = explode('/', $routePath);
        $paths = explode('/', $path);

        // initialize states
        $matched = true;
        $pathParams = array();

        // route segment loop.
        $equal = null;

        $equalSegmentLength = count($segments) == count($paths);

        foreach ($segments as $no => $segment) {
            // non-pattern based validation
//			if($segment == '' || ($segment[0] != '[' || $segment[strlen($segment) - 1] != ']'))
            if (strpos($segment, ':') === false) {
                $equal = false;

                // need to move this logic outside perhaps.
                if (!$equalSegmentLength)
                    $matched = false;

                if (isset($paths[$no]) && $paths[$no] != $segment) {
                    $matched = false;
                    break;
                } else {
                    $equal = true;
                }

                continue;
            }

            // pattern based validation
            $pattern = trim($segment, '[]');

            @list($pattern, $segmentParamName) = explode(':', $pattern);

            // no color was passed. thus, could't retrieve second value.
            if (!$segmentParamName) {
                $matched = false;
                break;
            }

            // optional flag
            $isOptional = $segmentParamName[strlen($segmentParamName) - 1] == '?';

            $segmentParamName = trim($segmentParamName, '?');

            // no data at current uri path segment.
            if (!isset($paths[$no]) || (isset($paths[$no]) && $paths[$no] === '')) {
                // but if optional, continue searching without breaking.
                if ($isOptional) {
                    $matched = true;
                    continue;
                }

                $matched = false;
                break;
            }

            if ($paths[$no] === '' && !$isOptional) {
                $matched = false;
                break;
            }

            // pattern based matching
            switch ($pattern) {
                // match all, so do nothing.
                case '':
                    if ($paths[$no] == '' && !$isOptional) {
                        $matched = false;
                        break 2;
                    }
                    break;
                // integer
                case 'i':
                    // segment value isn't numeric. OR is cumpulsory.
                    if (!is_numeric($paths[$no]) && !$isOptional) {
                        $continue = false;
                        $matched = false;
                        break 2;
                    }
                    break;
                // segments remainder
                case '*':
                    $path = explode('/', $path, $no + 1);
                    $pathParams[$segmentParamName] = array_pop($path);
                    $matched = true;
                    break 2;
                    break;
                // segments remainder into array.
                // to be deprecated.
                case '**':
                    // get all the rest of path for param, and explode it so it return as list of segment.
                    $explodes = explode('/', $path, $no + 1);
                    $pathParams[$segmentParamName] = explode('/', array_pop($explodes));
                    $matched = true;
                    break 2; // break the param loop, and set matched directly to true.
                    break;
                default:
                    // split pattern with
                    $split = explode('|', $pattern);

                    if (!in_array($paths[$no], $split)) {
                        $matched = false;
                        break 2;
                    }
                    break;
            }

            if (count($segments) != count($paths))
                $matched = false;

            // set parameter of the current segment
            $pathParams[$segmentParamName] = $paths[$no];

        } // segments loop end

        // build result.
        $result = array();

        $result['continue'] = $equal === false ? false : $continue;

        // pattern matched flag.
        $result['matched'] = $matched;

        // pass parameter.
        $result['parameter'] = $pathParams;

        // return matched, parameters founds, and continue flag to continue search
        return $result;
    }

    /**
     * Get remaining uri path extracted from the passed one.
     * @param string $path
     * @return string path
     */
    public function getRemainingPath($path)
    {
        $paths = explode('/', $path);

        $newPaths = array();

        $routePath = $this->properties['path'];

        if ($this->properties['uri'] && $this->properties['uri'] !== true && $uriPath = trim($this->properties['uri']->getPath(), '/'))
            $routePath = trim($uriPath . '/' . $routePath, '/');

        for ($i = count(explode('/', $routePath)); $i < count($paths); $i++)
            $newPaths[] = $paths[$i];

        return $routePath != '' ? implode('/', $newPaths) : $path;
    }

    /**
     * Check whether has subroutes or not.
     * @return boolean of existence.
     */
    public function hasSubroutes()
    {
        return isset($this->properties['subroutes']);
    }

    /**
     * Get subgroup of this route
     * Resolve the group in case of Closure, string and array
     * @return Group
     *
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function getSubroutes()
    {
        $group = $this->properties['subroutes'];

        if ($group instanceof Group)
            return $group;

        return $this->resolveGroup($group);
    }

    /**
     * Resolve group in case of Closure, string and array
     * @return \Exedra\Routing\Group
     *
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function resolveGroup($pattern)
    {
//        $type = @get_class($pattern) ?: gettype($pattern);
        $type = is_object($pattern) ? get_class($pattern) : gettype($pattern);

        try {
            $router = $this->group->factory->resolveGroup($pattern, $this);
        } catch (InvalidArgumentException $e) {
            $route = $this->getAbsoluteName();

            $pattern = is_string($pattern) ? ' (' . $pattern . ')' : '';

            throw new \Exedra\Exception\InvalidArgumentException('Unable to resolve group [' . $type . $pattern . '] on route @' . $route . '.');
        }

        if (!$router)
            throw new \Exedra\Exception\InvalidArgumentException('Unable to resolve route group [' . $type . ']. It must be type of \Closure, string, or array');

        $this->properties['subroutes'] = $router;

        return $router;
    }

    /**
     * Get methods for this route.
     * @return array
     */
    public function getMethod()
    {
        if (!isset($this->properties['method']))
            return array('get', 'post', 'put', 'delete', 'patch', 'options');

        return $this->properties['method'];
    }

    /**
     * Check if this route has execution property.
     * @return boolean of existence.
     */
    public function hasExecution()
    {
        return isset($this->properties['execute']);
    }

    /**
     * Set route name
     * Refresh absolute name, everytime name changed.
     * @param string $name
     * @return $this
     */
    protected function setName($name)
    {
        $this->name = $name;

        $this->refreshAbsoluteName();

        return $this;
    }

    /**
     * Rebuild absolute name
     */
    protected function refreshAbsoluteName()
    {
        $group = $this->group;

        $name = $this->name;

        $this->absoluteName = $group->getUpperRoute() ? $group->getUpperRoute()->getAbsoluteName() . '.' . $name : $name;
    }

    /**
     * Match host:port
     *
     * @param $domain
     * @return Route
     */
    public function setDomain($domain)
    {
        return $this->setProperty('uri', Uri::createFromDomain($domain));
    }

    /**
     * Alias to setDomain
     * @param $domain
     * @return Route
     */
    public function domain($domain)
    {
        return $this->setDomain($domain);
    }

    /**
     * Set complete URI
     * scheme OR port is required, else it'll assume as path
     * @param UriInterface|string|bool $uri
     * @return Route
     * @throws RoutingException
     */
    public function setUri($uri)
    {
        if (is_null($uri))
            throw new RoutingException('URI for route [' . $this->name . '] cannot be null');

        if (is_string($uri))
            $uri = new Uri($uri);
        else if ($uri !== true && !($uri instanceof UriInterface))
            throw new RoutingException('URI can only be either string, or UriInterface, or bool (true)');

        if ($uri !== true && !$uri->getHost())
            return $this->path($uri->getPath());

        if ($uri !== true) {
            $this->setPath($uri->getPath());
            $uri->setPath('');
        }

        $this->setProperty('uri', $uri);

        return $this;
    }

    /**
     * Alias to setURI
     * @param UriInterface|string $uri
     * @return Route
     * @throws RoutingException
     */
    public function uri($uri)
    {
        return $this->setUri($uri);
    }

    /**
     * Get first uri it can get
     * @return UriInterface|null
     */
    public function getBaseUri()
    {
        $group = $this->group;

        $uri = $this->getProperty('uri');

        if (is_object($uri) && $uri instanceof UriInterface)
            return $uri;

        /** @var Route $route */
        while ($route = $group->getUpperRoute()) {
            $uri = $route->getProperty('uri');

            if (is_object($uri) && $uri instanceof UriInterface)
                return $uri;

            // recursively refer to upperRoute's group
            $group = $route->getGroup();
        }

        return null;
    }

    /**
     * Set uri path pattern for this route.
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        if ($path !== false)
            $path = trim($path, '/');

        $this->setProperty('path', $path);

        return $this;
    }

    /**
     * Alias to setPath
     * @param string $path
     * @return Route
     */
    public function path($path)
    {
        return $this->setPath($path);
    }

    /**
     * Set method for this route.
     * @param mixed $method (array of method, or /)
     * @return $this
     */
    public function setMethod($method)
    {
        if ($method == 'any')
            $method = array('get', 'post', 'put', 'delete', 'patch', 'options');
        else if (!is_array($method))
            $method = explode('|', $method);

        $method = array_map(function ($value) {
            return trim(strtolower($value));
        }, $method);

        $this->setProperty('method', $method);

        return $this;
    }

    /**
     * Set config for this route.
     * @param array $config
     * @return Route
     */
    public function setConfig(array $config)
    {
        return $this->setProperty('config', $config);
    }

    /**
     * Alias to setConfig
     * @param array $config
     * @return Route
     */
    public function config(array $config)
    {
        return $this->setConfig($config);
    }

    /**
     * Set execution property
     * @param mixed $execute
     * @return $this
     */
    public function setExecute($execute)
    {
        $this->setProperty('execute', $execute);

        return $this;
    }

    /**
     * Alias to setExecute
     * @param mixed $execute
     * @return Route
     */
    public function execute($execute)
    {
        return $this->setExecute($execute);
    }

    /**
     * Alias to setExecute
     * @param mixed $handle
     * @return Route
     */
    public function handle($handle)
    {
        $this->setProperty('execute', $handle);

        return $this;
    }

    /**
     * Add new group on for this route.
     * @param array|string|\Callback $subroutes
     * @param bool $resolve
     * @return Route
     */
    public function setSubroutes($subroutes, $resolve = false)
    {
        if ($resolve)
            $subroutes = $this->resolveGroup($subroutes);

        return $this->setProperty('subroutes', $subroutes);
    }

    /**
     * Alias to setSubroutes
     * @param array|string|callback $subroutes
     * @param bool $resolve
     * @return Route
     */
    public function group($subroutes, $resolve = false)
    {
        if ($resolve) {
            $subroutes = $this->resolveGroup($subroutes);
        }

        return $this->setProperty('subroutes', $subroutes);
    }

    /**
     * Set middleware(s) on this route.
     * Will reset previously added middleware
     * If given argument is an array, add the list
     * Else, will be pushed as one middleware
     * @param mixed $middleware
     * @return $this
     */
    public function setMiddleware($middleware, array $properties = array())
    {
        // reset on each call
        $this->properties['middleware'] = array();

        if (!is_array($middleware)) {
            $this->properties['middleware'][] = array($middleware, $properties);
        } else {
            foreach ($middleware as $m)
                $this->properties['middleware'][] = array($m, []);
        }

        return $this;
    }

    public function setDecorators(array $decorators)
    {
        foreach ($decorators as $decorator)
            $this->decorators[] = $decorator;

        return $this;
    }

    public function getDecorators()
    {
        return $this->decorators;
    }

    public function addDecorator($decorator)
    {
        $this->decorators[] = $decorator;

        return $this;
    }

    public function setDecorator($decorator)
    {
        $this->decorators[] = $decorator;

        return $this;
    }

    public function decorator($decorator)
    {
        $this->decorators[] = $decorator;

        return $this;
    }

    /**
     * Add an array of middlewares
     * @param array $middlewares
     * @return $this
     */
    public function addMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware)
            $this->properties['middleware'][] = $middleware;

        return $this;
    }

    /**
     * Add middleware to existing
     * @param mixed $middleware
     * @param array $properties
     * @return Route
     */
    public function addMiddleware($middleware, array $properties = array())
    {
        $this->properties['middleware'][] = array($middleware, $properties);

        return $this;
    }

    /**
     * Alias to addMiddleware
     * @param mixed $middleware handler
     * @param null $name
     * @return Route
     */
    public function middleware($middleware)
    {
        return $this->addMiddleware($middleware);
    }

    /**
     * Set module under this route.
     * @param string $module
     * @return Route
     */
    public function setModule($module)
    {
        return $this->setProperty('module', $module);
    }

    /**
     * Alias to setModule()
     * @param string $module
     * @return Route
     */
    public function module($module)
    {
        return $this->setProperty('module', $module);
    }

    /**
     * Tag this route
     * @param string $tag
     * @return Route
     */
    public function setTag($tag)
    {
        return $this->setProperty('tag', $tag);
    }

    /**
     * Alias to setTag
     * @param string $tag
     * @return Route
     */
    public function tag($tag)
    {
        return $this->setProperty('tag', $tag);
    }

    /**
     * Set attribute
     * @deprecated
     * @param string $key
     * @param mixed $value
     * @return Route
     */
    public function setAttribute($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $item => $value) {
                if (strrpos($item, '[]') == ($itemLength = strlen($item) - 2)) {
                    $this->states[substr($item, 0, $itemLength)][] = $value;
                } else {
                    $this->states[$item] = $value;
                }
            }
        } else {
            if (strrpos($key, '[]') == ($keyLength = strlen($key) - 2)) {
                $this->states[substr($key, 0, $keyLength)][] = $value;
            } else {
                $this->states[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Set states
     * @param array $states
     * @return $this
     */
    public function setStates(array $states)
    {
        $this->states = array_merge($this->states, $states);

        return $this;
    }

    /**
     * Set state
     * @param string $key
     * @param mixed $value
     * @return Route
     */
    public function setState($key, $value = null)
    {
//        if (is_array($key)) {
//            foreach ($key as $item => $value) {
//                if (strrpos($item, '[]') == ($itemLength = strlen($item) - 2)) {
//                    $this->states[substr($item, 0, $itemLength)][] = $value;
//                } else {
//                    $this->states[$item] = $value;
//                }
//            }
//        } else {
//            if (strrpos($key, '[]') == ($keyLength = strlen($key) - 2)) {
//                $this->states[substr($key, 0, $keyLength)][] = $value;
//            } else {
//                $this->states[$key] = $value;
//            }
//        }

        $this->states[$key] = $value;

        return $this;
    }

    public function setFlags(array $flags)
    {
        $this->flags = array_merge($this->flags, $flags);

        return $this;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Get attribute
     * @deprecated
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        return $this->states[$key];
    }

    /**
     * Get state
     * @param string $key
     * @return mixed
     */
    public function getState($key)
    {
        return $this->states[$key];
    }

    /**
     * Check whether attribute exist
     * @deprecated
     * @param string $key
     * @return bool
     */
    public function hasAttribute($key)
    {
        return isset($this->states[$key]);
    }

    /**
     * Check whether attribute exist
     * @param string $key
     * @return bool
     */
    public function hasState($key)
    {
        return isset($this->states[$key]);
    }

    /**
     * Get all attributes
     * @deprecated
     * @return array
     */
    public function getAttributes()
    {
        return $this->states;
    }

    /**
     * Get all attributes
     * @return array
     */
    public function getStates()
    {
        return $this->states;
    }


    /**
     * Alias to setAttribute(key, value)
     * @param string $key
     * @param string $value
     * @return Route
     */
    public function attr($key, $value = null)
    {
        return $this->setAttribute($key, $value);
    }

    /**
     * Alias to setState(key, value)
     * @param string $key
     * @param string $value
     * @return Route
     */
    public function state($key, $value = null)
    {
        return $this->setState($key, $value);
    }

    /**
     *
     * @param string|ParamValidator $validator
     * @return Route
     */
    public function validateParam($validator)
    {
        if (is_string($validator))
            $validator = new $validator;

        $this->properties['param_validators'][] = $validator;

        return $this;
    }


    /**
     * Get route property
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getProperty($key, $default = null)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : $default;
    }

    /**
     * Check whether route property exist or not.
     * @param string $key
     * @return boolean
     */
    public function hasProperty($key)
    {
        return isset($this->properties[$key]);
    }

    /**
     * Set property for this route.
     * @param string $key
     * @param mixed $value
     * @return Route
     */
    protected function setProperty($key, $value)
    {
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * Whether the route is requestable
     * @return boolean
     */
    public function isRequestable()
    {
        return $this->getPath() !== false && $this->properties['requestable'] === true;
    }

    /**
     * Alias to setRequestable
     * @param bool $flag
     * @return self
     */
    public function requestable($flag = true)
    {
        return $this->setRequestable($flag);
    }

    /**
     * Set whether route is requestable / dispatchable
     * @param bool $flag
     * @return self
     */
    public function setRequestable($flag)
    {
        $this->setProperty('requestable', $flag);

        return $this;
    }

    /**
     * Set dependencies for execute arguments
     * @param array|mixed $dependencies
     * @return Route
     */
    public function setDependencies($dependencies)
    {
        if (!is_array($dependencies))
            $dependencies = array($dependencies);

        $this->setProperty('dependencies', $dependencies);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDependencies()
    {
        return isset($this->properties['dependencies']);
    }

    /**
     * Alias to setDependencies
     * @param array|mixed $dependencies
     * @return Route
     */
    public function dependencies($dependencies)
    {
        return $this->setDependencies($dependencies);
    }

    /**
     * Alias to setDependencies
     * @param array|mixed $dependencies
     * @return $this
     */
    public function inject($dependencies)
    {
        return $this->setDependencies($dependencies);
    }

    /**
     * Alias to setMethod with path settable
     * @param string $method
     * @return Route
     */
    public function method($method, $path = null)
    {
        if ($path !== null)
            $this->setPath($path);

        return $this->setMethod($method);
    }

    /**
     * Set method to GET and path
     * @param string $path
     * @return self
     */
    public function get($path = '/')
    {
        $this->setMethod('GET');

        $this->setPath($path);

        return $this;
    }

    /**
     * Set method to POST and path
     * @param string $path
     * @return self
     */
    public function post($path = '/')
    {
        $this->setMethod('POST');

        $this->setPath($path);

        return $this;
    }

    /**
     * Set method to PUT and path
     * @param string $path
     * @return self
     */
    public function put($path = '/')
    {
        $this->setMethod('PUT');

        $this->setPath($path);

        return $this;
    }

    /**
     * Set method to DELETE and path
     * @param string $path
     * @return self
     */
    public function delete($path = '/')
    {
        $this->setMethod('DELETE');

        $this->setPath($path);

        return $this;
    }

    /**
     * Set method to PATCH and path
     * @param string $path
     * @return self
     */
    public function patch($path = '/')
    {
        $this->setMethod('PATCH');

        $this->setPath($path);

        return $this;
    }

    /**
     * Set method to OPTION and path
     * @param $path
     * @return $this
     */
    public function options($path = '/')
    {
        $this->setMethod('OPTION');

        $this->setPath($path);

        return $this;
    }

    /**
     * Set method to any method and path
     * @param string $path
     * @return self
     */
    public function any($path = '/')
    {
        $this->setMethod('any');

        $this->setPath($path);

        return $this;
    }
}