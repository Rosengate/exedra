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
     * Finding flags
     * @var mixed[]
     */
    protected $flags = array();

    /**
     * Finding serieses
     * @var array
     */
    protected $serieses = array();

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

    protected function createMiddleware($class)
    {
        return new $class;
    }

    /**
     * @param $middleware
     * @return callable
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

            $middleware = $this->createMiddleware($middleware);

            $method = $method ? : 'handle';
        }

        if (is_array($middleware)) {
            return function () use ($middleware) {
                return call_user_func_array($middleware, func_get_args());
            };
        }

        if (is_callable($middleware))
            return $middleware;

        if (method_exists($middleware, $method))
            return [$middleware, $method];

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
//        $callStack = new CallStack;

        $executePattern = $this->route->getProperty('execute');

        /** @var ExecuteHandler[] $handlers */
        $handlers = array();

        $middlewares = array();

        $decorators = array();

        foreach ($this->route->getFullRoutes() as $route) {
            $group = $route->getGroup();

            foreach ($group->factory->getExecuteHandlers() as $handler)
                $handlers[get_class($handler)] = $handler;

            // stack all the handlers
            foreach ($group->getExecuteHandlers() as $name => $handler)
                $handlers[$name] = $handler;

            foreach ($group->getMiddlewares() as $middleware)
                $middlewares[] = new Call($this->resolveMiddleware($middleware[0]), $middleware[1]);
//                $callStack->addCallable($this->resolveMiddleware($middleware[0]), $middleware[1]);

            // append all route middlewares
//            foreach ($route->getProperty('middleware') as $middleware)
//                $middlewares[] = new Call($this->resolveMiddleware($middleware[0]), $middleware[1]);
//                $callStack->addCallable($this->resolveMiddleware($middleware[0]), $middleware[1]);

            foreach ($route->getStates() as $key => $value)
                $this->states[$key] = $value;

            foreach ($route->getFlags() as $flag)
                $this->flags[] = $flag;

            foreach ($route->getSerieses() as $key => $value) {
                if (!array_key_exists($key, $this->serieses))
                    $this->serieses[$key] = array();

                foreach ($value as $v)
                    $this->serieses[$key][] = $v;
            }

            foreach ($group->getDecorators() as $decorator)
                $decorators[] = new Call($this->resolveMiddleware($decorator));

//            foreach ($route->getDecorators() as $decorator)
//                $decorators[] = new Call($this->resolveMiddleware($decorator));

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

//                $callStack->addCallable($resolve, $properties);

                return $this->createCallStack($middlewares, $decorators, new Call($resolve, $properties));

//                return $callStack;
            }
        }

        return null;
    }

    /**
     * @param Call[] $middlewares
     * @param Call[] $decorators
     * @param Call $resolver
     * @return CallStack
     */
    protected function createCallStack(array $middlewares, array $decorators, Call $resolver)
    {
        $callStack = new CallStack();

        foreach (array_merge($middlewares, $decorators, [$resolver]) as $call)
            $callStack->addCall($call);

        return $callStack;
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
     * @deprecated use getStates instead
     * @return array
     */
    public function getAllStates()
    {
        return $this->states;
    }

    /**
     * Get all states
     * @return array
     */
    public function getStates()
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
     * Get all the collected flags
     * @return mixed[]
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param $flag
     * @return bool
     */
    public function hasFlag($flag)
    {
        return in_array($flag, $this->flags);
    }

    /**
     * @param $key
     * @param null $default
     * @return array|null
     */
    public function getSeries($key, $default = array())
    {
        return isset($this->serieses[$key]) ? $this->serieses[$key] : $default;
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasSeries($key)
    {
        return array_key_exists($key, $this->serieses);
    }

    /**
     * @return array
     */
    public function getSerieses()
    {
        return $this->serieses;
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
