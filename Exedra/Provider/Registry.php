<?php
namespace Exedra\Provider;

/**
 * List of registered providers
 */
class Registry
{
	/**
	 * List of registered provider
	 * @param array providers
	 */
	protected $providers = array();

	/**
	 * An index for deferred providers
	 * @var array providerDeferred
	 */
	protected $providersDeferred = array();

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
	public function add($provider, array $dependencies = array())
	{
		if(count($dependencies) > 0)
		{
			foreach($dependencies as $name)
			{
				@list($type, $dependency) = explode('.', $name, 2);

				if(!$dependency)
				{
					$dependency = $type;
					
					$type = 'service';
				}
				else
				{
					if(!in_array($type, array('service', 'callable', 'factory')))
					{
						$type = 'service';

						$dependency = $name;
					}
				}

				$this->providersDeferred[$type.'.'.$dependency] = $provider;
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

			if(method_exists($provider, 'provides') && is_array($dependencies = $provider::provides()) && count($dependencies) > 0)
				return $this->add($provider, $dependencies);

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
	 * Differed providers register
	 * Invoked on application container dependency search
	 * @param string service|factory|callable
	 * @return string
	 */
	public function listen($name)
	{
		if(!isset($this->providersDeferred[$name]))
			return;

		$this->register(new $this->providersDeferred[$name]);

		unset($this->providersDeferred[$name]);
	}
}