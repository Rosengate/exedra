<?php
namespace App\Controllers;

use Exedra\Routeller\Controller\Controller;
use Exedra\Runtime\Context;

class FooController extends Controller
{
    public function middleware(Context $context)
    {
        return 'middleware ' . $context->next($context);
    }

    /**
     * @path /index
     */
    public function executeIndex()
    {

    }

    public function get()
    {
        return 'foo hello';
    }

    /**
     * @path /users
     */
    public function getUsers()
    {
    }

    public function post()
    {
    }

    public function put()
    {
    }

    public function delete()
    {
    }

    public function patch()
    {

    }


    /**
     * @path /bar
     */
    public function groupBar()
    {
        return BarController::class;
    }
}