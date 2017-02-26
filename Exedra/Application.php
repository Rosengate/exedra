<?php namespace Exedra;

use Exedra\Container\Container;
use Exedra\Http\ServerRequest;
use Exedra\Provider\Registry as ProviderRegistry;
use Exedra\Routing\ExecuteHandlers\ClosureHandler;
use Exedra\Routing\Factory;
use Exedra\Routing\Group;
use Exedra\Support\Provider\Framework;
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
            'config' => \Exedra\Config::class,
            'routingFactory' => function(){ return new Factory();},
            'map' => function() { return $this['routingFactory']->createGroup();},
            'request' => function(){ return \Exedra\Http\ServerRequest::createFromGlobals();},
            'url' => function() { return $this->create('url.factory', array($this->map, $this->request, $this->config->get('app.url', null), $this->config->get('asset.url', null)));},
        ));

        $this->services['factory']->register(array(
            'runtime.exe' => \Exedra\Runtime\Exe::class,
            'runtime.response' => function(){ return \Exedra\Runtime\Response::createEmptyResponse();},
            'url.factory' => \Exedra\Url\UrlFactory::class
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
     * @return \Exedra\Http\ServerRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Execute application with route name
     * @param string|\Exedra\Http\ServerRequest $routeName
     * @param array $parameters
     * @param \Exedra\Http\ServerRequest $request
     * @return \Exedra\Runtime\Exe
     *
     * @throws Exception\InvalidArgumentException
     */
    public function execute($routeName, array $parameters = array(), \Exedra\Http\ServerRequest $request = null)
    {
        // expect it as route name
        if(!is_string($routeName))
            throw new Exception\InvalidArgumentException('Argument 1 must be [string]');

        return $this->exec($this->map->findByName($routeName, $parameters, $request));
    }

    /**
     * Execute using http request
     * @param \Exedra\Http\ServerRequest|null $request
     * @return \Exedra\Runtime\Exe
     */
    public function request(\Exedra\Http\ServerRequest $request = null)
    {
        return $this->exec($this->map->findByRequest($request ? : $this->request));
    }

    /**
     * Execute the finding
     * @param \Exedra\Routing\Finding $finding
     * @return \Exedra\Runtime\Exe
     *
     * @throws \Exedra\Exception\RouteNotFoundException
     */
    public function exec(\Exedra\Routing\Finding $finding)
    {
        if(!$finding->isSuccess())
            throw new \Exedra\Exception\RouteNotFoundException('Route does not exist');

        return $this->create('runtime.exe', array($this, $finding, $this->create('runtime.response')))->run();
    }

    /**
     * Dispatch and return the response
     * Handle uncaught \Exedra\Exception\Exception
     * @param \Exedra\Http\ServerRequest|null $request
     * @return \Exedra\Runtime\Response
     */
    public function respond(\Exedra\Http\ServerRequest $request)
    {
        $failRoute = $this->failRoute;

        try
        {
            $response = $this->request($request)
                            ->finalize()
                            ->getResponse();
        }
        catch(\Exception $e)
        {
            if($failRoute)
            {
                $response = $this->execute($failRoute, array('exception' => $e, 'request' => $request), $request)
                                ->finalize()
                                ->getResponse();

                $failRoute = null;
            }
            else if($this->map->hasFailRoute())
            {
                $response = $this->execute($this->map->getFailRoute(), array('exception' => $e, 'request' => $request), $request)
                                ->finalize()
                                ->getResponse();
            }
            else
            {
                $response = \Exedra\Runtime\Response::createEmptyResponse()
                ->setStatus(404)
                ->setBody($e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Dispatch and send the response
     * Clear any flash
     * @param \Exedra\Http\ServerRequest|null $request
     */
    public function dispatch(\Exedra\Http\ServerRequest $request = null)
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
