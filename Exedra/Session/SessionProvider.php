<?php

namespace Exedra\Session;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;

class SessionProvider implements Provider
{
    public function register(Application $app)
    {
        $app['service']->register(array(
            '@session' => Session::class,
            '@flash' => array(Flash::class, array('self.session'))
        ));
    }
}