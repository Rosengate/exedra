<?php
namespace Exedra\Support\Definitions;

use Exedra\Config;
use Exedra\Container\Registry;
use Exedra\Path;
use Exedra\Runtime\Factory\Form;
use Exedra\Runtime\Factory\Url;
use Exedra\Runtime\Redirect;

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
 * @property Registry[] $services
 */
interface Exe
{

}