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
		$this->httpRequest	= new \Exedra\Exedrian\HTTP\Request;
		$this->httpResponse = new \Exedra\Exedrian\HTTP\Response;

		## parser.
		$this->parser		= new \Exedra\Exedrian\Parser;
	}

	public function build($app_name,$execution = null)
	{
		try
		{
			## inject this components into the application.
			if(isset($this->apps[$app_name]))
				return $this->apps[$app_name];

			## register autoload for this app_name.
			$this->exedraLoader->registerAutoload($app_name);

			## create new application with an injected Map (with an injected map, request (an injected http request), and configuration handler.).
			$this->apps[$app_name] = new \Exedra\Application\Application($app_name,Array(
				"exedra"	=>$this,
				"structure"	=>$structure 	= new \Exedra\Application\Structure($app_name),
				"loader"	=>$loader 		= new \Exedra\Application\Loader($structure),
				"map"		=>$map = new \Exedra\Application\Map\Map($loader),
				"controller"=>new \Exedra\Application\Builder\Controller($structure,$loader),
				"view"		=>new \Exedra\Application\Builder\View($structure,$loader),
				"model"		=>new \Exedra\Application\Builder\Model($loader),
				"session"	=>new \Exedra\Application\Session\Session
				));

			$map->setApp($this->apps[$app_name]);
				
			## Execute in instant.
			$execution($this->apps[$app_name]);

			return $this->apps[$app_name];

		}
		catch (\Exception $e)
		{
			die("<pre><hr><u>Application [$app_name] Building Exception :</u>\n".$e->getMessage()."<hr>");
		}
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

		/*
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
		}*/


	}
}


?>