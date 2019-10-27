<?php

namespace Exedra\Support\Wireman;

use Exedra\Application;
use Exedra\Http\Response;
use Exedra\Routing\Call;
use Exedra\Routing\Finding;
use Exedra\Support\Wireman\Resolvers\ContainerResolver;
use Exedra\Support\Wireman\Resolvers\InstantiationResolver;

class Context extends \Exedra\Runtime\Context
{
    protected $wireman;

    public function __construct(Application $app, Finding $finding, Response $response)
    {
        parent::__construct($app, $finding, $response);

        $this->set(Context::class, function() {return $this;});

        $this->wireman = new Wireman([
            $contextResolver = new ContainerResolver($this),
            $appResovler = new ContainerResolver($this->app),
            $class = new InstantiationResolver()], [
                $contextResolver,
                $appResovler,
                $class
            ]
        );
    }

    public function call(Call $call, array $args)
    {
        $args = $this->wireman->resolveCallable($call->getCallable());

        return parent::call($call, $args);
    }
}