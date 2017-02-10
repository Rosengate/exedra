<?php namespace Exedra\Routing;

use Exedra\Contracts\Routing\Registrar;
use Exedra\Contracts\Routing\Validator;
use Exedra\Exception\InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

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
	 * - ajax
	 * - execute
	 * - middleware
	 * - subroutes
	 * - config
	 * - base
	 * - requestable
	 */
	protected $properties = array(
		'path' => '',
		'requestable' => true,
		'middleware' => array(),
		'execute' => null,
        'validators' => array()
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
								'uri' => 'path',
								'group' => 'subroutes',
								'handler' => 'execute',
								'verb' => 'method');

	/**
	 * Route meta attributes
	 * @var array $attributes
	 */
	protected $attributes = array();

	public function __construct(Group $group, $name, array $properties = array())
	{
		$this->name = $name;
		
		$this->group = $group;
		
		$this->refreshAbsoluteName();

		if(count($properties) > 0)
			$this->setProperties($properties);
	}

	/**
	 * Set multiple properties for this route
	 * @param array $properties
	 * @return self
	 */
	public function setProperties(array $properties)
	{
		foreach($properties as $key=>$value)
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
		if(isset(self::$aliases[$key]))
			$key = self::$aliases[$key];

		$method = 'set'.ucwords($key);

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

		foreach($routes as $route)
		{
			$path = $route->getParameterizedPath($params);
			
			if($path)
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
		if(!$absolute)
			return $this->getProperty('path');

		$routes = $this->getFullRoutes();

		$paths = array();

		foreach($routes as $route)
		{
			$path = $route->getProperty('path');

			if($path == '')
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
		if($this->fullRoutes !== null)
			return $this->fullRoutes;

		$routes = array();

		$routes[] = $this;
		
		$group = $this->group;

        /** @var Route $route */
		while($route = $group->getUpperRoute())
		{
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
        if($this->group->hasFailRoute())
            return $this->group->getFailRoute();

        $group = $this->group;

        while($route = $group->getUpperRoute())
        {
            /** @var Group $group */
            $group = $route->getGroup();

            if($group->hasFailRoute())
                return $group->getFailRoute();
        }

        return null;
    }

	/**
	 * Get parent route name after substracted the current route name.
	 * @return string|null
	 */
	public function getParentRouteName()
	{
		$absoluteRoute	= $this->getAbsoluteName();

		$absoluteRoutes	= explode('.', $absoluteRoute);

		if(count($absoluteRoutes) == 1)
			return null;

		array_pop($absoluteRoutes);

		$parentRoute	= implode('.', $absoluteRoutes);
		
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
		$path = $this->getProperty('path');

		$segments	= explode('/', $path);

		$newSegments	= array();

        $missingParameters = array();

		foreach($segments as $segment)
		{
//			if(strpos($segment, '[') === false && strpos($segment, ']') === false)
            if(strpos($segment, ':') === false)
			{
				$newSegments[]	= $segment;
				continue;
			}

			// strip.
			$segment	= trim($segment, '[]');
			list($key,$segment)	= explode(':', $segment);

			$isOptional	= $segment[strlen($segment)-1] == '?'? true : false;
			$segment	= $isOptional?substr($segment, 0,strlen($segment)-1):$segment;

			// is mandatory, but no parameter passed.
			if(!$isOptional && !isset($data[$segment]))
			{
			    $missingParameters[] = $segment;
                continue;
			}

			// trailing capture.
			if($key == '**')
			{
				if(is_array($data[$segment]))
				{
					$data[$segment] = implode('/', $data[$segment]);
				}
			}
				
			if(!$isOptional)
				$newSegments[]	= $data[$segment];
			else
				if(isset($data[$segment]))
					$newSegments[]	= $data[$segment];
				else
					$newSegments[]	= '';
		}

		if(count($missingParameters) > 0)
            throw new \Exedra\Exception\InvalidArgumentException("Url.Create : Route parameter(s) is missing [".implode(', ', $missingParameters)."].");

        return implode('/', $newSegments);
	}

    /**
     * Validate uri path against the request
     * @param ServerRequestInterface $request
     * @param string $path
     * @return array
     */
	public function match(ServerRequestInterface $request, $path)
	{
		foreach(array('method', 'path', 'ajax') as $key)
		{
			// if this parameter wasn't set, skip validation.
			if(!$this->hasProperty($key))
				continue;

			switch($key)
			{
				case 'method':
                    $value = $request->getMethod();
                    // return false because method doesn't exist.
                    if(!in_array(strtolower($value), $this->getProperty('method')))
                        return array('route' => false, 'parameter' => false, 'continue' => false);

				break;
				case 'path':
                    $result = $this->matchPath($path);

                    if(!$result['matched'])
                        return array('route' =>false, 'parameter' => $result['parameter'], 'continue' => $result['continue']);

                    if($this->properties['validators'])
                    {
                        $flag = $this->matchValidators($request, $path, $result['parameter']);

                        if($flag === false)
                            return array('route' => false, 'parameter' => false, 'continue' => false);
                    }

                    return array('route' => $this, 'parameter' => $result['parameter'], 'continue' => $result['continue']);
				break;
				case 'ajax':
                    if((strtolower($request->getHeaderLine('x-requested-with')) == 'xmlhttprequest') != $this->getProperty('ajax'))
                        return array('route' => false, 'parameter' => false, 'continue' => false);
				break;
			}
		}

		return array('route'=>false, 'parameter'=> array(), 'continue'=> false);
	}

    /**
     * Do a custom validation matching
     * @param ServerRequestInterface $request
     * @param $path
     * @param array $parameters
     * @return int
     * @throws InvalidArgumentException
     */
    protected function matchValidators(ServerRequestInterface $request, $path, array $parameters = array())
    {
        foreach($this->getProperty('validators') as $validation)
        {
            if(is_string($validation))
            {
                /** @var Validator $validationObj */
                $validationObj = new $validation;

                if(!($validationObj instanceof Validator))
                    throw new InvalidArgumentException('The [' . $validation . '] validator must be type of [' . Validator::class . '].');

                $flag = $validationObj->validate($parameters, $this, $request, $path);
            }
            else if(is_object($validation) && ($validation instanceof \Closure))
            {
                $flag = $validation($parameters, $this, $request, $path);
            }
            else
            {
                throw new InvalidArgumentException('The validator must be type of [' . Validator::class . ']');
            }

            if(!$flag || $flag === false)
                return false;
        }

        return true;
    }

	/**
	 * Validate given uri path
     * Return array of matched flag, and parameter.
	 * @param string $path
	 * @return array|boolean
	 */
	protected function matchPath($path)
	{
		$continue = true;

		$routePath = $this->getProperty('path');

		if($this->getProperty('requestable') === false)
			return false;

		if($routePath === false)
			return false;

		if($routePath === '')
		{
			return array(
				'matched'=> ($path === '' ? true : false),
				'parameter'=> array(),
				'continue' => $continue
				);
		}

		// route check
		$segments = explode('/', $routePath);
		$paths = explode('/', $path);

		// initialize states
		$matched = true;
		$pathParams	= array();

		// route segment loop.
		$equal = null;

		$equalSegmentLength = count($segments) == count($paths);

		foreach($segments as $no => $segment)
		{
			// non-pattern based validation
//			if($segment == '' || ($segment[0] != '[' || $segment[strlen($segment) - 1] != ']'))
            if(strpos($segment, ':') === false)
			{
				$equal	= false;

				// need to move this logic outside perhaps.
				if(!$equalSegmentLength)
					$matched = false;

				if(isset($paths[$no]) && $paths[$no] != $segment)
				{
					$matched	= false;
					break;
				}
				else
				{
					$equal	= true;
				}

				continue;
			}

			// pattern based validation
			$pattern	= trim($segment, '[]');
			
			@list($pattern, $segmentParamName) = explode(':', $pattern);

			// no color was passed. thus, could't retrieve second value.
			if(!$segmentParamName)
			{
				$matched	= false;
				break;
			}

			// optional flag
			$isOptional			= $segmentParamName[strlen($segmentParamName)-1] == '?';

			$segmentParamName	= trim($segmentParamName, '?');

			// no data at current uri path segment.
			if(!isset($paths[$no]) || (isset($paths[$no]) && $paths[$no] === ''))
			{
				// but if optional, continue searching without breaking.
				if($isOptional)
				{
					$matched	= true;  
					continue;
				}

				$matched	= false;
				break;
			}

			if($paths[$no] === '' && !$isOptional)
			{
				$matched = false;
				break;
			}

			// pattern based matching
			switch($pattern)
			{
				// match all, so do nothing.
				case '':
					if($paths[$no] == '' && !$isOptional)
					{
						$matched = false;
						break 2;
					}
				break;
				// integer
				case 'i':
					// segment value isn't numeric. OR is cumpulsory.
					if(!is_numeric($paths[$no]) && !$isOptional)
					{
						$continue = false;
						$matched = false;
						break 2;
					}
				break;
				// segments remainder
				case '*':
					$path = explode('/', $path, $no+1);
					$pathParams[$segmentParamName] = array_pop($path);
					$matched = true;
					break 2;
				break;
				// segments remainder into array.
				// to be deprecated.
				case '**':
					// get all the rest of path for param, and explode it so it return as list of segment.
					$explodes = explode('/', $path, $no+1);
					$pathParams[$segmentParamName]	= explode('/', array_pop($explodes));
					$matched		= true;
					break 2; // break the param loop, and set matched directly to true.
				break;
				default:
					// split pattern with 
					$split = explode('|', $pattern);

					if(!in_array($paths[$no], $split))
					{
						$matched = false;
						break 2;
					}
				break;
			}

			if(count($segments) != count($paths))
				$matched = false;

			// set parameter of the current segment
			$pathParams[$segmentParamName]	= $paths[$no];
		
		} // segments loop end

		// build result.
		$result 	= array();

		$result['continue'] = $equal === false ? false : $continue;

		// pattern matched flag.
		$result['matched']	= $matched;

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
		
		$newPaths	= array();

		for($i = count(explode('/', $this->properties['path'])); $i < count($paths); $i++)
			$newPaths[]	= $paths[$i];

		return $this->properties['path'] != '' ? implode('/', $newPaths) : $path;
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

		if($group instanceof Group)
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
		$type = @get_class($pattern) ? : gettype($pattern);

        $router = $this->group->factory->resolveGroup($pattern, $this);

        if(!$router)
            throw new \Exedra\Exception\InvalidArgumentException('Unable to resolve route group ['.$type.']. It must be type of \Closure, string, or array');

        $this->properties['subroutes'] = $router;

        return $router;
	}

	/**
	 * Get methods for this route.
	 * @return array
	 */
	public function getMethod()
	{
		if(!$this->hasProperty('method'))
			return array('get', 'post', 'put', 'delete', 'patch', 'options');

		return $this->getProperty('method');
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

		$this->absoluteName = $group->getUpperRoute() ? $group->getUpperRoute()->getAbsoluteName().'.'.$name : $name;
	}

	/**
	 * Set base route
	 */
	public function setBase($baseRoute)
	{
		$this->setProperty('base', $baseRoute);
		
		return $this;
	}

    /**
     * Alias to setBase
     * @param string $baseRoute
     * @return $this
     */
	public function base($baseRoute)
	{
		$this->setProperty('base', $baseRoute);

		return $this;
	}

	/**
	 * Set uri path pattern for this route.
	 * @param string $path
	 * @return $this
	 */
	public function setPath($path)
	{
		if($path !== false)
			$path = trim($path, '/');

		$this->setProperty('path', $path);

		return $this;
	}

	/**
	 * Alias to setPath
	 * @param string $path
     * @return $this
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
		if($method == 'any')
			$method = array('get', 'post', 'put', 'delete', 'patch', 'options');
		else if(!is_array($method))
			$method = explode('|', $method);

		$method = array_map(function($value){return trim(strtolower($value));}, $method);
	
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
		return $this->setProperty('config', $config);
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
     * @return $this
     */
	public function execute($execute)
	{
		$this->setProperty('execute', $execute);
	
		return $this;
	}

    /**
     * Alias to setExecute
     * @param mixed $handle
     * @return $this
     */
	public function handle($handle)
	{
		$this->setProperty('execute', $handle);

		return $this;
	}

    /**
     * Add new group on for this route.
     * @param array|string|\Callback $subroutes
     * @return Route
     */
	public function setSubroutes($subroutes)
	{
		return $this->setProperty('subroutes', $subroutes);
	}

    /**
     * Alias to setSubroutes
     * @param array|string|callback $subroutes
     * @return Route
     */
	public function group($subroutes)
	{
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
	public function setMiddleware($middleware)
	{
		// reset on each call
		$this->properties['middleware'] = array();

		if(!is_array($middleware))
		{
			$this->properties['middleware'][] = $middleware;
		}
		else
		{
			foreach($middleware as $m)
				$this->properties['middleware'][] = $m;
		}

		return $this;
	}

    /**
     * Add an array of middlewares
     * @param array $middlewares
     * @return $this
     */
	public function addMiddlewares(array $middlewares)
	{
		foreach($middlewares as $middleware)
			$this->properties['middleware'][] = $middleware;

        return $this;
	}

    /**
     * Add middleware to existing
     * @param mixed $middleware
     * @param null $name
     * @return $this
     */
	public function addMiddleware($middleware, $name = null)
	{
	    if($name)
	        $this->properties['middleware'][$name] = $middleware;
        else
            $this->properties['middleware'][] = $middleware;

		return $this;
	}

    /**
     * Alias to addMiddleware
     * @param mixed $middleware handler
     * @param null $name
     * @return $this
     */
	public function middleware($middleware, $name = null)
	{
        if($name)
            $this->properties['middleware'][$name] = $middleware;
        else
            $this->properties['middleware'][] = $middleware;

		return $this;
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
     * @param string $key
     * @param mixed $value
     * @return $this
     */
	public function setAttribute($key, $value)
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Get attribute
	 * @param string $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		return $this->attributes[$key];
	}

	/**
	 * Get all attributes
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

    /**
     * Alias to setAttribute(key, value)
     * @param string $key
     * @param string $value
     * @return $this
     */
	public function attr($key, $value)
	{
		$this->attributes[$key] = $value;

		return $this;
	}

    /**
     *
     * @param mixed $validator
     * @return $this
     */
    public function validate($validator)
    {
        $this->properties['validators'][] = $validator;

        return $this;
    }

    /**
     * @param mixed $validator
     * @return $this
     */
    public function addValidator($validator)
    {
        $this->properties['validators'][] = $validator;

        return $this;
    }

    /**
     * Set meta information
     * @param string $key
     * @param mixed $value
     * @return $this
     */
	public function setMeta($key, $value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k => $v)
				$this->attributes[$k] = $value;

			return $this;
		}

		$this->attributes[$key] = $value;

		return $this;
	}

    /**
     * Alias to setAttribute(), but without first arg checked as if its array.
     * @param string $key
     * @param mixed $value
     * @return $this
     */
	public function meta($key, $value)
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Get route property
	 * @param string $key
	 * @return mixed
	 */
	public function getProperty($key)
	{
		return $this->properties[$key];
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
     * @return $this
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
		return $this->getPath() !== false && $this->getProperty('requestable') === true;
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
     * Alias to setMethod with path settable
     * @param string $method
     * @return Route
     */
	public function method($method, $path = null)
	{
		if($path !== null)
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