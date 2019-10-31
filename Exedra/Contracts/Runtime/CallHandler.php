<?php

namespace Exedra\Contracts\Runtime;

use Exedra\Routing\Call;

interface CallHandler
{
    public function handle(Call $call, array $args);
}