<?php
namespace Exedra\Application\Execution\Builder;

class Url extends \Exedra\Application\Builder\Url
{
	/**
	 * Exec instance
	 * @var \Exedra\Application\Execution\Exec
	 */
	protected $exe;

	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		parent::__construct($exe->app);
	}

	/**
	 * Initiate url, prioritize exec instance.
	 */
	protected function initiateUrl()
	{
		// base url
		if($this->exe->config->has('app.url'))
			$this->setBase($this->exe->config->get('app.url'));
		else if($this->app->config->has('app.url'))
			$this->setBase($this->app->config->get('app.url'));

		// asset url
		if($this->exe->config->has('asset.url'))
			$this->setAsset($this->exe->config->get('asset.url'));
		else if($this->app->config->has('asset.url'))
			$this->setAsset($this->app->config->get('asset.url'));
	}

	public function getExceptionBuilder()
	{
		return $this->exe->exception;
	}

	/**
	 * Rebuild current route
	 * @param array data
	 * @param array query
	 * @return string
	 */
	public function current(array $data = array(), array $query = array())
	{
		// merge both finding parameters and the given data (prioritized)
		$data = array_merge($this->exe->finding->param(), $data);

		return $this->create('@'.$this->exe->getRoute(true), $data, $query);
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


	public function create($routeName, array $data = array(), array $query = array())
	{
		$routeName = $this->exe->baseRoute($routeName);

		return parent::create($routeName, $data, $query);
	}
}