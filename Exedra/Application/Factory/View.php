<?php
namespace Exedra\Application\Factory;

/**
 * Exedra View Factory
 */

class View
{
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
	 * Base dir
	 * @var string|null
	 */
	protected $baseDir = null;

	/**
	 * View default extension.
	 * @var string
	 */
	protected $ext = 'php';

	public function __construct(\Exedra\Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Create view instance based on given relative path.
	 * @param string path
	 * @param array data
	 * @return \Exedra\Application\Response\View view
	 */
	public function create($path, $data = array())
	{
		$path = $this->baseDir ? $this->baseDir . '/' . ltrim($path) : $path;

		$path = $this->buildPath($path);

		if(!file_exists($path))
			throw new \Exedra\Exception\NotFoundException('Unable to find view ['.$path.']');
		
		// merge with default data.
		$class = '\Exedra\Application\Factory\Blueprint\View';

		if(is_string($data))
		{
			// assume data as fully qualified class name 
			if($data)
			{
				$class = $data;

				$data = array();
			}
		}
		else
		{
			if(!is_array($data))
				throw new \Exedra\Exception\NotFoundException('Argument 2 must be either string or array ['.gettype($data).'] given.');
		}

		$data = array_merge($data, $this->defaultData);

		return new $class($path, $data);
	}

	/**
	 * Set base dir to be concenatted at the beginning of view path.
	 * @param string dir
	 *
	 */
	public function setBaseDir($dir)
	{
		$this->baseDir = $dir;
	}

	/**
	 * Absolutely build path based on the relative one
	 * @param string path
	 * @return string
	 */
	protected function buildPath($path)
	{
		$path	= $path. '.' .$this->ext;

		$path = $this->loader->buildPath('View/'. $path);

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

		return file_exists($path);
	}

	/**
	 * Set default data for every view created through this factory.
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