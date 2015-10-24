<?php
namespace Exedra;
require_once "Loader.php";

class Exedra
{
	/**
	 * An array of \Exedra\Application\Application
	 * @var array
	 */
	var $apps = array();

	/**
	 * A brand new general loader.
	 * @var \Exedra\Loader
	 */
	public $loader;

	/**
	 * The original HTTP Request object.
	 * @var \Exedra\HTTP\Request
	 */
	public $httpRequest;

	/**
	 * The original HTTP Response object (more likely a service object)
	 * @var \Exedra\HTTP\Response
	 */
	public $httpResponse;

	/**
	 * Base directory exedra is mainly on.
	 * @var string
	 */
	private $baseDir;

	// private $exedraLoader 	= null;

	protected $sourceDir;

	public function __construct($baseDir, \Exedra\HTTP\Request $request = null)
	{
		$this->loader = new Loader($baseDir);
		
		$this->sourceDir = __DIR__;

		// register autoload.
		$this->loader->registerAutoload($this->sourceDir, 'Exedra', false);

		// create http request and response.
		$this->httpRequest	= $request ? : new \Exedra\HTTP\Request;
		$this->httpResponse = new \Exedra\HTTP\Response;

		// baseDir
		$this->baseDir = $baseDir;
	}

	/**
	 * Return the dir the exedra was based on.
	 */
	public function getBaseDir()
	{
		return $this->baseDir;
	}

	/**
	 * Path of Exedra source directory.
	 * @return string
	 */
	public function getSourceDir()
	{
		return $this->sourceDir;
	}

	/**
	 * Build application instance
	 * @param string app_name
	 * @param callback execution
	 * @return \Exedra\Application\Application
	 * @throws \Exception
	 */
	public function build($app_name = 'App',\Closure $execution = null)
	{
		try
		{
			if(is_array($app_name))
			{
				$namespacePrefix = isset($app_name['namespace']) ? $app_name['namespace'] : $app_name['name'];
				$app_name = $app_name['name'];
			}
			else
			{
				$namespacePrefix = $app_name;
			}

			// throw exception if name exists
			if(isset($this->apps[$app_name]))
				throw new \Exception("Application with name ".$app_name.' already exists.');

			// create new application with an injected Map (with an injected map, request (an injected http request), and configuration handler.).
			$this->apps[$app_name] = new \Exedra\Application\Application($app_name, $this);

			// register autoload for this app_name.
			$this->loader->registerAutoload($app_name, $namespacePrefix);
				
			// Execute in instant.
			if(!is_null($execution))
				$execution($this->apps[$app_name]);

			return $this->apps[$app_name];

		}
		catch (\Exception $e)
		{
			die("<pre><hr><u>Application [$app_name] Building Exception :</u>\n".$e->getMessage()."<hr>");
		}
	}

	/**
	 * Get application instance
	 * @param string name
	 * @return \Exedra\Application\Application
	 */
	public function get($name)
	{
		return isset($this->apps[$name])? $this->apps[$name] : null;
	}

	public function getAll()
	{
		return $this->apps;
	}

	## load application by closure.
	/**
	 * Load application by closure
	 * @param string path
	 * @param mixed parameter
	 */
	public function load($path,$parameter = null)
	{
		$closure	= require_once $path;

		return $closure($this,$parameter);
	}

	/**
	 * Dispatch request as query for application execution.
	 */
	public function dispatch()
	{
		foreach($this->apps as $app_name => $app)
		{
			echo $app->execute($this->httpRequest);
		}
	}

	public function wizard(array $argv)
	{
		array_shift($argv);

		$wizard = new \Exedra\Console\Wizard\Archmage($this);

		$wizard->run($argv);
	}
}


?>