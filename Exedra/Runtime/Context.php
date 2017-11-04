<?php
namespace Exedra\Runtime;

use Exedra\Config;
use Exedra\Container\Container;
use Exedra\Http\ServerRequest;
use Exedra\Path;
use Exedra\Routing\Finding;
use Exedra\Routing\Route;
use Exedra\Support\Asset\AssetFactory;
use Exedra\Support\DotArray;
use Exedra\Support\Runtime\Form\Form;
use Exedra\Url\UrlFactory;
use Psr\Http\Message\ResponseInterface;

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
 */
class Context extends Container
{
    /**
     * Array of (referenced) parameters for this execution.
     * @var array
     */
    protected $params = array();

    /**
     * Base route to be appended on every execution scope based functionality.
     * @var string
     */
    protected $baseRoute = null;

    /**
     * stack of callables
     * @var array callStack
     */
    protected $callStack;

    protected $attributes = array();

    protected $currentModule;

    /**
     * Route for handling exception
     * @var string
     */
    protected $failRoute = null;

    public function __construct(
        \Exedra\Application $app,
        \Exedra\Routing\Finding $finding,
        \Exedra\Http\Response $response)
    {
        parent::__construct();

        $this->app = $app;

        $this->services['finding'] = $finding;

        $this->services['response'] = $response;

        // initiate services
        $this->initializeServices();

        // initiate service registry
        $this->setUp();
    }

    /**
     * Initialize execution services
     */
    protected function initializeServices()
    {
        // Initiate registry, route, params, and set base route based on finding.
        $this->services['route'] = $this->finding->getRoute();

        $this->setBaseRoute($this->finding->getBaseRoute());

        DotArray::initialize($this->params, $this->finding->param());

        $this->services['request'] = $this->finding->getRequest();
    }

    /**
     * Setup dependency registry
     */
    protected function setUp()
    {
        $this->services['service']->register(array(
            'path' => function(Context $context){ return $context->app->path; },
            'config' => function(Context $context) { return clone $context->app->config; },
            'url' => function(Context $context){
                $urlFactory = $context->app->url;

                $baseUrl = $urlFactory->getBaseUrl();

                return $context->app->create('url.factory', array($context->route->getGroup(), $context->request ? : null, $baseUrl, $urlFactory->getFilters(), $urlFactory->getCallables()));
            },
            'redirect' => array(Redirect::class, array('self.response', 'self.url')),
            'form' => array(Form::class, array('self')),
            // thinking of deprecating the asset as service
            'asset' => function(Context $context){ return new AssetFactory($context->url, $context->app->path['public'], $context->config->get('asset', array()));}
        ));

        $this->services['factory']->add('factory.url', UrlFactory::class);

        $this->setUpConfig();
    }

    protected function setUpConfig()
    {
        $finding = $this->finding;

        $this->services['service']->on('config', function(Config $config) use($finding)
        {
            $config->set($this->finding->getConfig());
        });
    }

    /**
     * Get execution namespace
     * @return string
     */
    public function getNamespace($namespace = null)
    {
        return $this->app->getNamespace() . ($namespace ? '\\'.$namespace : '');
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
     * Get current module based on finding
     * @return string
     */
    public function getModule()
    {
        if($this->currentModule)
            return $this->currentModule;

        return $this->module[$this->finding->getModule() ? : 'Application'];
    }

    /**
     * Get response instance
     * @return \Exedra\Runtime\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get base dir for this execution instance. A concenated app base directory
     * @return string.
     */
    public function getBaseDir()
    {
        return rtrim($this->app->getBaseDir(), '/');
    }

    /**
     * Set route for handling exception
     * @var string route
     */
    public function setFailRoute($route)
    {
        $this->failRoute = $route;
    }

    /**
     * Get route for handling exception
     * @return string
     */
    public function getFailRoute()
    {
        return $this->failRoute;
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
     */
    public function next()
    {
        $callStack = $this->finding->getCallStack();

        $callable = $callStack->getNextCallable();

        if($callable->hasDependencies())
        {
            $args = array();

            foreach($callable->getDependencies() as $injectable)
                $args[] = $this->tokenResolve($injectable);

            $context = $this;

            // $next callable
            $next = function() use($context, &$next)
            {
                $args = func_get_args();

                $args[] = $next;

                return call_user_func_array(array($context, 'next'), $args);
            };

            $args[] = $next;

            return call_user_func_array($callable, $args);
        }

        return call_user_func_array($callable, func_get_args());
    }

    public function getNextCall()
    {
        return $this->finding->getCallStack()->getNextCallable();
    }

    /**
     * Validate against given parameters
     * @param array $params
     * @return boolean
     */
    public function isParams(array $params)
    {
        foreach($params as $key => $value)
            if($this->param($key) != $value)
                return false;

        return true;
    }

    /**
     * Check if the given route exists within the current route.
     * @param string $route
     * @param array $params
     * @return boolean
     */
    public function hasRoute($route, array $params = array())
    {
        if(strpos($route, '@') === 0)
            $isRoute = strpos($this->getAbsoluteRoute(), substr($route, 1)) === 0;
        else
            $isRoute = strpos($this->getRoute(), $route) === 0;

        if(!$isRoute)
            return false;

        if(count($params) === 0)
            return true;

        return $this->isParams($params);
    }

    /**
     * Check if the given route is equal
     * @param string $route
     * @param array $params
     * @return boolean
     */
    public function isRoute($route, array $params = array())
    {
        if(strpos($route, '@') === 0)
            $isRoute = $this->getAbsoluteRoute() == substr($route, 1);
        else
            $isRoute = $this->getRoute() == $route;

        if(!$isRoute)
            return false;

        if(count($params) === 0)
            return true;

        return $this->isParams($params);
    }

    /**
     * Get execution parameter
     * @param string $name
     * @param mixed $default
     * @return mixed or default if not found.
     */
    public function param($name, $default = null)
    {
        return DotArray::has($this->params, $name) ? DotArray::get($this->params, $name) : $default;
    }

    /**
     * Check whether given attribute exists
     * @param string $key
     * @return boolean
     */
    public function hasAttribute($key)
    {
        return $this->finding->hasAttribute($key);
    }

    /**
     * Alias to hasAttribute(key)
     * @param string $key
     * @return boolean
     */
    public function hasAttr($key)
    {
        return $this->finding->hasAttribute($key);
    }

    /**
     * Set a context based attribute
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttr($key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Alias to getAttribute
     * @param string $key
     * @param string|null default value
     * @return mixed
     */
    public function attr($key, $default = null)
    {
        if (isset($this->attributes[$key]))
            return $this->attributes[$key];

        return $this->finding->getAttribute($key, $default);
    }

    /**
     * Get attribute
     * @param string $key
     * @param string|null default value
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return $this->finding->getAttribute($key, $default);
    }

    /**
     * Alias to get attribute
     * For backward compatibility
     * @param string $key
     */
    public function meta($key, $default = null)
    {
        return $this->finding->getAttribute($key, $default);
    }

    /**
     * Alias to hasAttr
     * For backward compatibility
     * Check whether given meta key exists
     * @param string $key
     * @return bool
     */
    public function hasMeta($key)
    {
        return $this->finding->hasAttribute($key);
    }

    /**
     * Check whether given param key exists
     * @param string $name
     * @return boolean
     */
    public function hasParam($name)
    {
        return DotArray::has($this->params, $name);
    }

    /**
     * Update the given param
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParam($key, $value = null)
    {
        DotArray::set($this->params, $key, $value);
    }

    /**
     * A public functionality to add parameter(s) to $context.
     * @param string $key
     * @param mixed $value
     * @return $this;
     */
    public function addParam($key, $value = null)
    {
        if(is_array($key))
        {
            foreach($key as $k=>$v)
                $this->setParam($k, $v);
        }
        else
        {
            if(isset($this->params[$key]))
                throw new \InvalidArgumentException('The given key ['.$key.'] has already exist');

            $this->params[$key] = $value;
        }

        return $this;
    }

    /**
     * @param array $params
     */
    public function addParams(array $params)
    {
        foreach($params as $key => $value)
            $this->setParam($key, $value);
    }

    /**
     * Get parameters by the given list of key
     * @param array $keys
     * @return array
     */
    public function params(array $keys = array())
    {
        if(count($keys) == 0)
            return $this->params;

        $params = array();

        foreach($keys as $key)
        {
            $key = trim($key);

            $params[$key] = isset($this->params[$key]) ? $this->params[$key] : null;
        }

        return $params;
    }

    /**
     * Route name relative to the current base route, return absolute route if true boolean is given as argument.
     * @param boolean $absolute, if true. will directly return absolute route. The same use of getAbsoluteRoute
     * @return string
     */
    public function getRouteName($absolute = false)
    {
        if($absolute !== true)
        {
            $baseRoute = $this->getBaseRoute();
            $absoluteRoute = $this->getAbsoluteRoute();

            if(!$baseRoute) return $absoluteRoute;

            $route	= substr($absoluteRoute, strlen($baseRoute)+1, strlen($absoluteRoute));

            return $route;
        }
        else
        {
            return $this->route->getAbsoluteName();
        }
    }

    /**
     * @return ServerRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Alias to getRouteName()
     * @param bool $absolute
     * @return string
     */
    public function getRoute($absolute = false)
    {
        return $this->getRouteName($absolute);
    }

    /**
     * get absolute route.
     * @return string current route absolute name.
     */
    public function getAbsoluteRoute()
    {
        return $this->route->getAbsoluteName();
    }

    /**
     * Get parent route. For example, route for public.main.index will return public.main.
     * Used on getBaseRoute()
     * @return string of parent route name.
     */
    public function getParentRoute()
    {
        return $this->route->getParentRouteName();
    }

    /**
     * Set a base route for this execution
     * @param string $route
     */
    public function setBaseRoute($route)
    {
        $this->baseRoute = $route;
    }

    /**
     * Get base route for this execution
     * @return string|null
     */
    public function getBaseRoute()
    {
        if($this->baseRoute)
            $baseRoute	= $this->baseRoute;
        else
            $baseRoute	= $this->getParentRoute();

        return $baseRoute ? $baseRoute : null;
    }

    /**
     * Base the given route.
     * Or return an absolute route, if absolute character was given at the beginning of the given string.
     * @param string $route
     * @return string
     */
    public function baseRoute($route)
    {
        if(strpos($route, '@') === 0)
        {
            $route = substr($route, 1, strlen($route)-1);
        }
        else
        {
            $baseRoute = $this->getBaseRoute();
            $route		= $baseRoute ? $baseRoute.'.'.$route : $route;
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

        $request = $request ? : $this->request;

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

        while(true)
        {
            $body = $context->response->getBody();

            if($body instanceof \Exedra\Runtime\Context)
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
        if(!$this->services[$type]->has($name))
        {
            if($this->app[$type]->has('@'.$name))
            {
                return $this->app->dependencyCall($type, $name, $args);
            }
            else
            {
                $isShared = false;

                if($type == 'callable' && ($this->app['service']->has($name) || $isShared = $this->app['service']->has('@'.$name)))
                {
                    $service = $this->app->get($isShared ? '@'.$name : $name);

                    $this->invokables[$name] = $service;

                    if(is_callable($service))
                        return call_user_func_array($service, $args);
                }

                throw new \Exedra\Exception\InvalidArgumentException('['.get_class($this).'] Unable to find ['.$name.'] in the registered '.$type);
            }
        }
        else
        {
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
        if(strpos($name, 'context') === 0)
            $name = str_replace('context', 'self', $name);

        if(strpos($name, 'app.') === 0)
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