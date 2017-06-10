<?php
namespace Exedra\Routing;

use Exedra\Exception\Exception;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Routing\ExecuteHandlers\DynamicHandler;
use Psr\Http\Message\ServerRequestInterface;

class Finding
{
    /**
     * Found route
     * @var Route $route
     */
    public $route;

    /**
     * Finding attributes
     * @var array $attributes
     */
    protected $attributes = array();

    /**
     * Route parameters
     * @var array $parameters
     */
    public $parameters = array();

    /**
     * string of module name.
     * @var string|null
     */
    protected $module = null;

    /**
     * string of route base
     * @var string|null $baseRoute
     */
    protected $baseRoute = null;

    /**
     * Request instance
     * @var \Psr\Http\Message\ServerRequestInterface|null $request
     */
    protected $request = null;

    /**
     * @var array config
     */
    protected $config = array();

    /**
     * Stacked handlers
     * @var array handlers
     */
    protected $handlers = array();

    protected $execute;

    /**
     * @var CallStack $callStack
     */
    protected $callStack;

    /**
     * @param \Exedra\Routing\Route|null
     * @param array $parameters
     * @param mixed $request
     */
    public function __construct(Route $route = null, array $parameters = array(), ServerRequestInterface $request = null)
    {
        $this->route = $route;

        $this->request = $request;

        if($route)
        {
            $this->addParameters($parameters);

            $this->callStack = $this->resolve();
        }
    }

    /**
     * Get route
     * @return Route|null
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Append given parameters
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        foreach($parameters as $key => $param)
            $this->parameters[$key] = $param;
    }

    /**
     * Get findings parameter
     * Return all if no argument passed
     * @param string|null $name
     * @return array|mixed
     */
    public function param($name = null)
    {
        if($name === null)
            return $this->parameters;

        return $this->parameters[$name];
    }

    /**
     * Whether finding is successful
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->route ? true : false;
    }

    /**
     * @param $middleware
     * @return \Closure
     * @throws InvalidArgumentException
     */
    protected function resolveMiddleware($middleware)
    {
        if(!is_string($middleware) && !is_object($middleware) && !is_array($middleware))
            throw new InvalidArgumentException('Unable to resolve middleware with type [' . gettype($middleware) .']');

        $method = 'handle';

        // expect a class name
        if(is_string($middleware))
        {
            list($middleware, $method) = explode('@', $middleware);

            $middleware = new $middleware;

            $method = $method ? : 'handle';
        }

        if(is_array($middleware))
        {
            return function() use($middleware) {
                return call_user_func_array($middleware, func_get_args());
            };
        }

        if(is_callable($middleware))
            return $middleware;

        if(method_exists($middleware, $method))
            return function() use($middleware, $method) {
                return call_user_func_array(array($middleware, $method), func_get_args());
            };

        throw new InvalidArgumentException('Middleware [' . get_class($middleware) .'] has to be callable or implements method handle()');
    }

    /**
     * Resolve finding informations and returns a CallStack
     * resolve baseRoute, middlewares, config, attributes
     * @return CallStack
     * @throws InvalidArgumentException
     */
    protected function resolve()
    {
        $this->baseRoute = null;

        $callStack = new CallStack;

        $executePattern = $this->route->getProperty('execute');

        foreach($this->route->getFullRoutes() as $route)
        {
            // get the latest module and route base
            if($route->hasProperty('module'))
                $this->module = $route->getProperty('module');

            // stack all the handlers
            foreach($route->getGroup()->getExecuteHandlers() as $name => $handler)
                $this->handlers[$name] = $handler;

            // if has parameter base, and it's true, set base route to the current route.
            if($route->hasProperty('base') && $route->getProperty('base') === true)
                $this->baseRoute = $route->getAbsoluteName();

            foreach($route->getGroup()->getMiddlewares() as $key => $middleware)
                $callStack->addCallable($this->resolveMiddleware($middleware[0]), $middleware[1]);

            // append all route middlewares
            foreach($route->getProperty('middleware') as $key => $middleware)
                $callStack->addCallable($this->resolveMiddleware($middleware[0]), $middleware[1]);

            foreach($route->getAttributes() as $key => $value)
                $this->attributes[$key] = $value;

            // pass conig.
            if($route->hasProperty('config'))
                $this->config = array_merge($this->config, $route->getProperty('config'));
        }

        foreach($this->handlers as $name => $class)
        {
            if(is_string($class))
            {
                $handler = new $class;
            }
            else if(is_object($class))
            {
                if($class instanceof \Closure)
                {
                    $class($handler = new DynamicHandler());
                }
            }
            else
            {
                throw new InvalidArgumentException('Handler must be either class name, or \Closure');
            }

            if($handler->validate($executePattern))
            {
                $resolve = $handler->resolve($executePattern);

                if(!is_callable($resolve))
                    throw new \Exedra\Exception\InvalidArgumentException('The resolve() method for handler ['.get_class($handler).'] must return \Closure or callable');

                $properties = array();

                if($this->route->hasDependencies())
                    $properties['dependencies'] = $this->route->getProperty('dependencies');

                if(!$resolve)
                {
                    $executePattern = $this->route->getProperty('execute');

                    if(!$executePattern)
                        throw new InvalidArgumentException('The route [' . $this->route->getAbsoluteName() . '] does not have execute handle.');

                    throw new InvalidArgumentException('The route [' . $this->route->getAbsoluteName() . '] execute handle was not properly resolved. '.(is_string($executePattern) ? ' ['.$executePattern.']' : ''));
                }

                $callStack->addCallable($resolve, $properties);

                $this->execute = $resolve;
            }
        }

        return $callStack;
    }

    /**
     * Get callables stack
     * @return CallStack
     */
    public function getCallStack()
    {
        return $this->callStack;
    }

    /**
     * Get stacked handlers
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * An array of config of the finding
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get route found attribute
     * @param string $key
     * @param mixed $default value
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    /**
     * Check whether attribute exists
     * @param string $key
     * @return boolean
     */
    public function hasAttribute($key)
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Module on this finding.
     * @return string referenced module name
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get base route configured for this Finding.
     * @return string
     */
    public function getBaseRoute()
    {
        return $this->baseRoute;
    }

    /**
     * Get Http request found along with the finding
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Whether this finding is dispatched with http request
     * @return bool
     */
    public function hasRequest()
    {
        return $this->request ? true : false;
    }
}