<?php
namespace Exedra\Support\Definitions;

use Exedra\Config;
use Exedra\Container\Registry;
use Exedra\Factory\Controller;
use Exedra\Path;
use Exedra\Runtime\Factory\Form;
use Exedra\Runtime\Factory\Url;
use Exedra\Runtime\Redirect;
use Exedra\Session\Flash;
use Exedra\Session\Session;
use Exedra\View\Factory;

/**
 * Properties Definitions Exe container
 * Interface Exe
 * @package Exedra\Support\Definitions
 *
 * @property Application $app
 * @property Path $path
 * @property Config $config
 * @property Url $url
 * @property Redirect $redirect
 * @property Form $form
 * @property Factory $view
 * @property Registry[] $services
 * @property Session $session
 * @property Flash $flash
 * @property Controller $controller
 */
interface Exe
{

}