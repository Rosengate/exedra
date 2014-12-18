<?php
namespace Exedra\Application\Builder;

class Url
{
	private $result			= null;
	private $routePrefix	= false;
	private $baseUrl		= false;

	public function __construct(\Exedra\Application\Application $app,\Exedra\Application\Execution\Exec $result = null)
	{
		$this->app	= $app;

		if($result)
			$this->result	= $result;
	}

	public function setRoutePrefix($prefix)
	{
		$this->routePrefix = $prefix;
	}

	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	public function create($routeName,$data = Array())
	{
		## base the routename, either on parent route or the configured routePrefix
		if($this->result)
		{
			if($this->routePrefix)
			{
				$routePrefix	= $this->routePrefix;
			}
			else
			{
				$parentRoute	= $this->result->getRouteName();
				$parentRoutes	= explode(".",$parentRoute);
				array_pop($parentRoutes);
				$routePrefix	= implode(".",$parentRoutes);
			}

			$routeName		= $routePrefix.".".$routeName;
		}

		## get route data by this name.
		$route	= $this->app->map->getRoute($routeName);

		$uris	= Array();
		foreach($route['route'] as $routeLevel=>$routeData)
		{
			$uri	= isset($routeData['uri'])?$routeData['uri']:false;
			## build uri.
			if($uri && $uri != "")
			{
				$uris[]	= $this->replaceSegments($routeData['uri'],$data);
			}
		}

		$url	= trim(implode("/",$uris),"/");

		if($this->baseUrl)
			$url	= trim($this->baseUrl,"/")."/".$url;

		return $url;
	}

	private function replaceSegments($segments,$data)
	{
		$segments	= explode("/",$segments);

		$newSegments	= Array();
		foreach($segments as $segment)
		{
			## strip.
			$segment	= trim($segment,"[]");
			list($key,$segment)	= explode(":",$segment);

			$isOptional	= $segment[strlen($segment)-1] == "?"?true:false;
			$segment	= $isOptional?substr($segment, 0,strlen($segment)-1):$segment;

			## is mandatory, but no parameter passed.
			if(!$isOptional && !isset($data[$segment]))
				throw new \Exception("Url.Create : Required parameter not passed ($segment).", 1);
				
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
}