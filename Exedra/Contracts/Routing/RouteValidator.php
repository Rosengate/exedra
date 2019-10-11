<?php

namespace Exedra\Contracts\Routing;

use Exedra\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

interface RouteValidator
{
    public function validate(Route $route, ServerRequestInterface $request, $groupUriPath);
}