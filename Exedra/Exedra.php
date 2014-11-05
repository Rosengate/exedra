<?php
namespace Exedra;
require_once "ExedraLoader.php";

class Exedra
{
	## required objects.
	var $response	= null;
	var $apps		= Array();

	## executed application.
	var $executionResult	= null;

	public function __construct()
	{
		$loader	= new ExedraLoader();
		
		## helper functions.
		$loader->loadFunctions("helper");

		## register autoload.
		// $loader->registerAutoload(refine_path(dirname(__FILE__)."/Application"));
		// $loader->registerAutoload(refine_path(dirname(__FILE__)."/Exedrian"));
		$loader->registerAutoload();


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

		## create new application with an injected Map (with an injected map, request (an injected http request), and configuration handler.).
		$this->apps[$app_name]	= new \Exedra\Application\Application($app_name,Array(
			"map"		=>new \Exedra\Application\Map(),
			"loader"	=>$loader = new \Exedra\Application\Loader,
			"structure"	=>$structure = new \Exedra\Application\Structure,
			"request"	=>new \Exedra\Application\Request($this->httpRequest),
			"response"	=>new \Exedra\Application\Response,
			"controller"=>new \Exedra\Application\Builder\Controller($structure,$loader),
			"layout" 	=>new \Exedra\Application\Builder\Layout($structure,$loader)
			));
			
		if($execution)
		{
			// $this->apps[$app_name]->setExecution($execution);
			$this->apps[$app_name]->build($execution);
		}

		return $this->apps[$app_name];
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