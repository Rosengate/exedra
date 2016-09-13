<?php
namespace Exedra\Support\Provider;

class Modular implements \Exedra\Provider\ProviderInterface
{
	public function register(\Exedra\Application $app)
	{
		$app->path->register('modules', $app->path['app']->create('modules'));

		$app->map->middleware(function($exe)
		{
			if($exe->hasAttribute('module'))
			{
				$module = $exe->attr('module');

				$pathModule = $exe->path['modules']->create(strtolower($module));

				$exe->view = $exe->create('factory.view', array($pathModule->create('views')));

				$namespace = $exe->app->getNamespace().'\\'.ucfirst($module);

				$exe->controller = $exe->create('factory.controller', array($namespace));

				// autoload the controller path.
				$pathModule->autoloadPsr4($namespace.'\\Controller', 'controllers');
			}

			return $exe->next($exe);
		});
	}
}