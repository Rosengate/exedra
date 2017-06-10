<?php namespace Exedra\Container;

class Container implements \ArrayAccess
{
    /**
     * Container resolved services
     * resolved services, and publicly accessible service
     * @var Registry[] services
     */
    protected $services = array();

    /**
     * Cached invokables
     * @var array invokables
     */
    protected $invokables = array();

    /**
     * List of mutables
     * @var array mutables
     */
    protected $mutables = array();

    public function __construct()
    {
        // default container registries
        $this->services = array(
            'service' => new \Exedra\Container\Registry,
            'callable' => new \Exedra\Container\Registry,
            'factory' => new \Exedra\Container\Registry,
        );
    }

    /**
     * registry exist check
     * @param string type
     * @return bool
     */
    public function offsetExists($type)
    {
        return isset($this->services[$type]);
    }

    /**
     * Set service
     * alias to __set
     * @param mixed $name
     * @param mixed $value
     * @throws \Exedra\Exception\Exception
     */
    public function offsetSet($name, $value)
    {
        if(array_key_exists($name, $this->services) && !isset($this->mutables[$name]))
            throw new \Exedra\Exception\Exception('['.get_class($this).'] Service ['.$name.'] is publically immutable and readonly once assigned.');

        $this->services[$name] = $value;

        $this->services['service']->set($name, true);
    }

    /**
     * Empty the service
     * @param string key
     */
    public function offsetUnset($key)
    {
        if(!in_array($key, array('service', 'callable', 'factory')))
            return;

        unset($this->services[$key]);
    }

    /**
     * Get container registry
     * @param string type
     * @return \Exedra\Container\Registry
     */
    public function registry($type)
    {
        return $this->services[$type];
    }

    /**
     * Set service.
     * @param string name
     * @param mixed service
     */
    public function __set($name, $value)
    {
        if(array_key_exists($name, $this->services) && !isset($this->mutables[$name]))
            throw new \Exedra\Exception\Exception('['.get_class($this).'] Service ['.$name.'] is publically immutable and readonly once assigned.');

        $this->services[$name] = $value;

        $this->services['service']->set($name, true);
    }

    /**
     * Register mutable services
     * @param array names
     */
    public function setMutables(array $services)
    {
        foreach($services as $service)
            $this->mutables[$service] = true;
    }

    /**
     * Get service
     * If has none, resolve
     * @param string name
     * @return mixed
     */
    public function get($name)
    {
        if(array_key_exists($name, $this->services))
            return $this->services[$name];

        return $this->services[$name] = $this->solve('service', $name);
    }

    /**
     * Get service
     * If has none, find in services registry.
     * Alias to get()
     * @param string name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->services['service']->has($name);
    }

    /**
     * Get service
     * If has none, find in services registry.
     * Alias to get()
     * @param string key
     * @return \Exedra\Container\Registry
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Invoke the registered callable
     * @param string name
     * @param array args
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __call($name, array $args = array())
    {
        // if there's a cached stored.
        if(isset($this->invokables[$name]))
            return call_user_func_array($this->invokables[$name], $args);

        if(method_exists($this, $name))
            return call_user_func_array(array($this, $name), $args);

        return $this->solve('callable', $name, $args);
    }

    /**
     * Invoke the registered factory
     * @param string name
     * @param array args
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function create($name, array $args = array())
    {
        return $this->solve('factory', $name, $args);
    }

    /**
     * All-capable dependency call.
     * @param string type
     * @param string name
     * @param array args
     * @return mixed
     */
    public function dependencyCall($type, $name, array $args = array())
    {
        switch($type)
        {
            case 'service':
                return $this->get($name);
            break;
            case 'callable':
                return $this->__call($name, $args);
            break;
            case 'factory':
                return $this->create($name, $args);
            break;
        }
    }

    /**
     * Solve the given type of registry
     * @param string type services|callables|factories
     * @param string name
     * @return mixed
     *
     * @throws \Exedra\Exception\InvalidArgumentException for failing to find in registry
     */
    protected function solve($type, $name, array $args = array())
    {
        if(!$this->services[$type]->has($name))
        {
            if($type == 'callable' && $this->services['service']->has($name))
            {
                // then check on related invokable services
                $service = $this->get($name);

                // if service is invokable.
                if(is_callable($service))
                {
                    $this->invokables[$name] = $service;

                    return call_user_func_array($service, $args);
                }
            }

            throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Unable to find ['.$name.'] registry as a registered '.$type .'.');
        }

        $registry = $this->services[$type]->get($name);

        return $this->filter($type, $name, $this->resolve($name, $registry, $args));
    }

    protected function filter($type, $name, $resolve)
    {
        foreach($this->services[$type]->getFilters($name) as $filter)
            $filter($resolve);

        return $resolve;
    }

    /**
     * Resolve services, factories, callables by given token
     * @param string $name
     * @return mixed
     */
    public function tokenResolve($name)
    {
        switch($name)
        {
            case 'self':
                return $this;
                break;
            default:
                $split = explode('.', $name, 2);

                if(isset($split[1]))
                {
                    switch($split[0])
                    {
                        case 'self':
                            return $this->{$split[1]};
                            break;
                        case 'service':
                            return $this->get($split[1]);
                            break;
                        case 'factory':
                            return $this->create($split[1]);
                            break;
                        case 'callable':
                            return $this->__call($split[1]);
                            break;
                        default:
                            return $this->{$arg};
                            break;
                    }
                }
                else
                {
                    return $this->get($name);
                }
            break;
        }
    }

    /**
     * Actual resolve the given type of registry
     * @param mixed registry
     * @return mixed
     */
    protected function resolve($name, $registry, array $args = array())
    {
        if($registry instanceof \Closure)
            return call_user_func_array($registry->bindTo($this), $args);

        if(is_string($registry))
            return $this->instantiate($registry, $args);

        // assume index 0 as class name
        if(is_array($registry))
        {
            $class = $registry[0];

            $arguments = array();

            // argument passed
            if(isset($registry[1]))
            {
                // the second element isn't an array
                if(!is_array($registry[1]))
                    throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Second value for array based ['.$name.'] registry must be an array');

                foreach($registry[1] as $arg)
                {
                    // if isn't string. allow only string.
                    if(!is_string($arg))
                        throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Argument for array based ['.$name.'] registry must be string');

                    if($arg = $this->tokenResolve($arg))
                        $arguments[] = $arg;
                }
            }

            // merge with the one passed
            $arguments = array_merge($arguments, $args);

            return $this->instantiate($class, $arguments);
        }

        throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Unable to resolve the ['.$name.'] registry');
    }

    protected function instantiate($class, array $arguments)
    {
        switch(count($arguments))
        {
            case 0:
                return new $class();
            break;
            case 1:
                return new $class($arguments[0]);
            break;
            case 2:
                return new $class($arguments[0], $arguments[1]);
            break;
            case 3:
                return new $class($arguments[0], $arguments[1], $arguments[2]);
            break;
            case 4:
                return new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
            break;
            case 5:
                return new $class($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
            break;
            default:
                $reflection = new \ReflectionClass($class);

                return $reflection->newInstanceArgs($arguments);
            break;
        }
    }
}