<?php
namespace Exedra\Module;

class Application extends \Exedra\Module\Module
{
	public function __construct(\Exedra\Application $app, $namespace, \Exedra\Path $path)
	{
		\Exedra\Container\Container::__construct();

		$this->services['app'] = $app;

		$this->namespace = $app->getNamespace();

		$this->setUp($app->path['app']);
	}
}