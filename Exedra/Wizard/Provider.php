<?php
namespace Exedra\Wizard;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider as ProviderInterface;

class Provider implements ProviderInterface
{
    public function register(Application $app)
    {
        $app['service']->add('wizard', array(Manager::class, array('self')));

        $app['service']->on('wizard', function(Manager $wizard){
            $wizard->add(\Exedra\Wizard\Application::class);
        });

        $app['callable']->add('wizard', function(array $argv) {
            array_shift($argv);

            return $this->wizard->listen($argv);
        });

        // alias
        $app['callable']->add('console', function(array $argv) {
            return $this->wizard($argv);
        });
    }
}