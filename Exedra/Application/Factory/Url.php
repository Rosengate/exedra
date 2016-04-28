<?php
namespace Exedra\Application\Factory;

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
	 * Base asset url
	 * @var string
	 */
	protected $assetUrl;

	public function __construct(
		\Exedra\Application\Map\Level $router,
		\Exedra\Http\ServerRequest $request = null,
		\Exedra\Application\Config $config)
	{
		$this->map = $router;

		$this->request = $request;

		$this->setBase($config->get('app.url') ? : ($request ? $request->getUri()->getScheme().'://'.$request->getUri()->getAuthority() : null ));

		$this->setAsset($config->get('asset.url') ? : $this->baseUrl);
	}

	/**
	 * Get previous url (referer)
	 * @return string
	 */
	public function previous()
	{
		if(!$this->request)
			return null;

		$referer = $this->request->getHeaderLine('referer');

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

	/**
	 * Create url by route name.
	 * @param string routeName
	 * @param array data
	 * @param mixed query (uri query string)
	 *
	 * @throws \InvalidArgumentException
	 */
	public function create($routeName, array $data = array(), array $query = array())
	{
		// build query
		$query = http_build_query($query);

		// get \Exedra\Application\Map\Route by name.
		$route = $this->map->findRoute($routeName);

		if(!$route)
			throw new \InvalidArgumentException('Unable to find route ['.$routeName.']');

		$path = $route->getAbsolutePath($data);

		return $this->base($path).($query ? '?'.$query : null);
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

	/**
	 * Get current url
	 * @param array query
	 * @return string
	 */
	public function current(array $query = array())
	{
		if(!$this->request)
			return;

		$uri = $this->request->getUri();

		if(count($query) > 0)
		{
			// append query to uri
			if($uri->getQuery())
				$query = http_build_query($query);
			else
				$query = '?' . http_build_query($query);
		}
		else
		{
			$query = '';
		}

		return $this->request->getUri().$query;
	}
}