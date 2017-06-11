<?php namespace Exedra;

use Exedra\Config;
use Exedra\Container\Container;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\RouteNotFoundException;
use Exedra\Http\ServerRequest;
use Exedra\Provider\Registry as ProviderRegistry;
use Exedra\Routing\ExecuteHandlers\ClosureHandler;
use Exedra\Routing\Factory;
use Exedra\Routing\Finding;
use Exedra\Routing\Group;
use Exedra\Runtime\Context;
use Exedra\Runtime\Response;
use Exedra\Url\UrlFactory;

/**
 * Class Application
 * @package Exedra
 *
 * @property ProviderRegistry $provider
 * @property Config $config
 * @property Factory $routingFactory
 * @property Group $map
 * @property Path $path
 * @property ServerRequest $request
 * @property UrlFactory $url
 */
class Application extends Container
{
    protected $failRoute = null;

    /**
     * Create a new application
     * @param string|array params [if string, expect root directory, else array of directories, and configuration]
     */
    public function __construct($rootDir)
    {
        parent::__construct();

        $this->services['provider'] = new ProviderRegistry($this);

        $this->services['path'] = new Path($rootDir);

        $this->setUp();
    }

    /**
     * Alias for above method.
     * @param string $routename
     */
    public function setFailRoute($routename)
    {
        $this->failRoute = $routename;
    }

    /**
     * Setup dependency registry.
     */
    protected function setUp()
    {
        $this->services['service']->register(array(
            'config' => Config::class,
            'routingFactory' => function(){ return new Factory();},
            'map' => function() { return $this['routingFactory']->createGroup();},
            'request' => function(){ return ServerRequest::createFromGlobals();},
            'url' => function() { return $this->create('url.factory', array($this->map, $this->request, $this->config->get('app.url', null), $this->config->get('asset.url', null)));},
        ));

        $this->services['factory']->register(array(
            'runtime.context' => Context::class,
            'runtime.response' => function(){ return Response::createEmptyResponse();},
            'url.factory' => UrlFactory::class
        ));

        $this->map->addExecuteHandler('closure', ClosureHandler::class);
    }

    /**
     * @return Path
     */
    public function getRootDir()
    {
        return $this->path;
    }

    /**
     * @return ServerRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Execute application given route name
     * @param string $routeName
     * @param array $parameters
     * @param ServerRequest $request
     * @return Context
     *
     * @throws InvalidArgumentException
     * @throws RouteNotFoundException
     */
    public function execute($routeName, array $parameters = array(), ServerRequest $request = null)
    {
        // expect it as route name
        if(!is_string($routeName))
            throw new Exception\InvalidArgumentException('Argument 1 must be [string]');

        $finding = $this->map->findByName($routeName, $parameters, $request);

        if(!$finding->isSuccess())
            throw new RouteNotFoundException('Route ['.$routeName.'] does not exist.');

        return $this->run($finding);
    }

    /**
     * Execute the http request
     * And return the context
     * @param ServerRequest|null $request
     * @return Runtime\Context
     * @throws RouteNotFoundException
     */
    public function request(ServerRequest $request = null)
    {
        $finding = $this->map->findByRequest($request = ($request ? : $this->request));

        if(!$finding->isSuccess())
            throw new RouteNotFoundException('Route for request '.$request->getMethod().' '.$request->getUri()->getPath().' does not exist');

        return $this->run($finding);
    }

    /**
     * Execute the call stack
     * @param Finding $finding
     * @return Context
     */
    public function run(Finding $finding)
    {
        $context = $this->create('runtime.context', array($this, $finding, $this->create('runtime.response')));

        // The first call
        $response = $context->next($context);

        if($response instanceof Response)
            $context['service']->response = $response;
        else
            $context->response->setBody($response);

        return $context;
    }

    /**
     * Dispatch and return the response
     * Handle uncaught \Exedra\Exception\Exception
     * @param ServerRequest|null $request
     * @return Response
     */
    public function respond(ServerRequest $request)
    {
        try
        {
            $response = $this->request($request)
                            ->finalize()
                            ->getResponse();
        }
        catch(\Exception $e)
        {
            if($failRoute = $this->failRoute)
            {
                $response = $this->execute($failRoute, array('exception' => $e, 'request' => $request), $request)
                                ->finalize()
                                ->getResponse();

                $this->failRoute = null;
            }
            else if($this->map->hasFailRoute())
            {
                $response = $this->execute($this->map->getFailRoute(), array('exception' => $e, 'request' => $request), $request)
                                ->finalize()
                                ->getResponse();
            }
            else
            {
                $message = '<pre><h2>['.get_class($e).'] '.$e->getMessage().'</h2>'.PHP_EOL;
                $message .= $e->getTraceAsString();

                $response = Response::createEmptyResponse()
                ->setStatus(404)
                ->setBody($message);
            }
        }

        return $response;
    }

    /**
     * Dispatch and send header
     * And print the response
     * @param ServerRequest|null $request
     */
    public function dispatch(ServerRequest $request = null)
    {
        $response = $this->respond($request ? : $this->request);

        $response->send();
    }

    /**
     * Extended container solve method
     * Search for shared symbol for sharable dependency
     * @param string $type
     * @param string $name
     * @param array $args
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function solve($type, $name, array $args = array())
    {
        // keep pinging the provider registry.
        $this->services['provider']->listen($type.'.'.$name);

        if(!$this->services[$type]->has($name))
        {
            if($this->services[$type]->has('@'.$name))
            {
                $registry = $this->services[$type]->get('@'.$name);
            }
            else
            {
                $isShared = false;

                if($type == 'callable' && ($this->services['service']->has($name) || $isShared = $this->services['service']->has('@'.$name)))
                {
                    $service = $this->get($isShared ? '@'.$name : $name);

                    $this->invokables[$name] = $service;

                    if(is_callable($service))
                        return call_user_func_array($service, $args);
                }

                throw new Exception\InvalidArgumentException('['.get_class($this).'] Unable to find ['.$name.'] in the registered '.$type);
            }
        }
        else
        {
            $registry = $this->services[$type]->get($name);
        }

        return $this->filter($type, $name, $this->resolve($name, $registry, $args));
    }
}
