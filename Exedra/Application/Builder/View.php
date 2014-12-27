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
		$path	= $path.".php";
		$path	= $this->structure->get("view",$path,$this->dir);
		
		if(!file_exists($path))
			$this->exe->exception->create("Unable to find view '$path'");

		if(count($this->defaultData) > 0)
			$data = array_merge($data, $this->defaultData);

		$view	= new \Exedra\Application\Response\View($path,$data,$this->loader);
		
		return $view;
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