<?php namespace Exedra\Application\Map;

class Route
{
	/**
	 * @var string name
	 */
	protected $name;

	/**
	 * @var string absolute name
	 */
	protected $absoluteName;

	/**
	 * @var array stored full routes
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
	 * - module
	 * - config
	 * - base
	 */
	protected $properties = array(
		'path' => '',
		'middleware' => array()
		);

	/**
	 * Level this route is bound to
	 * @var \Exedra\Application\Map\Level
	 */
	protected $level;

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
								'handler' => 'execute',
								'verb' => 'method');

	public function __construct(Level $level, $name, array $properties = array())
	{
		$notation = self::$notation;

		$this->name = $name;
		$this->absoluteName = $level->getUpperRoute() ? $level->getUpperRoute()->getAbsoluteName().$notation.$name : $name;
		$this->level = $level;

		if(count($properties) > 0)
			$this->setProperties($properties);
	}

	/**
	 * Set multiple properties for this route
	 * @param array properties
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
	 * @param string key
	 * @param mixed value
	 * @return this->set'Key'
	 */
	public function parseProperty($key, $value)
	{
		if(isset(self::$aliases[$key]))
			$key = self::$aliases[$key];

		$method = 'set'.ucwords($key);

		$this->$method($value);
	}

	/**
	 * Get name of this route, relative to the current level
	 * @return string of route name.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get current level this route was bound to.
	 * @return \Exedra\Application\Map\Level
	 */
	public function getLevel()
	{
		return $this->level;
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
	 * Get an absolutely resolved uri path
	 * @param params param for named parameter.
	 * @return uri path of all of the related routes to this, with replaced named parameter.
	 */
	public function getAbsolutePath($params = array())
	{
		$routes = $this->getFullRoutes();

		$paths = array();

		foreach($routes as $route)
			$paths[] = $route->pathParameterReplace($params);

		return trim(implode('/', $paths), '/');
	}

	/**
	 * Get uri path property for this route.
	 * @param boolean absolute
	 * @return string
	 */
	public function getPath($absolute = false)
	{
		if(!$absolute)
			return $this->getProperty('path');

		$routes = $this->getFullRoutes();

		$paths = array();

		foreach($routes as $route)
			$paths[] = $route->getProperty('path');

		return trim(implode('/', $paths), '/');
	}

	/**
	 * Return all of the related routes.
	 * @return array
	 */
	public function getFullRoutes()
	{
		// if has saved already, return that.
		if($this->fullRoutes != null)
			return $this->fullRoutes;

		$routes = array();
		$routes[] = $this;
		$level = $this->level;

		while($route = $level->getUpperRoute())
		{
			$routes[] = $route;

			// recursively refer to upperRoute's level
			$level = $route->getLevel();
		}

		$this->fullRoutes = array_reverse($routes);

		return $this->fullRoutes;
	}

	/**
	 * Get parent route name after substracted the current route name.
	 * @return string | null
	 */
	public function getParentRoute()
	{
		$notation = self::$notation;

		$absoluteRoute	= $this->getAbsoluteName();
		$absoluteRoutes	= explode($notation,$absoluteRoute);

		if(count($absoluteRoutes) == 1)
			return null;

		array_pop($absoluteRoutes);
		$parentRoute	= implode($notation,$absoluteRoutes);
		
		return $parentRoute;
	}

	/**
	 * Get a replaced uri path parameter.
	 * @param array data;
	 * @return string of a replaced path
	 */
	public function pathParameterReplace(array $data)
	{
		$path = $this->getProperty('path');

		$segments	= explode('/', $path);

		$newSegments	= Array();
		foreach($segments as $segment)
		{
			if(strpos($segment, '[') === false && strpos($segment, ']') === false)
			{
				$newSegments[]	= $segment;
				continue;
			}

			## strip.
			$segment	= trim($segment, '[]');
			list($key,$segment)	= explode(':', $segment);

			$isOptional	= $segment[strlen($segment)-1] == '?'? true : false;
			$segment	= $isOptional?substr($segment, 0,strlen($segment)-1):$segment;

			## is mandatory, but no parameter passed.
			if(!$isOptional && !isset($data[$segment]))
			{
				if(isset($this->exe))
					$this->exe->exception->create("Url.Create : Required parameter not passed ($segment).");
				else
					throw new \Exedra\Application\Exception\Exception("Url.Create : Required parameter not passed ($segment).", null, null);
			}

			## trailing capture.
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

		return implode('/', $newSegments);
	}

	/**
	 * Validate uri path against the request
	 * @param \Exedra\HTTP\Request request
	 * @param string path
	 * @return array struct of {route, parameter, continue}
	 */
	public function validate(\Exedra\HTTP\Request $request, $path)
	{
		// print_r($query);die;
		foreach(array('method', 'path', 'ajax') as $key)
		{
			// by default, if parameter was set, but query not provided anything.
			// if($this->hasProperty($key) && !isset($query[$key]))
			// 	return array('route'=> false, 'parameter'=> false);
			
			// if this parameter wasn't set, skip validation.
			if(!$this->hasProperty($key))
				continue;

			// $value = $query[$key];

			switch($key)
			{
				case 'method':
				$value = $request->getMethod();
				// return false because method doesn't exist.
				if(!in_array(strtolower($value), $this->getProperty('method')))
					return array('route'=> false, 'parameter'=> false, 'continue'=> false);

				break;
				case 'path':
				$result = $this->validatePath($path);

				if(!$result['matched'])
					return array('route'=>false, 'parameter'=> $result['parameter'], 'continue'=> $result['continue']);

				return array('route'=> $this, 'parameter'=> $result['parameter'], 'continue'=> $result['continue']);
				break;
				case 'ajax':
				if($request->isAjax() != $this->getProperty('ajax'))
					return array('route' => false, 'parameter' => false, 'continue' => false);
				
				break;
			}
		}

		return array('route'=>false, 'parameter'=> array(), 'continue'=> false);
	}

	/**
	 * Validate given uri path
	 * @param string path
	 * @return array of matched flag, and parameter.
	 */
	protected function validatePath($path)
	{
		$continue = true;

		$routePath = $this->getProperty('path');

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

		## 2. route check.
		$segments	= explode('/',  $routePath);
		$paths		= explode('/',  $path);

		## initialize
		$matched	= true;
		$isTrailing = false;
		$pathParams	= Array();

		## route segment loop.
		$equal	= null;

		$equalSegmentLength = count($segments) == count($paths);

		foreach($segments as $no=>$segment)
		{
			## 2.1 non-pattern comparation.
			// if($segment[0] != "[" || $segment[strlen($segment) - 1] != "]") gives notice due to uninitialized segment.
			if($segment == '' || ($segment[0] != '[' || $segment[strlen($segment) - 1] != ']'))
			{
				$equal	= false;

				## need to move this logic outside perhaps.
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

			## 2.2 pattern comparation
			$pattern	= trim($segment, '[]');
			@list($type, $segmentParamName) = explode(':', $pattern); # split by colon.

			## no color was passed. thus, could't retrieve second value.
			if(!$segmentParamName)
			{
				$matched	= false;
				break;
			}

			## 2.2.3 optional flag.
			$isOptional			= $segmentParamName[strlen($segmentParamName)-1] == '?';
			$segmentParamName	= trim($segmentParamName, '?');

			## 2.2.4 no data at current uri path segment.
			if(!isset($paths[$no]))
			{
				## 2.2.4.1 but if optional, continue searching without breaking.
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

			### type matching.
			switch($type)
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
					$pathParams[$segmentParamName] = $path;
					$matched = true;
					$isTrailing = true;
					break 2;
				break;
				// remainder segments into array
				case '**':
					/*if(!$isOptional && $no === 0 && $uri === '')
					{
						$matched = false;
						break 2;
					}*/
					## get all the rest of path for param, and explode it so it return as list of segment.
					$explodes = explode('/', $path, $no+1);
					$pathParams[$segmentParamName]	= explode('/', array_pop($explodes));
					$matched		= true;
					$isTrailing		= true;
					break 2; ## break the param loop, and set matched directly to true.
				break;
				default:
					$matched = false;
					break 2;
				break;
			}

			## need to move this logic outside perhaps.
			if(count($segments) != count($paths))
				$matched = false;

			## set parameter.
			$pathParams[$segmentParamName]	= $paths[$no];
		}

		## build result.
		$result 	= Array();

		$result['continue'] = $equal === false ? false : $continue;

		## pattern matched.
		$result['matched']	= $matched;

		## pass parameter.
		$result['parameter'] = $pathParams;

		## return matched, parameter founds, and remaining_uri (if deeproute)
		return $result;
	}

	/**
	 * Get remaining uri path extracted from the passed one.
	 * @param string path
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
	 * Get sublevel of this route
	 * Resolve the level in case of Closure, string and array
	 * @return \Exedra\Application\Map\Level
	 */
	public function getSubroutes()
	{
		$level = $this->properties['subroutes'];

		if($level instanceof \Exedra\Application\Map\Level)
			return $level;

		return $this->resolveLevel($level);
	}

	/**
	 * Resolve level in case of Closure, string and array
	 * @return \Exedra\Application\Map\Level
	 * @throws \Exception
	 */
	public function resolveLevel($level)
	{
		$type = @get_class($level) ? : gettype($level);

		switch($type)
		{
			case 'Closure':
				$closure = $level;
				$level = $this->level->factory->createLevel($this);
				$closure($level);
				$this->properties['subroutes'] = $level;
				return $level;
			break;
			case 'string':
				$this->properties['subroutes'] = $level = $this->level->factory->createLevelByPattern($this, $level);
				return $level;
			break;
			case 'array':
				$this->properties['subroutes'] = $level = $this->level->factory->createLevel($this, $level);
				return $level;
			break;
			default:
				return $this->level->factory->throwException('Unable to resolve route level. It must be type of Closure, string, or array');
			break;
		}
	}

	/**
	 * Get methods for this route.
	 * @return array
	 */
	public function getMethod()
	{
		if(!$this->hasProperty('method'))
			return array('get', 'post', 'put', 'delete');

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

	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	public function setBase($baseRoute)
	{
		$this->setProperty('base', $baseRoute);
		return $this;
	}

	/**
	 * Set uri path pattern for this route.
	 * @param string path
	 * @return this
	 */
	public function setPath($path)
	{
		if($path !== false)
			$path = trim($path, '/');

		$this->setProperty('path', $path);

		return $this;
	}

	/**
	 * Set method for this route.
	 * @param mixed method (array of method, or /)
	 */
	public function setMethod($method)
	{
		if(!is_array($method))
			$method = explode(',', $method);

		$method = array_map(function($value){return trim(strtolower($value));}, $method);
	
		if($method == 'any')
			$method = array('get', 'post', 'put', 'delete');
	
		$this->setProperty('method', $method);
	}

	/**
	 * Set config for this route.
	 * @param array value
	 */
	public function setConfig(array $value)
	{
		return $this->setProperty('config', $value);
	}

	/**
	 * Set execution property
	 * @param mixed execute
	 */
	public function setExecute($execute)
	{
		$this->setProperty('execute', $execute);
		return $this;
	}

	/**
	 * Add new level on for this route.
	 * @param array or pattern subroutes
	 */
	public function setSubroutes($subroutes)
	{
		// only create Level if the argument is array. else, just save the pattern.
		return $this->setProperty('subroutes', $subroutes);
	}

	/**
	 * Set middleware(s) on this route.
	 * Will reset any
	 * @param mixed middleware
	 */
	public function setMiddleware($middleware)
	{
		if(!is_array($middleware))
		{
			$this->addMiddleware($middleware);
		}
		else
		{
			// reset if array was passed.
			$this->properties['middleware'] = array();

			foreach($middleware as $m)
				$this->addMiddleware($m);
		}

		return $this;
	}

	/**
	 * Add middleware to existing
	 */
	public function addMiddleware($middleware)
	{
		if($middleware === true && $this->level->factory->isExplicit())
			$middleware = 'route='.$this->getAbsoluteName();
		
		$this->properties['middleware'][] = $middleware;
	}

	/**
	 * Set module under this route.
	 * @param string module
	 */
	public function setModule($module)
	{
		return $this->setProperty('module', $module);
	}

	/**
	 * Tag this route
	 * @param string tag
	 */
	public function setTag($tag)
	{
		return $this->setProperty('tag', $tag);
	}

	/**
	 * Get route property
	 * @param string key
	 * @return mixed
	 */
	public function getProperty($key)
	{
		return $this->properties[$key];
	}

	/**
	 * Check whether route property exist or not.
	 * @param string key
	 * @return boolean of existence
	 */
	public function hasProperty($key)
	{
		return isset($this->properties[$key]);
	}

	/**
	 * Set property for this route.
	 * @param string key
	 * @param mixed value
	 */
	protected function setProperty($key, $value)
	{
		$this->properties[$key] = $value;

		return $this;
	}
}


?>