<?php
namespace Exedra\Application\Builder;

class Asset
{
	/**
	 * @var \Exedra\Application\Execution\Exec
	 */
	protected $exe;

	/**
	 * Whether the asset can create by the given \Closure or not.
	 * @var boolean
	 */
	protected $createEnabled;

	/**
	 * Base path for where exedra may look for it at.
	 * @var string
	 */
	protected $basePath;

	protected $assetPaths = array();

	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		$this->initialize();
	}

	protected function initialize()
	{
		$configAsset = array_merge($this->exe->app->config->get('asset', array()), $this->exe->config->get('asset', array()));

		foreach($configAsset as $key => $value)
		{
			switch($key)
			{
				case 'enable_create':
				$this->enableCreate($value);
				break;
				case 'base_path':
				$this->setBasePath($value);
				break;
				case 'js_path':
				$this->setJsPath($value);
				break;
				case 'css_path':
				$this->setCssPath($value);
				break;
			}
		}
	}

	/**
	 * @return string of Exedra very base directory.
	 */
	public function getBaseDir()
	{
		return $this->exe->app->exedra->getBaseDir();
	}

	/**
	 * Set base path for this builder. All assets built will be based on this path.
	 * @param string path
	 * @param boolean absoluteness of path given
	 * @return this
	 */
	public function setBasePath($path, $absolute = false)
	{
		if(!$absolute)
		{
			$root = $this->exe->app->exedra->getBaseDir();

			$this->basePath = $root.DIRECTORY_SEPARATOR.$path;
		}
		else
		{
			$this->basePath = $path;
		}

		return $this;
	}

	/**
	 * Enable file creation.
	 * @param boolean
	 */
	public function enableCreate($bool)
	{
		$this->createEnabled = $bool;
	}

	/**
	 * Correct the directory separator
	 * @param string path
	 * @return string
	 */
	protected function refinePath($path)
	{
		$path = str_replace(array('/', "\\"), DIRECTORY_SEPARATOR, $path);

		list($path) = explode('?', $path);

		return $path;
	}

	/**
	 * Set initial directory for js files
	 * @param string path
	 */
	public function setJsPath($path)
	{
		$this->assetPaths['js'] = $path;
	}

	/**
	 * Set initial directory for js files
	 * @param string path
	 */
	public function setCssPath($path)
	{
		$this->assetPaths['css'] = $path;
	}

	/**
	 * Instantiate a javascript Asset 
	 * @param string
	 * @return \Exedra\Application\Builder\Blueprint\Asset
	 */
	public function js($filename)
	{
		$ds = DIRECTORY_SEPARATOR;
		$filename = (isset($this->assetPaths['js']) ? $this->assetPaths['js'].'/' : '').$filename;
		$filepath = $this->refinePath($this->basePath.$ds.$filename);

		return new Blueprint\Asset($this->exe, 'js', $filepath, $filename, $this->createEnabled);
	}

	/**
	 * Instantiate a css asset
	 * @param string
	 * @return \Exedra\Application\Builder\Blueprint\Asset
	 */
	public function css($filename)
	{
		$ds = DIRECTORY_SEPARATOR;
		$filename = (isset($this->assetPaths['css']) ? $this->assetPaths['css'].'/' : '').$filename;
		$filepath = $this->refinePath($this->basePath.$ds.$filename);

		return new Blueprint\Asset($this->exe, 'css', $filepath, $filename, $this->createEnabled);
	}
}