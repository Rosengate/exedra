<?php

namespace Exedra\Support\Wireman;

use Exedra\Application;
use Exedra\Contracts\Provider\Provider;
use Exedra\Runtime\Context;
use Exedra\Support\Wireman\Resolvers\ContainerResolver;
use Exedra\Support\Wireman\Resolvers\InstantiationResolver;

class WiremanProvider implements Provider
{
    /**
     * @var bool
     */
    protected $guarded;

    public function __construct($guarded = false)
    {
        $this->guarded = $guarded;
    }

    public function register(Application $app)
    {
        $guarded = $this->guarded;

        $app['factory']->intercept('runtime.context', function(Context $context) use ($guarded) {
            $context->set(Context::class, function() {return $this;});

            $context->set('callHandler', function() use ($context, $guarded) {
                return new CallHandler($context, new Wireman([
                    $contextResolver = new ContainerResolver($context),
                    $appResolver = new ContainerResolver($context->app),
                    $class = new InstantiationResolver()], [
                        $contextResolver,
                        $appResolver,
                        $class
                    ]
                ), $guarded);
            });

            return $context;
        });
    }
}