<?php
namespace Exedra\Form;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;

class FormProvider implements Provider
{
    public function register(Application $app)
    {
        $app['service']->add('@form', Form::class);
    }
}