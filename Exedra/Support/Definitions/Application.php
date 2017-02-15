<?php
namespace Exedra\Support\Definitions;

use Exedra\Config;
use Exedra\Factory\Controller;
use Exedra\Factory\Url;
use Exedra\Http\ServerRequest;
use Exedra\Middleware\Registry as MiddlewareRegistry;
use Exedra\Path;
use Exedra\Provider\Registry as ProviderRegistry;
use Exedra\Routing\Route;
use Exedra\Routing\Group;
use Exedra\Session\Flash;
use Exedra\Session\Session;
use Exedra\Support\Runtime\ControllerFactory;
use Exedra\Support\Runtime\Url\UrlFactory;
use Exedra\View\Factory;
use Exedra\Wizard\Manager;

/**
 * Interface Application
 * @package Exedra\Support\Definitions
 *
 * @property Path $path
 * @property Group|Route[] $map
 * @property ProviderRegistry $provider
 * @property Config $config
 * @property ServerRequest $request
 * @property UrlFactory $url
 * @property Session $session
 * @property Flash $flash
 * @property Manager $wizard
 * @property ControllerFactory $controller
 * @property Factory $view
 */

interface Application
{

}