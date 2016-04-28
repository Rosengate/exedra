<?php
namespace Exedra\Application\Execution\Factory;

class Url extends \Exedra\Application\Factory\Url
{
	/**
	 * Exec instance
	 * @var \Exedra\Application\Execution\Exec
	 */
	protected $exe;

	// public function __construct(\Exedra\Application\Execution\Exec $exe)
	public function __construct(
		\Exedra\Application\Map\Level $router,
		\Exedra\Http\ServerRequest $request = null,
		\Exedra\Application\Config $config,
		\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		parent::__construct($router, $request, $config);
	}

	/**
	 * Rebuild current route
	 * @param array data
	 * @param array query
	 * @return string
	 */
	/*public function current(array $data = array(), array $query = array())
	{
		// merge both finding parameters and the given data (prioritized)
		$data = array_merge($this->exe->finding->param(), $data);

		return $this->create('@'.$this->exe->getRoute(true), $data, $query);
	}*/

	/**
	 * Get url of parent route
	 * @param array data
	 * @param array query
	 * @return string
	 */
	public function parent(array $data = array(), array $query = array())
	{
		$data = array_merge($this->exe->finding->param(), $data);

		return $this->create('@'.$this->exe->getParentRoute(), $data, $query);
	}

	public function create($routeName, array $data = array(), array $query = array())
	{
		$routeName = $this->exe->baseRoute($routeName);

		return parent::create($routeName, $data, $query);
	}
}