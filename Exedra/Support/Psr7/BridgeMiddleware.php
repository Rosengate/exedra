<?php
namespace Exedra\Support\Psr7;

use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\NotFoundException;
use Exedra\Http\Stream;
use Exedra\Runtime\Context;
use Psr\Http\Message\ResponseInterface;

class BridgeMiddleware
{
    /**
     * Psr7 middlewares
     * @var array|callable[]
     */
    private $middlewares;

    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __invoke()
    {
        $args = func_get_args();

        $context = null;

        foreach($args as $arg)
            if(is_object($arg) && $arg instanceof Context)
                $context = $arg;

        if(!$context)
            throw new InvalidArgumentException('The previous middleware must at least pass a [Exedra\Runtime\Context]');

        if(!$context->hasRequest())
            throw new NotFoundException('Request is not available in this context.');

        $middlewares = $this->middlewares;

        $middlewares[] = function($request, $response) use($context, $args)
        {
            $contents = call_user_func_array($context->getNextCall(), $args);

            return $context->getResponse()->setBody(Stream::createFromContents($contents));
        };

        reset($middlewares);

        $first = current($middlewares);

        // expect a ResponseInterface
        $response = $first($context->getRequest(), $context->getResponse(), next($middlewares));

        return $response;
    }
}