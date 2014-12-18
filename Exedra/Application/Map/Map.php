<?php
namespace Exedra\Application\Map;

class Map
{
	private $route 		= Array();
	public $binds 		= Array();
	private $request 	= null;
	private $resultData	= null;
	private $loader;
	public	$config		= Array();
	private $app		= null;

	## variables.
	private $methodDelimiter	= ",";

	public function __construct(\Exedra\Application\Loader $loader)
	{
		$this->loader	= $loader;
	}

	public function setApp(\Exedra\Application\Application $app)
	{
		$this->app	= $app;
	}

	public function onRoute($routeName,$action,$param)
	{
		list($action,$actionExecution)	= explode(":",$action);

		switch($action)
		{
			case "bind":
			$this->bindRoute($routeName,$actionExecution,$param);
			break;
		}
	}

	## Main function to add Route.
	public function addRoute($firstParam,$secondParam = null)
	{
		## second param was passed, it's adding subroute.
		if($secondParam && is_string($firstParam))
		{
			$routes	= $secondParam;
			$routeStorage	= &$this->_getRoute($firstParam);
			$routeStorage	= &$routeStorage['subroute'];
		}
		## add route on upmost level.
		else if(is_string($firstParam))
		{
			$routes			= $this->parseRoute($firstParam);
			$routeStorage	= &$this->route;
		}
		else
		{
			$routes			= $firstParam;
			$routeStorage	= &$this->route;
		}

		## sanitize route.
		$route	= $this->_addRoute($routes);

		## merge 
		$routeStorage	= array_merge($routeStorage,$route);
	}

	private function _addRoute(&$routes,$parentRouteNames = Array())
	{
		$route	= Array();
		// $firstlevel	= !$routeNames?true:false;
		foreach($routes as $key=>$data)
		{
			$routeData	= Array();

			## if this loop is withing the first level of route name.
			// if($firstlevel)
				// $routeNames	= Array();

			// $routeNames[]	= $key;

			## subsequential based
			if(isset($data[0]))
			{
				$routeData['method']	= $data[0];
				$routeData['uri']		= $data[1];
				$routeData['execute']	= $data[2];
			}
			## associative based
			else
			{
				foreach($data as $data_key=>$val)
				{
					if(in_array($data_key,Array("method","uri","execute","subroute","config","ajax","ext","subapp")))
					{
						$routeData[$data_key]	= $val;
					}

					if(strpos($data_key,"bind:") === 0)
					{
						$routeName	= array_merge($parentRouteNames,Array($key));
						$this->onRoute(implode(".",$routeName),$data_key,$val);
					}

					if($data_key == "config")
					{
						$routeName	= array_merge($parentRouteNames,Array($key));
						$this->setConfig(implode(".",$routeName),$val);
					}
				}
			}

			## explode method, by delimiter.
			$routeData['method']	= $routeData['method'] == null || $routeData['method'] == "any"?"GET,POST,PUT,DELETE":$routeData['method'];
			$routeData['method']	= is_array($routeData['method'])?$routeData['method']:explode($this->methodDelimiter,$routeData['method']);
			$routeData['method']	= array_map("strtolower", $routeData['method']);

			## if execute key is an array, treat it as subroute.
			if(is_array($routeData['execute']) || (is_string($routeData['execute']) && strpos($routeData['execute'], "route:") === 0))
			{
				$routeData['subroute']	= $routeData['execute'];
				unset($routeData['execute']);
			}

			$route[$key]	= $routeData;

			## if subroute exists. create subroute.
			if($routeData['subroute'])
			{
				$routeData['subroute'] = $this->parseRoute($routeData['subroute']);

				$parents	= array_merge($parentRouteNames,Array($key));
				$route[$key]['subroute']	= $this->_addRoute($routeData['subroute'],$parents);
			}
			else if($routeNames)
			{

			}
		}

		return $route;
	}

	private function parseRoute($route)
	{
		if(is_string($route))
		{
			if(strpos($route, "route:") === 0)
			{
				$arr	= $this->loader->load($route,Array("app"=>$this->app));

				if(!is_array($arr))
				{
					throw new \Exception("Unable to find routes in $route");
				}

				return $arr;
			}
		}
		else
		{
			return $route;
		}
	}

	## return a referenced property route based on level.
	private function &_getRoute($routeName,$data = null)
	{
		$routeNameR	= explode(".",$routeName);

		$route	= &$this->route;
		$result	= false;
		foreach($routeNameR as $no=>$routeName)
		{
			## no found.
			if(!isset($route[$routeName]))
			{
				break;
			}

			## ends.
			if(count($routeNameR) == $no+1)
			{
				$result	= &$route[$routeName];
				break;
			}

			$route	= &$route[$routeName]['subroute'];
		}

		return $result;
	}

	public function getRoute($routeName)
	{
		return $this->find(Array("route"=>$routeName));
	}

	private function bindRoute($routeName,$bindName,$execution)
	{
		$this->binds[$routeName][$bindName]	= $execution;
	}

	public function setConfig($routeName,$key,$value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->setConfig($routeName,$k,$v);
			}
		}
		else
		{
			$this->config[$routeName][$key]	= $value;
		}
	}

	## Recursively and safely find deeper route on every level. Beware of the recursion demon.
	private function routeFind($route,$query,&$routeReference = null,$subrouteChecking = false)
	{
		$routeReference	= !isset($routeReference)?Array():$routeReference;
		foreach($route as $key=>$routeData)
		{
			$routeReference['level']	= ($routeReference['level']?:0) + 1;

			## get route data.
			$routeName	= $key;
			$methodR	= $routeData['method'];
			$route		= $routeData['route'];

			## subroute check.
			$hasSubroute	= $routeData['subroute']?true:false;

			## found, and assign pre_uri.
			$routeMatch	= $this->validate($routeData,$query,$hasSubroute);

			if($routeMatch['matched'] != false)
			{
				## build routeData to be passed.
				$routeReference['routeData'][$routeName]	= Array(
								"parameter"=>$routeMatch['parameter']
					);
				
				$routeReference['execution']	= $routeData['execute'];

				## the third parameter is not an array, so just return true.
				if(!$hasSubroute)
				{
					return Array(
							"result"=>true,
							#"execution"=>$routeReference['execution'],
							"data"=>Array(
									"route"=>$this->_prepareRouteData($routeReference['routeData']),
											),
								);
				}
				else
				{
					## find deeper route based on remaining uri.
					$query['uri']	= $routeMatch['remaining_uri'];

					$findRoute	= $this->routeFind($routeData['subroute'],$query,$routeReference,true);
					if($findRoute['result'])
					{
						return Array(
							"result"=>true,
							#"execution"=>$routeReference['execution'],
							"data"=>Array(
									"route"=>$this->_prepareRouteData($routeReference['routeData']),
									// "route"=>$routeReference['routeData'],
											),
								);
					}
					## deeper route not found. reset.
					else
					{
						## reset
						$subrouteChecking = false;
						
						## just unset the last route in routeReference.
						$routes	= array_keys($routeReference['routeData']);
						$lastSubroute = end($routes);
						unset($routeReference['routeData'][$lastSubroute]);
					}
				}
			}
			## not found. continue searching.
			else
			{
				continue;
			}
		}

		return Array("result"=>false,"response"=>null);
	}

	private function _prepareRouteData($routeData)
	{
		$routename	= implode(".",array_keys($routeData));

		$newParams	= Array();
		foreach($routeData as $name=>$params)
		{
			$nameR[]	= $name;
			$name		= implode(".",$nameR);
			if(count($params) > 0)
			{
				foreach($params as $key=>$vals)
				{
					foreach($vals as $valsKey=>$val)
					{
						$newParams[$valsKey]	= $val;
					}
				}
			}
		}

		return Array("name"=>$routename,"parameter"=>$newParams);
	}

	private function validateQuery($routeData,$query)
	{
		foreach($routeData as $key=>$val)
		{
			switch($key)
			{
				case "method":
				if(!in_array($query['method'],$val))
					return false;
				break;
				case "ajax":
					if(!isset($query['ajax']))
						return false;

					if($query['ajax'] != $val)
						return false;
				break;
				case "ext":
					## extract ext from uri.

				break;
			}
		}

		return true;
	}

	private function validateURI($routeURI,$uri,$deepRoute)
	{
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
			if($segment == null && $deepRoute)
			{
				break;
			}

			## 2.1 non-pattern comparation.
			if($segment[0] != "[" || $segment[strlen($segment) - 1] != "]")
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
					$uriParams[$segmentParamName]	= explode("/",array_pop(explode("/",$uri,$no+1)));
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

		## set matched into result.
		$result['matched']	= $matched;

		## pass parameter.
		$result['parameter'] = $uriParams;

		if($deepRoute && ($equal === null || $equal === true))
		{
			## set as true.
			$result['matched'] = true;

			## since trailing would sedut the remaining uri, just return empty.
			if($isTrailing)
				return "";

			## normal. just substract and return the remaining uri.
			$total	= count($segments);

			## rebuild. since i don't have internet currently.
			$new_uriR	= Array();
			for($i=$total;$i<count($uris);$i++)
			{
				$new_uriR[]	= $uris[$i];
			}

			## pass remaining uri.
			// $result['remaining_uri']	= implode("/",$new_uriR);  # old 
			$result['remaining_uri']	= $routeURI != ""?implode("/",$new_uriR):$uri; 
		}

		## return matched, parameter founds, and remaining_uri (if deeproute)
		return $result;
	}

	## Route validation.
	private function validate($routeData,$query,$deepRoute = false)
	{
		## Query check
		if(!$this->validateQuery($routeData,$query))
			return Array("matched"=>false);

		## URI Check
		return $this->validateURI($routeData['uri'],$query['uri'],$deepRoute);
	}

	public function find($query)
	{
		if(isset($query['uri']))
		{
			$uri_querying	= true;
			$result	= $this->routeFind($this->route,Array(
				"method"=>$query['method'],
				"uri"	=>$query['uri'],
				"uri_original"	=>$query['uri'], ## save original, so that it may not be altered by a deeproute search.
				"ajax"	=>$query['ajax'],
				"ext"	=>$query['ext']
				));
		}
		else
		{
			$uri_querying	= false;
		}

		## If both were passed (uri/method and route, do comparation);
		if($result['result'] && $query['route'] && ($result['data']['route']['name'] != $query['route']))
			return false;

		if($result['result'] || $query['route'])
		{
			## result not found (if it's doing a uri_querying)
			if($query['route'] && !$result['result'] && $uri_querying)
				return false;

			$name	= isset($query['route'])?$query['route']:$result['data']['route']['name'];
			$parameters	= !$query['route'] || $uri_querying?$result['data']['route']['parameter']:Array();

			$route	= $this->executeRoute($name,$parameters);

			if(!$route['result'])
			{
				return false;
			}

			## build result.
			$finding['name']			= !$query['route']?$result['data']['route']['name']:$query['route'];
			$finding['route']			= $route['result'];
			$finding['parameters']		= $route['parameter'];

			return $finding;
		}

		return false;
	}

	## Return array, with it's routing attribute.
	private function extractParameterFromUri($uri)
	{
		$uriR	= explode("/",$uri);
		$keys	= Array();

		$typeR	= Array(
			"i"=>"integer",
			"**"=>"trailing",
			""=>"string"
						);

		foreach($uriR as $segment)
		{
			$key	= trim($segment,"[]");
			list($type,$key)	= explode(":",$key);

			## build val
			$valR['type']		= $typeR[$type];
			$valR['optional'] 	= $key[strlen($key-1)] == "?"?true:false;

			$key	= trim($key,"?");
			$keys[$key]	= $valR;
		}

		return $keys;
	}

	## main execution. Create bind list along the way.
	public function executeRoute($routeName,$parameter = Array())
	{
		$routeNameR	= explode(".",$routeName);

		$routes	= &$this->route;
		$result	= false;

		$executedRoutes	= Array();
		foreach($routeNameR as $no=>$route)
		{
			## no found.
			if(!isset($routes[$route]))
			{
				break;
			}

			$executedRoutes[]	= $route;

			## ends.
			$temp		= $routes[$route];
			unset($temp['subroute']);
			$routeR[]	= $route;
			$result[implode(".",$routeR)]	= $temp;
			if(count($routeNameR) == $no+1)
			{
				// $result	= &$route[$routeName];
				break;
			}

			$routes	= &$routes[$route]['subroute'];
		}

		return Array("result"=>$result,"parameter"=>$parameter);
	}
}



?>