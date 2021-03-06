<?php
namespace Exedra\Routeller;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;
use Exedra\Exception\Exception;
use Exedra\Routeller\Cache\CacheInterface;
use Exedra\Routeller\Cache\EmptyCache;

class RoutellerRootProvider implements Provider
{
    protected $controller = null;

    protected $options;

    protected $cache;

    public function __construct($controller = null, CacheInterface $cache = null, array $options = array())
    {
        $this->controller = $controller;

        $this->cache = $cache ? $cache : new EmptyCache();

        $this->options = $options;
    }

    public function register(Application $app)
    {
        $handler = Handler::createAppHandler($app, $this->cache, $this->options);

        if (!$this->controller)
            throw new Exception('Instance of RoutellerRootProvider is required on provider registry');

        if (!$handler->validateGroup($this->controller))
            throw new Exception('Invalid pattern');

        $app->routingFactory->addGroupHandler($handler);
        $app->routingFactory->addExecuteHandlers(new ExecuteHandler());

        $controller = $this->controller;

        $app->set('map', function() use ($app, $handler, $controller) {
            return $handler->resolveGroup($app->routingFactory, $controller);
        });
    }
}