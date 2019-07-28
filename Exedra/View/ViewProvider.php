<?php

namespace Exedra\View;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;
use Exedra\Exception\NotFoundException;

class ViewProvider implements Provider
{
    public function register(Application $app)
    {
        if (!$app->path->hasRegistry('views'))
            throw new NotFoundException('path.views is required.');

        $app['service']->add('@view', function () {
            return $this->create('view.factory', array($this->path['views']));
        });

        $app['factory']->add('@view.factory', ViewFactory::class);
    }
}