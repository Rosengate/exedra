<?php
namespace Exedra\Provider;
use Exedra\Contracts\Provider\Provider;

/**
 * List of registered providers
 */
class Registry
{
    /**
     * List of registered provider
     * @param array $providers
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
     * A flexible prover registrar
     * Register the provider given the class name
     * Or if the Provider itself, register
     * @param string $provider fully qualified class name
     * @return null
     */
    public function add($provider)
    {
        if(is_object($provider) && $provider instanceof Provider)
            return $this->register($provider);

        if($this->lateRegistry == true)
        {
            $this->providers[$provider] = false;

            return $this;
        }

        if(method_exists($provider, 'provides') && is_array($dependencies = $provider::provides()) && count($dependencies) > 0)
            return $this->addDeferred($provider, $dependencies);

        $this->providers[$provider] = true;

        $this->register(new $provider);

        return $this;
    }

    /**
     * Deferred registry
     * @param $provider
     * @param array $dependencies
     * @return $this
     */
    public function addDeferred($provider, array $dependencies)
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

        return $this;
    }

    /**
     * @param array $providers
     * @return $this
     */
    public function batchAdd(array $providers)
    {
        foreach($providers as $provider)
            $this->add($provider);

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->providers[$name]) || in_array($name, $this->providersDeferred);
    }

    /**
     * Register the given provider.
     * @param Provider $provider
     * @return $this
     */
    public function register(Provider $provider)
    {
        $provider->register($this->app);

        return $this;
    }

    /**
     * Batch register
     * @param Provider[] $providers
     * @return $this
     */
    public function batchRegister(array $providers)
    {
        foreach($providers as $provider)
            $this->register($provider);

        return $this;
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
     * @param string $name service|factory|callable
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