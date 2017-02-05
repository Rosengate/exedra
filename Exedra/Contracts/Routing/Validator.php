<?php
namespace Exedra\Contracts\Routing;

use Exedra\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

interface Validator
{
    /**
     *
     * @param Route $route
     * @param ServerRequestInterface $request
     * @param array $parameters
     * @param $path
     * @return mixed
     */
    public function validate(array $parameters = array(), Route $route, ServerRequestInterface $request, $path);
}