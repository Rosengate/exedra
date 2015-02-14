<?php
namespace Exedra\Application\Builder;

/**
 * Exedra View Builder
 */

class View
{
	/**
	 * Instance of structure.
	 * @var \Exedra\Application\Structure\Structure
	 */
	protected $structure;

	/**
	 * Intance of execution based loader.
	 * @var \Exedra\Loader
	 */
	protected $loader;

	/**
	 * Default datas for this view.
	 * @var array
	 */
	protected $defaultData = array();

	/**
	 * View default extension.
	 * @var string
	 */
	protected $ext = 'php';

	public function __construct(\Exedra\Application\Execution\Exec $exe)
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

		// merge with default data.
		if(count($this->defaultData) > 0)
			$data = array_merge($data, $this->defaultData);

		$view	= new Blueprint\View($this->exe, $path,$data,$this->loader);
		
		return $view;
	}

	/**
	 * Build path with extension
	 * @param string path
	 * @return string
	 */
	protected function buildPath($path)
	{
		$path	= $path. '.' .$this->ext;

		return $path;
	}

	/**
	 * Check file's path existence.
	 * @param string path
	 * @param boolean build
	 * @return boolean
	 */
	public function has($path, $build = true)
	{
		if($build)
			$path = $this->buildPath($path);

		return $this->loader->has(array('structure'=> 'view', 'path'=> $path));
	}

	/**
	 * Set default data for every view created through this builder.
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