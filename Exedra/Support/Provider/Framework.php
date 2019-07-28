<?php

namespace Exedra\Support\Provider;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;
use Exedra\Exception\Exception;
use Exedra\Form\Form;
use Exedra\Routing\ExecuteHandlers\ClosureHandler;
use Exedra\Routing\GroupHandlers\PathHandler;
use Exedra\Support\Runtime\ControllerFactory;
use Exedra\Support\Runtime\Handler\Controller;
use Exedra\View\Factory as ViewFactory;

/**
 * Class Framework
 * @package Exedra\Support\Provider
 * @deprecated
 */
class Framework implements Provider
{
    public function register(Application $app)
    {
        if (!$app->config->has('namespace'))
            $app->config->set('namespace', 'App');

        $this->setUpPaths($app);

        $this->setUpServices($app);

        $this->setUpHandlers($app);

        $this->setUpAutoloading($app);
    }

    /**
     * Configure [app], [public], [src], [routes], [views], and [root] (recusive) paths
     * @param Application $app
     */
    protected function setUpPaths(Application $app)
    {
        $pathRoot = $app->path;

        $pathRoot->register('root', $pathRoot); // recursive reference.

        $pathRoot->register('public', isset($params['path.public']) ? $params['path.public'] : $pathRoot->to('public'), true);

        $pathApp = $pathRoot->register('app', isset($params['path.app']) ? $params['path.app'] : $pathRoot->to('app'), true);

        $pathRoot->register('src', isset($params['path.src']) ? $params['path.src'] : $pathApp->to('src'), true);

        $pathRoot->register('routes', isset($params['path.routes']) ? $params['path.routes'] : $pathApp->to('routes'), true);

        $pathRoot->register('views', isset($params['path.views']) ? $params['path.views'] : $pathApp->to('views'), true);
    }

    protected function setUpServices(Application $app)
    {
        $app['service']->register(array(
            '@session' => \Exedra\Session\Session::class,
            '@flash' => array(\Exedra\Session\Flash::class, array('self.session')),
            '@controller' => function () {
                if (!$this->config->has('namespace'))
                    throw new Exception('The [' . ControllerFactory::class . '] require config.namespace in order to work');

                return $this->create('controller.factory', array($this->config->get('namespace')));
            },
            '@view' => function () {
                return $this->create('view.factory', array($this->path['views']));
            },
            '@form' => function () {
                return new Form();
            }
        ));

        $app['factory']->register(array(
            '@controller.factory' => ControllerFactory::class,
            '@view.factory' => ViewFactory::class
        ));

        $app['callable']->register(array(
            'autoloadSrc' => function () {
                $this->path['src']->autoloadPsr4($this->config['namespace'], '');
            }
        ));
    }

    protected function setUpHandlers(Application $app)
    {
        $app->routingFactory->addGroupHandler(new PathHandler($app->path['routes']));

        $app->map->addExecuteHandler('controller', Controller::class);
    }

    protected function setUpAutoloading(Application $app)
    {
        $app->path['src']->autoloadPsr4($app->config['namespace'], '');
    }
}