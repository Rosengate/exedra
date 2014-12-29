<?php
namespace Exedra\Application\Builder;

class View
{
	private $structure;
	private $loader;
	private $dir;
	private $defaultData = array();

	public function __construct($exe, $loader,$dir = null)
	{
		$this->loader = $loader;
		$this->structure = $loader->structure;
		$this->dir = $dir;
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
		$path = $this->buildPath($path);
		
		if(!$this->has($path, false))
			$this->exe->exception->create("Unable to find view '$path'");

		if(count($this->defaultData) > 0)
			$data = array_merge($data, $this->defaultData);

		$view	= new \Exedra\Application\Response\View($path,$data,$this->loader);
		
		return $view;
	}

	private function buildPath($path)
	{
		$path	= $path.".php";
		$path	= $this->structure->get("view",$path,$this->dir);

		return $path;
	}

	/**
	 * Return boolean of existence for the given path name.
	 */
	public function has($path, $build = true)
	{
		if($build)
		{
			$path = $this->buildPath($path);
		}

		return file_exists($path);
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