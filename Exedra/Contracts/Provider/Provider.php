<?php

namespace Exedra\Contracts\Provider;

use Exedra\Application;

interface Provider
{
    public function register(Application $app);
}