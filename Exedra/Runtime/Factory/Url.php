<?php
namespace Exedra\Runtime\Factory;

class Url extends \Exedra\Factory\Url
{
	protected $exe;

	public function __construct(
		\Exedra\Routing\Level $router,
		\Exedra\Http\ServerRequest $request = null,
		$appUrl = null,
		$assetUrl = null,
		\Exedra\Runtime\Exe $exe)
	{
		$this->exe = $exe;

		parent::__construct($router, $request, $appUrl, $assetUrl);
	}

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

	/**
	 * Create url by the given route name
	 * @param string name
	 * @param array named parameter
	 * @param array query array
	 * @return string
	 */
	public function create($routeName, array $data = array(), array $query = array())
	{
		$routeName = $this->exe->baseRoute($routeName);

		return parent::create($routeName, $data, $query);
	}
}