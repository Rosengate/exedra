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

	public function __construct($baseDir, \Exedra\HTTP\Request $request = null)
	{
		$this->loader = new Loader($baseDir);
		
		// register autoload.
		$this->loader->registerAutoload(__DIR__, 'Exedra', false);

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
	 * Build application instance
	 * @param string app_name
	 * @param callback execution
	 * @return \Exedra\Application\Application
	 * @throws \Exception
	 */
	public function build($app_name,\Closure $execution = null)
	{
		try
		{
			// inject this components into the application.
			if(isset($this->apps[$app_name]))
				return $this->apps[$app_name];

			// register autoload for this app_name.
			$this->loader->registerAutoload($app_name);

			// create new application with an injected Map (with an injected map, request (an injected http request), and configuration handler.).
			$this->apps[$app_name] = new \Exedra\Application\Application($app_name,$this);
				
			// Execute in instant.
			if($execution)
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
		return isset($this->apps[$name])?$this->apps[$name]:null;
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
}


?>