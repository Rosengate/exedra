<?php

namespace Exedra\Runtime;

use Exedra\Application;
use Exedra\Config;
use Exedra\Container\Container;
use Exedra\Contracts\Runtime\CallHandler;
use Exedra\Exception\Exception;
use Exedra\Http\ServerRequest;
use Exedra\Path;
use Exedra\Routing\Call;
use Exedra\Routing\Finding;
use Exedra\Routing\Route;
use Exedra\Url\UrlFactory;

/**
 * Class Context
 * @package Exedra\Runtime
 *
 * @property ServerRequest $request
 * @property \Exedra\Http\Response $response
 * @property Route $route
 * @property Finding $finding
 * @property Path $path
 * @property Config $config
 * @property Redirect $redirect
 * @property UrlFactory $url
 * @property Application app
 * @property CallHandler callHandler
 */
class Context extends Container
{
    /**
     * Array of (referenced) parameters for this execution.
     * @var array
     */
    protected $params = array();

    /**
     * stack of callables
     * @var array callStack
     */
    protected $callStack;

    /**
     * @deprecated
     * @var array $attributes
     */
    protected $attributes = array();

    /**
     * @var array $states
     * @var array $states
     */
    protected $states = array();

    /**
     * Route for handling exception
     * @var string
     */
    protected $failRoute = null;

    public function __construct(
        Application $app,
        Finding $finding,
        \Exedra\Http\Response $response)
    {
        parent::__construct();

        $this->services['app'] = $app;

        $this->services['finding'] = $finding;

        $this->services['response'] = $response;

        // initiate services
        $this->initializeServices();

        // initiate service registry
        $this->setUp();

        $callStack = $finding->getCallStack();

        if (!$callStack)
            throw new Exception('The route [' . $finding->getRoute()->getAbsoluteName() . '] does not have execute handle.');

        $this->callStack = $callStack;
    }

    /**
     * Initialize execution services
     */
    protected function initializeServices()
    {
        // Initiate registry, route, params, and set base route based on finding.
        $this->services['route'] = $this->finding->getRoute();

        $this->params = $this->finding->getParameters();

        $this->services['request'] = $this->finding->getRequest();
    }

    /**
     * Setup dependency registry
     */
    protected function setUp()
    {
        $this->services['service']->register(array(
            'path' => function (Context $context) {
                return $context->app->path;
            },
            'config' => function (Context $context) {
                return clone $context->app->config;
            },
            'url' => function (Context $context) {
                $urlFactory = $context->app->url;

                $baseUrl = $urlFactory->getBaseUrl();

                return $context->app->create('url.factory', array($context->route->getGroup(), $context->request ?: null, $baseUrl, $urlFactory->getFilters(), $urlFactory->getCallables()));
            },
            'redirect' => array(Redirect::class, array('self.response', 'self.url')),
            'callHandler' => \Exedra\Runtime\CallHandler::class
        ));

        $this->setUpConfig();
    }

    protected function setUpConfig()
    {
        $finding = $this->finding;

        $this->services['service']->on('config', function (Config $config) use ($finding) {
            $config->set($this->finding->getConfig());

            return $config;
        });
    }

    /**
     * Override main and check the app instance
     *
     * @param string $name
     * @return bool
     */
    public function offsetExists($name)
    {
        return parent::offsetExists($name) || $this->app->offsetExists('@' . $name);
    }

    /**
     * Get application instance
     * @return \Exedra\Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Get response instance
     * @return \Exedra\Runtime\Response|\Exedra\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get execution finding
     * @return Finding
     */
    public function getFinding()
    {
        return $this->finding;
    }

    /**
     * Point and execute the next handler
     * If there's an injectable argument, inject them, and create another $next caller
     * @return mixed
     * @throws Exception
     */
    public function next()
    {
        $callStack = $this->finding->getCallStack();

        $callable = $callStack->getNextCallable();

        if ($callable->hasDependencies()) {
            $args = array();

            foreach ($callable->getDependencies() as $injectable)
                $args[] = $this->tokenResolve($injectable);

            $context = $this;

            // $next callable
            $next = function () use ($context, &$next) {
                $args = func_get_args();

                $args[] = $next;

                return call_user_func_array(array($context, 'next'), $args);
            };

            $args[] = $next;

            return call_user_func_array($callable, $args);
        }

        return $this->callHandler->handle($callable, func_get_args());
    }

    /**
     * Get execution parameter
     * @param string $name
     * @param mixed $default
     * @return mixed or default if not found.
     */
    public function param($name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * Alias to hasAttribute(key)
     * @param string $key
     * @return boolean
     */
    public function hasAttr($key)
    {
        if (isset($this->states[$key]))
            return true;

        return $this->finding->hasState($key);
    }

    /**
     * Alias to hasState(key)
     * @param string $key
     * @return boolean
     */
    public function hasState($key)
    {
        if (isset($this->states[$key]))
            return true;

        return $this->finding->hasState($key);
    }

    /**
     * Set a context based attribute
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttr($key, $value)
    {
        $this->states[$key] = $value;

        return $this;
    }

    /**
     * Set a context based state
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setState($key, $value)
    {
        $this->states[$key] = $value;

        return $this;
    }

    /**
     * Get attribute
     * @deprecated use state instead
     * @param string $key
     * @param string|null default value
     * @return mixed
     */
    public function attr($key = null, $default = null)
    {
        if ($key == null)
            return array_merge($this->finding->getAllStates(), $this->states);

        if (isset($this->states[$key]))
            return $this->states[$key];

        return $this->finding->getState($key, $default);
    }

    /**
     * Get state
     * Replacement for attribute
     * @param string $key
     * @param string|null default value
     * @return mixed
     */
    public function state($key = null, $default = null)
    {
        if ($key == null)
            return array_merge($this->finding->getAllStates(), $this->states);

        if (isset($this->states[$key]))
            return $this->states[$key];

        return $this->finding->getState($key, $default);
    }

    /**
     * @return mixed[]
     */
    public function flags()
    {
        return $this->finding->getFlags();
    }

    /**
     * @param $flag
     * @return bool
     */
    public function hasFlag($flag)
    {
        return $this->finding->hasFlag($flag);
    }

    /**
     * Check whether given param key exists
     * @param string $name
     * @return boolean
     */
    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Update the given param
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParam($key, $value = null)
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Merge params with given
     * prioritize the existing so that it don't get replaced.
     * @param array $params
     * @return $this
     */
    public function addParams(array $params)
    {
        $this->params = array_merge($params, $this->params);

        return $this;
    }

    /**
     * Get params by the given list of keys
     * @param array $keys
     * @param bool $onlyExistingParams
     * @return array
     */
    public function params(array $keys = array(), $onlyExistingParams = false)
    {
        if (count($keys) == 0)
            return $this->params;

        $params = array();

        foreach ($keys as $key) {
            $key = trim($key);

            if ($onlyExistingParams && isset($this->params[$key]))
                $params[$key] = $this->params[$key];
            else
                $params[$key] = isset($this->params[$key]) ? $this->params[$key] : null;
        }

        return $params;
    }

    /**
     * @return ServerRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Base the given route.
     * Or return an absolute route, if absolute character was given at the beginning of the given string.
     * @param string $route
     * @return string
     */
    public function baseRoute($route)
    {
        if (strpos($route, '@') === 0) {
            $route = substr($route, 1, strlen($route) - 1);
        } else {
            $baseRoute = $this->route->getParentRouteName();
            $route = $baseRoute ? $baseRoute . '.' . $route : $route;
        }

        return $route;
    }

    /**
     * Forward current request to the given route
     * @param string $route
     * @param array $args
     * @return Context
     */
    public function forward($route, array $args = array())
    {
        $route = $this->baseRoute($route);

        return $this->app->execute($route, $args, $this->request);
    }

    /**
     * Execute a scope based route
     * @param string $route
     * @param array $parameters
     * @param \Exedra\Http\ServerRequest|null $request
     * @return Context
     */
    public function execute($route, array $parameters = array(), \Exedra\Http\ServerRequest $request = null)
    {
        $route = $this->baseRoute($route);

        $request = $request ?: $this->request;

        return $this->app->execute($route, $parameters, $request);
    }

    /**
     * Alias to app->request()
     * @param \Exedra\Http\ServerRequest request
     * @return \Exedra\Runtime\Context
     */
    public function request(\Exedra\Http\ServerRequest $request)
    {
        return $this->app->request($request);
    }

    /**
     * Retrieve the actual execution instance
     * @return \Exedra\Runtime\Context
     */
    public function finalize()
    {
        $context = $this;

        while (true) {
            $body = $context->response->getBody();

            if ($body instanceof \Exedra\Runtime\Context)
                $context = $body;
            else
                break;
        }

        return $context;
    }

    /**
     * Extended container::solve method
     * for shared service/factory/callable check
     * @param string $type
     * @param string $name
     * @param array $args
     * @return mixed
     *
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    protected function solve($type, $name, array $args = array())
    {
        if (!$this->services[$type]->has($name)) {
            if ($this->app[$type]->has('@' . $name)) {
                return $this->app->dependencyCall($type, $name, $args);
            } else {
                $isShared = false;

                if ($type == 'callable' && ($this->app['service']->has($name) || $isShared = $this->app['service']->has('@' . $name))) {
                    $service = $this->app->get($isShared ? '@' . $name : $name);

                    $this->invokables[$name] = $service;

                    if (is_callable($service))
                        return call_user_func_array($service, $args);
                }

                throw new \Exedra\Exception\InvalidArgumentException('[' . get_class($this) . '] Unable to find [' . $name . '] in the registered ' . $type);
            }
        } else {
            $registry = $this->services[$type]->get($name);
        }

        return $this->filter($type, $name, $this->resolve($type, $name, $registry, $args));
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function tokenResolve($name)
    {
        if (strpos($name, 'context') === 0)
            $name = str_replace('context', 'self', $name);

        if (strpos($name, 'app.') === 0)
            return $this->app->tokenResolve(str_replace('app.', 'self.', $name));

        return parent::tokenResolve($name);
    }

    /**
     * @return bool
     */
    public function hasRequest()
    {
        return $this->request ? true : false;
    }
}