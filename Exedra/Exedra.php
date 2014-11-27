<?php
namespace Exedra;
require_once "ExedraLoader.php";

class Exedra
{
	## Required objects.
	var $response			= null;
	var $apps				= Array();
	private $exedraLoader 	= null;

	## Executed application.
	var $executionResult	= null;

	public function __construct($dir)
	{
		$this->exedraLoader	= new ExedraLoader();
		
		## helper functions.
		$this->exedraLoader->loadFunctions("helper");

		## register autoload.
		// $this->exedraLoader->registerAutoload();
		$this->exedraLoader->registerAutoload($dir);

		## create http request.
		$this->httpRequest	= new \Exedra\Exedrian\HTTPRequest;

		## parser.
		$this->parser		= new \Exedra\Exedrian\Parser;
	}

	public function build($app_name,$execution = null)
	{
		## inject this components into the application.
		if(isset($this->apps[$app_name]))
			return $this->apps[$app_name];

		## register autoload for this app_name.
		$this->exedraLoader->registerAutoload($app_name);

		## create new application with an injected Map (with an injected map, request (an injected http request), and configuration handler.).
		$this->apps[$app_name] = new \Exedra\Application\Application($app_name,Array(
			"structure"	=>$structure 	= new \Exedra\Application\Structure($app_name),
			"loader"	=>$loader 		= new \Exedra\Application\Loader($structure),
			"map"		=>new \Exedra\Application\Map\Map($loader),
			"request"	=>new \Exedra\Application\Request($this->httpRequest),
			"response"	=>new \Exedra\Application\Response,
			"controller"=>new \Exedra\Application\Builder\Controller($structure,$loader),
			"view"		=>new \Exedra\Application\Builder\View($structure,$loader),
			));
			
		## Execute in instant.
		$execution($this->apps[$app_name]);

		return $this->apps[$app_name];
	}

	public function get($name)
	{
		return isset($this->apps[$name])?$this->apps[$name]:null;
	}

	## load application by closure.
	public function load($path,$parameter = null)
	{
		$closure	= require_once $path;

		$closure($this,$parameter);
	}

	## main dispatch.
	public function dispatch()
	{
		try{
			## loop the application. and execute the routing.
			foreach($this->apps as $app_name=>$app)
			{
				$executionResponse	= $app->dispatch();

				## matched.
				if($executionResponse)
				{
					## save.
					$this->executionResponse['app_name']	= $app_name;
					$this->executionResponse['data']		= $executionResponse;
				}
			}

			## return a parsed router response.
			return $this->executionResponse?$this->parser->parse($this->executionResponse['data']):null;
		}
		catch(exception $e)
		{
			$message[]	= $e->getMessage();
			return $this->parser->parse(implode("<br>",$message),404);
		}
	}
}


?>