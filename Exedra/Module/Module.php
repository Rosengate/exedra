<?php
namespace Exedra\Module;

class Module extends \Exedra\Container\Container
{
	/**
	 * Module namespace
	 * @var string namespace
	 */
	protected $namespace;

	public function __construct(\Exedra\Application $app, $namespace, \Exedra\Path $path)
	{
		parent::__construct();

		$this->services['app'] = $app;

		$this->namespace = str_replace('/', '\\', $namespace);

		$this->setUp($path);
	}

	public function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Setup dependency registry
	 */
	protected function setUp(\Exedra\Path $path)
	{
		$this->services['service']->register(array(
			'path' => function() use($path) {
				return $path;
			},
			'view' => function() {
				return new \Exedra\View\Factory($this->path->create('View'));
			},
			'controller' => function() {
				return new \Exedra\Factory\Controller($this->getNamespace());
			}));
	}
}