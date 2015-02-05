<?php
namespace Exedra;
require_once "Loader.php";

class Exedra
{
	/**
	 * An array of \Exedra\Application\Application
	 */
	var $apps				= Array();

	/**
	 * A brand new general loader.
	 */
	public $loader = null;

	/**
	 * The original \Exedra\HTTP\Request object.
	 */
	public $httpRequest;

	/**
	 * The original \Exedra\HTTP\Response object.
	 */
	public $httpResponse;

	/**
	 * Base directory of your application.
	 */
	private $baseDir;

	// private $exedraLoader 	= null;

	public function __construct($dir)
	{
		// $this->exedraLoader	= new ExedraLoader();

		$this->loader = new Loader($dir);
		
		// helper functions.
		// $this->loader->load("functions/helper.php");

		// register autoload.
		$this->loader->registerAutoload($dir);

		// create http request.
		$this->httpRequest	= new \Exedra\HTTP\Request;
		$this->httpResponse = new \Exedra\HTTP\Response;

		// baseDir
		$this->baseDir = $dir;
	}

	/**
	 * The interface to autoloading.
	 */
	public function registerAutoload($dir)
	{
		$this->loader->registerAutoload($dir);
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
	public function build($app_name,$execution = null)
	{
		try
		{
			// inject this components into the application.
			if(isset($this->apps[$app_name]))
				return $this->apps[$app_name];

			// register autoload for this app_name.
			$this->registerAutoload($app_name);

			// create new application with an injected Map (with an injected map, request (an injected http request), and configuration handler.).
			$this->apps[$app_name] = new \Exedra\Application\Application($app_name,$this);
				
			// Execute in instant.
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

	## dispatch request as a query for application execution.
	public function dispatch()
	{
		foreach($this->apps as $app_name=>$build)
		{
			echo $build->execute(Array(
				"method"=>$this->httpRequest->getMethod(),
				"uri"=>$this->httpRequest->getURI(),
				"ajax"=>$this->httpRequest->isAjax(),
				));
		}
	}
}


?>