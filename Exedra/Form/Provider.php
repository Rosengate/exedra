<?php
namespace Exedra\Form;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider as ProviderInterface;

class Provider implements ProviderInterface
{
    public function register(Application $app)
    {
        $app['service']->add('@form', Form::class);
    }
}