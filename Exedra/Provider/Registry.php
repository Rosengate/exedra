<?php
namespace Exedra\Provider;

/**
 * List of registered providers
 */
class Registry
{
	protected $providers = array();

	/**
	 * An index for deferred providers
	 * @var array providerDeferred
	 */
	protected $providerDeferred = array();

	/**
	 * Application instance
	 * @var \Exedra\Application app instance
	 */
	protected $app;

	/**
	 * flag for late registry
	 * @param boolean
	 */
	protected $lateRegistry = false;

	public function __construct(\Exedra\Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Register the provider.
	 * If any types is passed, it will be assumed as deferred provider
	 * @param string provider fully qualified class name
	 * @param array types
	 * @return string
	 */
	public function add($provider, array $types = array())
	{
		if(count($types) > 0)
		{
			foreach($types as $type => $dependencies)
			{
				if(!is_array($dependencies))
					throw new \Exedra\Exception\InvalidArgumentException('Value of the key-pair type of dependencies must be an array.');

				foreach($dependencies as $name)
					$this->providerDeferred[$type][$name] = $provider;
			}
		}
		// register.
		else
		{

			if($this->lateRegistry == true)
			{
				$this->providers[$provider] = false;

				return;
			}

			$this->providers[$provider] = true;

			$this->register(new $provider);
		}
	}

	/**
	 * Register the given provider.
	 * @param \Exedra\Provider\ProviderInterface
	 */
	public function register(\Exedra\Provider\ProviderInterface $provider)
	{
		$provider->register($this->app);
	}

	/**
	 * Flag this registry as late registry.
	 * Any provider classes added will not be registered until boot() method run
	 */
	public function flagAsLateRegistry()
	{
		$this->lateRegistry = true;
	}

	/**
	 * Boot all the late registry providers
	 */
	public function boot()
	{
		foreach($this->providers as $provider => $registered)
		{
			if($registered === false)
				$this->register(new $provider);
		}
	}

	/**
	 * Differed register
	 * Invoked on application container dependency search
	 * @param string type
	 * @param string name
	 */
	public function registerDeferred($type, $name)
	{
		if(!isset($this->providerDeferred[$type][$name]))
			return;

		$this->register(new $this->providerDeferred[$type][$name]);
	}
}