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
	 * @var array parameters
	 * - method
	 * - uri
	 * - subapp
	 * - middleware
	 * - execute
	 * - config
	 */
	protected $parameters = array();

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

	public function __construct(Level $level, $name, array $parameters = array())
	{
		$notation = self::$notation;

		$this->name = $name;
		$this->absoluteName = $level->getUpperRoute() ? $level->getUpperRoute()->getAbsoluteName().$notation.$name : $name;
		$this->level = $level;

		// default uri.
		$this->setUri('');

		if(count($parameters) > 0)
			foreach($parameters as $key=>$value)
				$this->parseParameter($key, $value);
	}

	/**
	 * Manual setter based on string.
	 * @param string key
	 * @param mixed value
	 * @return this->set'Key'
	 */
	public function parseParameter($key, $value)
	{
		if($key == 'bind:middleware')
			$key = 'middleware';

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
	 * Get an absolute uri
	 * @param params param for named parameter.
	 * @return uri of all of the related routes to this, with replaced named parameter.
	 */
	public function getAbsoluteUri($params = array())
	{
		$routes = $this->getFullRoutes();

		$uris = array();
		foreach($routes as $route)
		{
			$uris[] = $route->uriParameterReplace($params);
		}

		return trim(implode('/', $uris), '/');
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
	 * Get a replaced uri parameter.
	 * @param array data;
	 * @return string of a replaced uri
	 */
	public function uriParameterReplace(array $data)
	{
		$uri = $this->getParameter('uri');

		$segments	= explode("/",$uri);

		$newSegments	= Array();
		foreach($segments as $segment)
		{
			if(strpos($segment,"[") === false && strpos($segment, "]") === false)
			{
				$newSegments[]	= $segment;
				continue;
			}

			## strip.
			$segment	= trim($segment,"[]");
			list($key,$segment)	= explode(":",$segment);

			$isOptional	= $segment[strlen($segment)-1] == "?"?true:false;
			$segment	= $isOptional?substr($segment, 0,strlen($segment)-1):$segment;

			## is mandatory, but no parameter passed.
			if(!$isOptional && !isset($data[$segment]))
			{
				if($this->exe)
					$this->exe->exception->create("Url.Create : Required parameter not passed ($segment).");
				else
					throw new \Exedra\Application\Exception\Exception("Url.Create : Required parameter not passed ($segment).",null,null);
			}

			## trailing capture.
			if($key == "**")
			{
				if(is_array($data[$segment]))
				{
					$data[$segment] = implode("/",$data[$segment]);
				}
			}
				
			if(!$isOptional)
				$newSegments[]	= $data[$segment];
			else
				if(isset($data[$segment]))
					$newSegments[]	= $data[$segment];
				else
					$newSegments[]	= "";
		}

		return implode("/",$newSegments);
	}

	/**
	 * Query the route. return with uri parameter.
	 * @param associative array of result :
	 * - route \Exedra\Application\Map\Route or false
	 * - parameter array
	 * - equal boolean
	 * @return array struct of {route, parameter, equal}
	 */
	public function validate(array $query)
	{
		// print_r($query);die;
		foreach(array('method', 'uri', 'ajax') as $key)
		{
			// by default, if parameter was set, but query not provided anything.
			if($this->hasParameter($key) && !isset($query[$key]))
				return array('route'=> false, 'parameter'=> false);
			
			// if this parameter wasn't set, skip validation.
			else if(!$this->hasParameter($key))
				continue;

			$value = $query[$key];

			switch($key)
			{
				case "method":
				// return false because method doesn't exist.
				if(!in_array(strtolower($value), $this->getParameter('method')))
					return array('route'=> false, 'parameter'=> false, 'equal'=> false);

				break;
				case "uri":
				$result = $this->validateURI($value);

				if(!$result['matched'])
					return array('route'=>false, 'parameter'=> $result['parameter'], 'equal'=> $result['equal']);

				return array('route'=> $this, 'parameter'=> $result['parameter'], 'equal'=> $result['equal']);
				break;
				case "ajax":

				break;
			}
		}

		return array('route'=>false, 'parameter'=> array(), 'equal'=> false);
	}

	/**
	 * Validate given uri
	 * @param string uri
	 * @return array of matched flag, and parameter.
	 */
	private function validateURI($uri)
	{
		$routeURI = $this->getParameter('uri');

		if($routeURI === false)
			return false;

		if($routeURI === "")
		{
			return array('matched'=> ($uri === "" ? true : false), 'equal'=> null, 'parameter'=> array());
		}


		## 2. route check.
		$segments	= explode("/",$routeURI);
		$uris		= explode("/",$uri);

		## initialize
		$matched	= true;
		$isTrailing = false;
		$uriParams	= Array();

		## route segment loop.
		$equal	= null;

		foreach($segments as $no=>$segment)
		{
			## 2.1 non-pattern comparation.
			// if($segment[0] != "[" || $segment[strlen($segment) - 1] != "]") gives notice due to uninitialized segment.
			if($segment == "" || ($segment[0] != "[" || $segment[strlen($segment) - 1] != "]"))
			{
				$equal	= false;
				## need to move this logic outside perhaps.
				if(count($segments) != count($uris))
					$matched = false;

				if($uris[$no] != $segment)
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
			$pattern	= trim($segment,"[]");
			list($type,$segmentParamName) = explode(":",$pattern); # split by colon.

			## no color was passed. thus, could't retrieve second value.
			if(!$segmentParamName)
			{
				$matched	= false;
				break;
			}

			## 2.2.3 optional flag.
			$isOptional			= $segmentParamName[strlen($segmentParamName)-1] == "?";
			$segmentParamName	= trim($segmentParamName,"?");

			## 2.2.4 no data at current uri segment.
			if(!isset($uris[$no]))
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

			### type matching.
			switch($type)
			{
				case "":# match all, so do nothing.
				if($uris[$no] == "" && !$isOptional)
				{
					$matched	= false;
					break 2;
				}
				break;
				case "i":# Integer
					if(!is_numeric($uris[$no]))
					{
						$matched = false;
						break 2;
					}
				break;
				case "**":# trailing!
					## get all the rest of uri for param, and explode it so it return as list of segment.
					$explodes = explode("/",$uri,$no+1);
					$uriParams[$segmentParamName]	= explode("/",array_pop($explodes));
					$matched		= true;
					$isTrailing		= true;
					break 2; ## break the param loop, and set matched directly to true.
				break;
				default:
					$matched	= false;
					break 2;
				break;
			}

			## need to move this logic outside perhaps.
			if(count($segments) != count($uris))
			{
				$matched = false;
			}

			## set parameter.
			$uriParams[$segmentParamName]	= $uris[$no];
		}

		## build result.
		$result 	= Array();

		$result['equal'] = $equal;

		## pattern matched.
		$result['matched']	= $matched;

		## pass parameter.
		$result['parameter'] = $uriParams;

		## return matched, parameter founds, and remaining_uri (if deeproute)
		return $result;
	}

	/**
	 * Get remaining uri extracted from the passed uri.
	 * @param string uri
	 * @return string uri
	 */
	public function getRemainingUri($uri)
	{
		$uris = explode("/", $uri);
		$new_uriR	= Array();
		for($i=count(explode("/", $this->parameters['uri']));$i<count($uris);$i++)
		{
			$new_uriR[]	= $uris[$i];
		}

		return $this->parameters['uri'] != '' ? implode("/", $new_uriR) : $uri;
	}

	/**
	 * Check whether has subroute or not.
	 * @return boolean of existence.
	 */
	public function hasSubroute()
	{
		return isset($this->parameters['subroute']);
	}

	/**
	 * Get subroute (Map\Level)
	 * @return \Exedra\Application\Map\Level
	 */
	public function getSubroute()
	{
		$level = $this->parameters['subroute'];

		// is a string based level.
		if(is_string($level))
			return $this->level->factory->createLevelByPattern($this, $level);

		return $level;
	}

	/**
	 * Check if this route has execution parameter.
	 * @return boolean of existence.
	 */
	public function hasExecution()
	{
		return isset($this->parameters['execute']);
	}

	/**
	 * Set uri pattern for this route.
	 * @param string uri
	 * @return this
	 */
	public function setUri($uri)
	{
		$this->setParameter('uri', $uri);
		return $this;
	}

	/**
	 * Set method for this route.
	 * @param mixed method (array of method, or /)
	 */
	public function setMethod($method)
	{
		$method = !is_array($method) ? explode(',', $method) : $method;
		$method = array_map('strtolower', $method);
		$this->setParameter('method', $method);
	}

	/**
	 * Set config for this route.
	 * @param array value
	 */
	public function setConfig(array $value)
	{
		return $this->setParameter('config', $value);
	}

	/**
	 * Set execution parameter
	 * @param mixed execute
	 */
	public function setExecute($execute)
	{
		$this->setParameter('execute', $execute);
		return $this;
	}

	/**
	 * Add new level on for this route.
	 * @param array or pattern subroutes
	 */
	public function setSubroute($subroutes)
	{
		// only create Level if the argument is array. else, just save the pattern.
		$subroutes = is_array($subroutes) ? $this->level->factory->createLevel($this, $subroutes) : $subroutes;
		return $this->setParameter('subroute', $subroutes);
	}

	/**
	 * Set middleware on this route.
	 * @param mixed middleware
	 */
	public function setMiddleware($middleware)
	{
		return $this->setParameter('middleware', $middleware);
	}

	/**
	 * Set sub application under this route.
	 * @param subapp
	 */
	public function setSubapp($subapp)
	{
		return $this->setParameter('subapp', $subapp);
	}

	/**
	 * Generally get a referenced parameter.
	 * @param string key
	 * @return parameter value
	 */
	public function &getParameter($key)
	{
		return $this->parameters[$key];
	}

	/**
	 * Check whether parameter exist or not.
	 * @param string key
	 * @return boolean of existence
	 */
	public function hasParameter($key)
	{
		return isset($this->parameters[$key]);
	}

	/**
	 * Set parameter for this route.
	 * @param string key
	 * @param mixed value
	 */
	public function setParameter($key, $value)
	{
		switch($key)
		{
			case 'method':
				if($value == 'any')
					$value = array('get', 'post', 'put', 'delete');
			break;
			case 'subroute':
				// $value = new Level($this, $value);
			break;
		}

		$this->parameters[$key] = $value;

		return $this;
	}
}


?>