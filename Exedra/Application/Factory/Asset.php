<?php
namespace Exedra\Application\Factory;

class Asset
{
	/**
	 * Whether asset is persistable on creation or not
	 * @var boolean
	 */
	protected $persistable = false;

	/**
	 * Base path for where exedra may look for it at.
	 * @var \Exedra\Path basePath
	 */
	protected $basePath;

	/**
	 * Asset configuration
	 * @var array config
	 */
	protected $config;

	protected $assetPaths = array();

	public function __construct(\Exedra\Application\Factory\Url $urlFactory, \Exedra\Path $basePath, array $config = array())
	{
		$this->urlFactory = $urlFactory;

		$this->config = $config;

		$this->initialize();

		$this->basePath = $basePath;
	}

	protected function initialize()
	{
		foreach($this->config as $key => $value)
		{
			switch($key)
			{
				case 'persistable':
				$this->setPersistable($value);
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
	 * Get factory base path
	 * @return string 
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * Set base path for this factory. All assets built will be based on this path.
	 * @param string path
	 * @param bool absoluteness of path given
	 * @return this
	 */
	public function setBasePath($path)
	{
		$this->basePath;
	}

	/**
	 * Enable file creation.
	 * @param boolean
	 */
	public function setPersistable($bool)
	{
		$this->persistable = $bool;
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
	 * @return \Exedra\Application\Factory\Blueprint\Asset
	 */
	public function js($filename)
	{
		$ds = DIRECTORY_SEPARATOR;

		$filename = (isset($this->assetPaths['js']) ? $this->assetPaths['js'].'/' : '').$filename;

		$filepath = $this->refinePath($this->basePath.$ds.$filename);

		return new Blueprint\Asset($this->urlFactory, 'js', $filepath, $filename, $this->persistable);
	}

	/**
	 * Instantiate a css asset
	 * @param string
	 * @return \Exedra\Application\Factory\Blueprint\Asset
	 */
	public function css($filename)
	{
		$ds = DIRECTORY_SEPARATOR;
		
		$filename = (isset($this->assetPaths['css']) ? $this->assetPaths['css'].'/' : '').$filename;

		$filepath = $this->refinePath($this->basePath.$ds.$filename);

		return new Blueprint\Asset($this->urlFactory, 'css', $filepath, $filename, $this->persistable);
	}
}