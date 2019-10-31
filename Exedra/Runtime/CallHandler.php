<?php

namespace Exedra\Runtime;

use Exedra\Routing\Call;

class CallHandler implements \Exedra\Contracts\Runtime\CallHandler
{
    public function handle(Call $call, array $args)
    {
        return call_user_func_array($call, $args);
    }
}