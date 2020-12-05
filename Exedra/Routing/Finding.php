<?php

namespace Exedra\Routing;

use Exedra\Contracts\Routing\ExecuteHandler;
use Exedra\Exception\Exception;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Routing\ExecuteHandlers\DynamicHandler;
use Psr\Http\Message\ServerRequestInterface;

class Finding
{
    /**
     * Matched route
     * @var Route $route
     */
    public $route;

    /**
     * Finding attributes
     * @deprecated
     * @var array $attributes
     */
    protected $attributes = array();

    /**
     * Finding states
     * @var array $states
     */
    protected $states = array();

    /**
     * Route parameters
     * @var array $parameters
     */
    protected $parameters = array();

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

    /**
     * @var CallStack|null $callStack
     */
    protected $callStack;

    /**
     * @param \Exedra\Routing\Route|null
     * @param array $parameters
     * @param mixed $request
     */
    public function __construct(Route $route, array $parameters = array(), ServerRequestInterface $request = null)
    {
        $this->route = $route;

        $this->request = $request;

        if ($route) {
            foreach ($parameters as $key => $param)
                $this->parameters[$key] = $param;

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
     * Get named parameter value
     * @param string $name
     * @param null|mixed $default
     * @return mixed|null
     */
    public function param($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * Get all named parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param $middleware
     * @return \Closure
     * @throws InvalidArgumentException
     */
    protected function resolveMiddleware($middleware)
    {
        if (!is_string($middleware) && !is_object($middleware) && !is_array($middleware))
            throw new InvalidArgumentException('Unable to resolve middleware with type [' . gettype($middleware) . ']');

        $method = 'handle';

        // expect a class name
        if (is_string($middleware)) {
            @list($middleware, $method) = explode('@', $middleware);

            $middleware = new $middleware;

            $method = $method ?: 'handle';
        }

        if (is_array($middleware)) {
            return function () use ($middleware) {
                return call_user_func_array($middleware, func_get_args());
            };
        }

        if (is_callable($middleware))
            return $middleware;

        if (method_exists($middleware, $method))
            return function () use ($middleware, $method) {
                return call_user_func_array(array($middleware, $method), func_get_args());
            };

        throw new InvalidArgumentException('Middleware [' . get_class($middleware) . '] has to be callable or implements method handle()');
    }

    /**
     * Resolve finding informations and returns a CallStack
     * resolve middlewares, config, state
     * @return CallStack|null
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function resolve()
    {
        $callStack = new CallStack;

        $executePattern = $this->route->getProperty('execute');

        /** @var ExecuteHandler[] $handlers */
        $handlers = array();

        foreach ($this->route->getFullRoutes() as $route) {
            $group = $route->getGroup();

            foreach ($group->factory->getExecuteHandlers() as $handler)
                $handlers[get_class($handler)] = $handler;

            // stack all the handlers
            foreach ($group->getExecuteHandlers() as $name => $handler)
                $handlers[$name] = $handler;

            foreach ($group->getMiddlewares() as $middleware)
                $callStack->addCallable($this->resolveMiddleware($middleware[0]), $middleware[1]);

            // append all route middlewares
            foreach ($route->getProperty('middleware') as $middleware)
                $callStack->addCallable($this->resolveMiddleware($middleware[0]), $middleware[1]);

            foreach ($route->getAttributes() as $key => $value) {
                if (is_array($value)) {
                    if (isset($this->states[$key]) && !is_array($this->states[$key]))
                        throw new Exception('Unable to push value into attribute [' . $key . '] on route ' . $route->getAbsoluteName() . '. The attribute type is not an array.');

                    foreach ($value as $val) {
                        $this->states[$key][] = $val;
                    }
                } else {
                    $this->states[$key] = $value;
                }
            }

            // pass config.
            if ($config = $route->getProperty('config'))
                $this->config = array_merge($this->config, $config);
        }

        foreach ($handlers as $name => $class) {
            $handler = null;

            if (is_string($class)) {
                $handler = new $class;
            } else if (is_object($class)) {
                if ($class instanceof \Closure) {
                    $class($handler = new DynamicHandler());
                } else if ($class instanceof ExecuteHandler) {
                    $handler = $class;
                }
            }

            if (!$handler || !is_object($handler) || !($handler instanceof ExecuteHandler))
                throw new InvalidArgumentException('Handler must be either class name, ' . ExecuteHandler::class . ' or \Closure ');

            if ($handler->validateHandle($executePattern)) {
                $resolve = $handler->resolveHandle($executePattern);

                if (!is_callable($resolve))
                    throw new \Exedra\Exception\InvalidArgumentException('The resolveHandle() method for handler [' . get_class($handler) . '] must return \Closure or callable');

                $properties = array();

                if ($this->route->hasDependencies())
                    $properties['dependencies'] = $this->route->getProperty('dependencies');

                if (!$resolve)
                    throw new InvalidArgumentException('The route [' . $this->route->getAbsoluteName() . '] execute handle was not properly resolved. ' . (is_string($executePattern) ? ' [' . $executePattern . ']' : ''));

                $callStack->addCallable($resolve, $properties);

                return $callStack;
            }
        }

        return null;
    }

    /**
     * Get callables stack
     * @return CallStack|null
     */
    public function getCallStack()
    {
        return $this->callStack;
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
     * @deprecated
     * @param string $key
     * @param mixed $default value
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return array_key_exists($key, $this->states) ? $this->states[$key] : $default;
    }

    /**
     * Get route found state
     * @param string $key
     * @param mixed $default value
     * @return mixed
     */
    public function getState($key, $default = null)
    {
        return array_key_exists($key, $this->states) ? $this->states[$key] : $default;
    }

    /**
     * Get all attributes
     * @deprecated
     * @return array
     */
    public function getAllAttributes()
    {
        return $this->states;
    }

    /**
     * Get all states
     * @return array
     */
    public function getAllStates()
    {
        return $this->states;
    }

    /**
     * Check whether attribute exists
     * @deprecated
     * @param string $key
     * @return boolean
     */
    public function hasAttribute($key)
    {
        return array_key_exists($key, $this->states);
    }

    /**
     * Check whether attribute exists
     * @param string $key
     * @return boolean
     */
    public function hasState($key)
    {
        return array_key_exists($key, $this->states);
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