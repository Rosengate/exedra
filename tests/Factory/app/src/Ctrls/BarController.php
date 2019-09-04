<?php
namespace Foo\Ctrls;

use Exedra\Routeller\Controller\Controller;
use Exedra\Routing\Group;
use Exedra\Runtime\Context;

class BarController extends Controller
{
    public function middleware(Context $context)
    {
        if ($context->route->getName() == 'get')
            return 'bar-middleware ' . $context->next($context);
        else
            return $context->next($context);
    }

    public function setup(Group $group)
    {
        $group['bah']->any('/bah')->execute(function() {

        });
    }

    /**
     * @path /baz
     */
    public function subBaz(Group $group)
    {
        $group['bat']->any('/')->execute(function() {
            return 'Batttt';
        });
    }

    /**
     * @path /hello
     */
    public function executeHello()
    {
        return 'hello world';
    }

    /**
     * @tag holla
     * @attr.attrval foobarbaz
     */
    public function get(Context $context)
    {
        return $context->attr('attrval');
    }

    /**
     * @config.conf confvalue
     * @tag testconf
     */
    public function delete(Context $context)
    {
        return $context->config->get('conf');
    }
}