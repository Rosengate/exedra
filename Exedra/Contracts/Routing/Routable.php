<?php
namespace Exedra\Contracts\Routing;

interface Routable
{
    public function any($path);

    public function get($path);

    public function post($path);

    public function put($path);

    public function patch($path);

    public function delete($path);

    public function option($path);

    public function path($path);

    public function method($methods);

    public function tag($tag);
}