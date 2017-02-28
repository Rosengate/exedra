<?php
namespace Exedra\Session;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider as ProviderInterface;

class Provider implements ProviderInterface
{
    public function register(Application $app)
    {
        $app['service']->register(array(
            '@session' => Session::class,
            '@flash' => array(Flash::class, array('self.session'))
        ));
    }
}