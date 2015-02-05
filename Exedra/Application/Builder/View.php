<?php
namespace Exedra\Application\Builder;

class View
{
	private $structure;
	private $loader;
	private $dir;
	private $defaultData = array();

	public function __construct(\Exedra\Application\Exec $exe/*, $loader,$dir = null*/)
	{
		$this->loader = $exe->loader;
		$this->structure = $exe->app->structure;
		$this->exe = $exe;
	}

	/**
	 * Create view instance.
	 * @param string path
	 * @param array data
	 * @return \Exedra\Application\Response\View view
	 */
	public function create($path,$data = array())
	{
		// $path = $this->buildPath($path);
		if(!$this->has($path))
			$this->exe->exception->create("Unable to find view '$path'");
		
		// append .php extension.
		$path = $this->buildPath($path);

		/*if(!$this->has($path, false))
			$this->exe->exception->create("Unable to find view '$path'");*/

		if(count($this->defaultData) > 0)
			$data = array_merge($data, $this->defaultData);

		$view	= new Blueprint\View($this->exe, $path,$data,$this->loader);
		
		return $view;
	}

	private function buildPath($path)
	{
		$path	= $path.".php";
		/*$dir = $this->dir;

		if(strpos($path, '@') === 0)
		{
			$path = substr($path, 1);
			$dir = null;
		}*/

		return $path;
	}

	/**
	 * Return boolean of existence for the given path name.
	 */
	public function has($path, $build = true)
	{
		if($build)
			$path = $this->buildPath($path);

		return $this->loader->has(array('structure'=> 'view', 'path'=> $path));
	}

	/**
	 * Set default data for every view created.
	 * @param mixed name
	 * @param data string
	 * @return this
	 */
	public function setDefaultData($key, $val = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->setDefaultData($k, $v);
			}
		}
		else
		{
			$this->defaultData[$key] = $val;
		}

		return $this;
	}
}