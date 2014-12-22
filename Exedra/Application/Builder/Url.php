<?php
namespace Exedra\Application\Builder;

class Url
{
	private $result			= null;
	private $routePrefix	= false;
	private $baseUrl		= false;
	private $assetUrl		= false;

	public function __construct(\Exedra\Application\Application $app,\Exedra\Application\Execution\Exec $exe = null)
	{
		$this->app	= $app;

		if($exe)
			$this->exe	= $exe;
	}

	/*public function setRoutePrefix($prefix)
	{
		$this->routePrefix = $prefix;
	}*/

	public function base()
	{
		return trim($this->baseUrl,"/");
	}

	public function asset($asset)
	{
		return trim($this->assetUrl,"/")."/".$asset;
	}

	public function setBase($baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	public function setAsset($assetUrl)
	{
		$this->assetUrl	= $assetUrl;
	}

	public function create($routeName,$data = Array())
	{
		## base the routename, either on parent route or the configured routePrefix
		/*if($this->exe)
		{
			$routePrefix = $this->exe->getRoutePrefix();
			$routeName		= $routePrefix?$routePrefix.".".$routeName:$routeName;
		}*/

		$routeName = $this->exe->prefixRoute($routeName);

		## get route data by this name.
		$route	= $this->app->map->getRoute($routeName);

		if(!$route)
		{
			return $this->exe->exception->create("Unable to find route '$routeName'");
		}

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
}