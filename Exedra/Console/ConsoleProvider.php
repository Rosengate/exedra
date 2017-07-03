<?php
namespace Exedra\Console;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;
use Exedra\Console\Commands\RouteListCommand;
use Exedra\Console\Commands\ServeCommand;

class ConsoleProvider implements Provider
{
    public function register(Application $app)
    {
        $app['service']->add('console', function() use($app) {
            $console = new \Symfony\Component\Console\Application();

            $console->add(new RouteListCommand($app->map));

            $console->add(new ServeCommand($app->path->hasRegistry('public') ? $app->path->get('public') : $app->path->create('public')));

            return $console;
        });
    }
}