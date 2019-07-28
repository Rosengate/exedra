<?php
namespace Exedra\Contracts\Routing;

use Exedra\Routing\Factory;
use Exedra\Routing\Group;
use Exedra\Routing\Route;

interface GroupHandler
{
    /**
     * Validate group pattern
     *
     * @param mixed $pattern
     * @param Route|null $parentRoute
     * @return boolean
     */
    public function validateGroup($pattern, Route $parentRoute = null);

    /**
     * Resolve group pattern
     *
     * @param Factory $factory
     * @param mixed $pattern
     * @param Route|null $parentRoute
     * @return Group
     */
    public function resolveGroup(Factory $factory, $pattern, Route $parentRoute = null);
}