<?php

namespace Exedra\Support\Provider;

use Exedra\Application;
use Exedra\Exception\Exception;
use Exedra\Exception\NotFoundException;
use Exedra\Runtime\Context;

/**
 * Class Modular
 * @package Exedra\Support\Provider
 * @deprecated
 */
class Modular implements \Exedra\Provider\ProviderInterface
{
    public function register(Application $app)
    {
        if (!$app->provider->has(Framework::class))
            throw new NotFoundException('This provider requires [' . Framework::class . '] in order to work.');

        if (!$app->config->has('namespace'))
            throw new Exception('config.namespace is required');


        $app->path->register('modules', $app->path['app']->create('modules'));

        $app->map->middleware(function (Context $exe) {
            if ($exe->hasAttribute('module')) {
                $module = $exe->attr('module');

                $pathModule = $exe->path['modules']->create(strtolower($module));

                $exe->view = $exe->create('view.factory', array($pathModule->create('views')));

                $namespace = $exe->app->config->get('namespace') . '\\' . ucfirst($module);

                $exe->controller = $exe->create('controller.factory', array($namespace));

                // autoload the controller path.
                $pathModule->autoloadPsr4($namespace . '\\Controller', 'controllers');
            }

            return $exe->next($exe);
        });
    }
}