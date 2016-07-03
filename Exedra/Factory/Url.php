<?php
namespace Exedra\Factory;

/**
 * A route oriented url generator
 */
class Url implements UrlInterface
{
	/**
	 * Absolute Base url
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * Absolute base asset url
	 * If not given on construct, will use the baseUrl
	 * @var string
	 */
	protected $assetUrl;

	/**
	 * Request instance
	 * @var \Exedra\Http\ServerRequest|null
	 */
	protected $request;

	protected $callables = array();

	/**
	 * Application map
	 * @param \Exedra\Routing\Level
	 */
	protected $map;

	public function __construct(
		\Exedra\Routing\Level $router,
		\Exedra\Http\ServerRequest $request = null,
		$appUrl = null,
		$assetUrl = null)
	{
		$this->map = $router;

		$this->request = $request;

		$this->setBase($appUrl ? : ($request ? $request->getUri()->getScheme().'://'.$request->getUri()->getAuthority() : null ));

		$this->setAsset($assetUrl ? : $this->baseUrl);

		// add a dynamic alias to addCallable()
		$this->addCallable('callable', function($name, \Closure $closure)
		{
			return $this->addCallable($name, $closure);
		});
	}

	/**
	 * Get previous url (referer)
	 * @return string
	 */
	public function previous()
	{
		if(!$this->request)
			throw new \Exedra\Execution\Exception('Http Request does not exist.');

		$referer = $this->request->getHeaderLine('referer');

		return $referer ? : false;
	}

	public function __call($name, array $args = array())
	{
		if(!isset($this->callables[$name]))
			throw new \Exedra\Exception\NotFoundException('Method / callable ['.$name.'] does not exists');

		if(isset($this->callables[$name.'_bound']))
			$callable = $this->callables[$name.'_bound'];
		else
			$callable = $this->callables[$name.'_bound'] = $this->callables[$name]->bindTo($this);

		return call_user_func_array($callable, $args);
	}

	/**
	 * Register a callables
	 * @param string name
	 * @param \Closure callable
	 */
	public function addCallable($name, \Closure $callable)
	{
		$this->callables[$name] = $callable;

		return $this;
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
		// get \Exedra\Routing\Route by name.
		$route = $this->map->findRoute($routeName);

		if(!$route)
			throw new \InvalidArgumentException('Unable to find route ['.$routeName.']');

		$path = $route->getAbsolutePath($data);

		return $this->base($path).($query ? '?'. http_build_query($query) : null);
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
			throw new \Exedra\Exception\InvalidArgumentException('Http Request does not exist.');

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