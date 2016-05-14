<?php
namespace Exedra\Wizard;

class Application extends Wizardry
{
	/**
	 * @namespace app
	 * @description List routes
	 * @arguments name, params
	 */
	public function executeRoutes($arguments)
	{
		$table = new \Exedra\Wizard\Tools\Table;

		$header = explode(' ', $arguments->get('params', 'route method tag uri'));

		$table->setHeader($header);

		$previousRoute = null;

		$this->app->map->each(function(\Exedra\Application\Map\Route $route) use($table, $header, $arguments)
		{
			$routeName = $route->getAbsoluteName();
			
			$methods = $route->getMethod();

			if(count($methods) == 4)
				$methods = 'any';
			else
				$methods = implode(', ', $methods);

			// only by name.
			if(isset($arguments['name']))
			{
				if(strpos($routeName, $arguments['name']) !== 0)
					return;
			}

			// list only routes that is executable
			if(!$route->hasExecution())
				return;

			$row = array();

			$data = array(
				'route' => $route->getAbsoluteName(), 
				'method' => $methods,
				'uri' => '/'.$route->getPath(true),
				'tag' => $route->hasProperty('tag') ? $route->getProperty('tag') : ''
				);

			foreach($header as $col)
			{
				$col = strtolower($col);

				$row[] = $data[$col];
			}

			$table->addRow($row);
		});

		if($table->getRowCounts() === 0)
			$table->addOneColumnRow('Not found!');

		$this->say('Showing list of routes : ');

		$this->tabulize($table);
	}

	/**
	 * @description Serve application
	 * @arguments port
	 */
	public function executeServe(Arguments $arguments)
	{
		$validation = function($answer)
		{
			if($answer == '')
				return true;

			if(!is_numeric($answer))
			{
				$this->say('Please specify only integer');
				return false;
			}

			if($answer < 7000)
			{
				$this->say('Please specify port greater than 7000');
				return false;
			}
			else if($answer > 65500)
			{
				$this->say('Please specify port smaller than 65500');
				return false;
			}

			return true;
		};

		$port = isset($arguments['port']) && $arguments['port'] !== '' ? $arguments['port'] : $this->ask('Run server at port 9000 ? [leave empty/specify] : ', $validation, 9000);

		$this->validate($port, $validation);

		// $dir = $this->app->config->get('dir.public', 'public');
		$dir = $this->app->path['public'];

		if(!file_exists($dir))
			return $this->say('Public folder doesn\'t exist. ('.$dir.')');

		if(isset($arguments['router']))
			$router = ' '.$arguments['router'];
		else
			$router = '';

		chdir($dir);

		$this->say('PHP server started at localhost:'.$port.' on folder '.realpath($dir));
		
		exec('php -S localhost:'.$port.$router);
	}
}