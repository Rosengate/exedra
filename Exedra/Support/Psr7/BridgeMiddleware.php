<?php
namespace Exedra\Support\Psr7;

use Exedra\Exception\InvalidArgumentException;
use Exedra\Exception\NotFoundException;
use Exedra\Http\ServerRequest;
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

        $context->setMutables(array(
            'request', 'response'
        ));

        $middlewares = $this->middlewares;

        // the last middleware
        $middlewares[] = function(ServerRequest $request, $response) use($context, $args)
        {
            $context->response = $response;

            // mutate the request object
            $context->request = $request;

            $contents = call_user_func_array($context->finding->getCallStack()->getNextCallable(), $args);

            if(is_object($contents))
            {
                if($contents instanceof ResponseInterface)
                    return $contents;

                if($contents instanceof Context)
                    return $context->response;
            }

            return $context->getResponse()->setBody(Stream::createFromContents($contents));
        };

        reset($middlewares);

        $next = function($request, $response) use(&$middlewares, &$next)
        {
            $call = next($middlewares);

            return $call($request, $response, $next);
        };

        $first = function($request, $response) use(&$middlewares, $next)
        {
            reset($middlewares);

            $call = current($middlewares);

            return $call($request, $response, $next);
        };

        // expect a ResponseInterface
        $response = $first($context->getRequest(), $context->getResponse(), $next);

        return $response;
    }
}