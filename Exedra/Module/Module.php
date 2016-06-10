<?php
namespace Exedra\Module;

class Module extends \Exedra\Container\Container
{
	/**
	 * Module namespace
	 * @var string namespace
	 */
	protected $namespace;

	public function __construct(\Exedra\Application $app, \Exedra\Path $path, $namespace = null)
	{
		parent::__construct();

		$this->services['app'] = $app;

		$this->services['path'] = $path;

		$this->namespace = str_replace('/', '\\', $namespace);

		$this->setUp();
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Setup dependency registry
	 */
	protected function setUp()
	{
		$this->services['service']->register(array(
			'view' => function() {
				return new \Exedra\View\Factory($this->getPath()->create('View'));
			},
			'controller' => function() {
				return new \Exedra\Factory\Controller($this->getNamespace());
			}));
	}
}