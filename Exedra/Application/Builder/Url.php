<?php
namespace Exedra\Application\Builder;

/**
 * A Route Oriented Url builder.
 */

class Url
{
	/**
	 * Base url
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * Asset url
	 * @var string
	 */
	protected $assetUrl;

	public function __construct(\Exedra\Application\Application $app, \Exedra\Application\Execution\Exec $exe)
	{
		$this->app	= $app;
		$this->exe	= $exe;

		// initiate base and asset url
		$this->initiateUrl();
	}

	/**
	 * Initiate url, prioritize exec instance.
	 */
	protected function initiateUrl()
	{
		// base url
		if($this->exe->config->has('app.url'))
			$this->setBase($this->exe->config->get('app.url'));
		else if($this->app->config->has('app.url'))
			$this->setBase($this->app->config->get('app.url'));

		// asset url
		if($this->exe->config->has('asset.url'))
			$this->setAsset($this->exe->config->get('asset.url'));
		else if($this->app->config->has('asset.url'))
			$this->setAsset($this->app->config->get('asset.url'));
	}

	/**
	 * Get url prefixed with $baseUrl
	 * @param string uri (optional)
	 * @return string
	 */
	public function base($uri = null)
	{
		return rtrim($this->baseUrl, '/' ).($uri ? '/' . trim($uri, '/') : '');
	}

	/**
	 * Get url of parent route
	 */
	public function parent()
	{
		return $this->create('@'.$this->exe->getParentRoute());
	}

	/**
	 * Get asset url prefixed with $assetUrl
	 * @param string asset uri (optonal)
	 * @return string
	 */
	public function asset($asset = null)
	{
		return rtrim($this->assetUrl,"/").($asset ? "/". trim($asset, '/') : '');
	}

	/**
	 * Set $baseUrl
	 * @param string baseUrl
	 * @return this
	 */
	public function setBase($baseUrl)
	{
		$this->baseUrl = $baseUrl;
		return $this;
	}

	/**
	 * Set $assetUrl
	 * @param string assetUrl
	 * @return this
	 */
	public function setAsset($assetUrl)
	{
		$this->assetUrl	= $assetUrl;
		return $this;
	}

	/**
	 * Rebuild current route
	 * @return string
	 */
	public function current()
	{
		return $this->create('@'.$this->exe->getRoute(true), $this->exe->param());
	}

	/**
	 * Create url by route name.
	 * @param string routeName
	 * @param array data
	 * @param mixed query (uri query)
	 */
	public function create($routeName, array $data = array(), $query = null)
	{
		// build query
		if(is_array($query) && count($query) > 0)
		{
			$queries = array();
			foreach($query as $k=>$v)
			{
				$queries[] = $k.'='.$v;
			}
			$query = implode('&', $queries);
		}
		
		$routeName = $this->exe->prefixRoute($routeName);

		// get \Exedra\Application\Map\Route by name.
		$route = $this->app->map->getRoute($routeName);

		if(!$route)
			return $this->exe->exception->create('Unable to find route '.$routeName.' while creating a url');

		$uri = $route->getAbsoluteUri($data);

		// return ($this->baseUrl ? trim($this->baseUrl, '/') .'/'. $uri : $uri) . ($query ? '?'. $query : null);
		return ($this->baseUrl ? trim($this->baseUrl, '/') .'/'. $uri : $uri) . ($query ? '?'. $query : null);
	}
}