<?php
namespace Exedra\Application\Builder;

/**
 * A route oriented url generator
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

	public function __construct(\Exedra\Application $app)
	{
		$this->app	= $app;

		// initiate base and asset url
		$this->initiateUrl();
	}

	/**
	 * Initiate url, prioritize exec instance.
	 */
	protected function initiateUrl()
	{
		// base url
		if($this->app->config->has('app.url'))
			$this->setBase($this->app->config->get('app.url'));

		// asset url
		if($this->app->config->has('asset.url'))
			$this->setAsset($this->app->config->get('asset.url'));
	}

	/**
	 * Get previous url (referer)
	 * @return string
	 */
	public function previous()
	{
		$referer = $this->app->request->getHeaderLine('referer');

		return $referer ? : false;
	}

	/**
	 * Get url prefixed with $baseUrl
	 * @param string path (optional)
	 * @return string
	 */
	public function base($path = null)
	{
		return ($this->baseUrl ? rtrim($this->baseUrl, '/' ).'/' : '/').($path ? trim($path, '/') : '');
	}

	/**
	 * Alias to base()
	 * @param string path
	 * @return string
	 */
	public function to($path = null)
	{
		return $this->base($path);
	}

	/**
	 * Get asset url prefixed with $assetUrl
	 * @param string asset path (optonal)
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

	public function getExceptionBuilder()
	{
		return $this->app->exception;
	}

	/**
	 * Create url by route name.
	 * @param string routeName
	 * @param array data
	 * @param mixed query (uri query string)
	 */
	public function create($routeName, array $data = array(), array $query = array())
	{
		// build query
		$query = http_build_query($query);

		// get \Exedra\Application\Map\Route by name.
		$route = $this->app->map->findRoute($routeName);

		if(!$route)
			return $this->getExceptionBuilder()->create('Unable to find route '.$routeName.' while creating a url');

		$path = $route->getAbsolutePath($data);

		// return ($this->baseUrl ? trim($this->baseUrl, '/') .'/'. $uri : $uri) . ($query ? '?'. $query : null);
		return $this->base($path).($query ? '?'.$query : null);
		// return ($this->baseUrl ? trim($this->baseUrl, '/') .'/'. $uri : '/'.$uri) . ($query ? '?'. $query : null);
	}

	/**
	 * Alias to create()
	 * @param string routename
	 * @param array data
	 * @param array query
	 */
	public function route($routeName, array $data = array(), array $query = array())
	{
		return $this->create($routeName, $data, $query);
	}
}