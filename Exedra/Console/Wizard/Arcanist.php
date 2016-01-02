<?php
namespace Exedra\Console\Wizard;
/**
 * \Exedra\Application\Application based wizard
 */
class Arcanist extends Wizardry
{
	protected $app;

	protected $exedra;

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;
		parent::__construct($app->getExedra());
	}

	protected function setUp()
	{
		$this->register('routes', array(
			'description' => 'List routes',
			'options' => array('name', 'params', 'scan')
			));

		$this->register('serve', array(
			'description' => 'Start server',
			'options' => array()
			));

		$this->register('config', array(
			'description' => 'List all configuration',
			'options' => array('scan')
			));
	}

	protected function executeScan()
	{
		
	}

	public function executeServe()
	{
		$port = $this->ask('Run server at port 9000 ? [Or please specify] : ', function($answer, $wizard)
		{
			if($answer == '')
				return true;

			if(!is_numeric($answer))
				return false;

			if($answer < 7000)
			{
				$wizard->say('Please specify port greater than 7000');
				return false;
			}
			else if($answer > 65500)
			{
				$wizard->say('Please specify port smaller than 65500');
				return false;
			}

			return true;
		});

		$port = $port == '' ? 9000 : $port;

		$public = $this->app->config->get('wizard.public_folder');

		$dir = $this->exedra->getBaseDir().'/'.$public;
		chdir($dir);
		$this->say('php server started at localhost:'.$port);
		exec('php -S localhost:'.$port);
	}

	/*protected function executeConfig(array $options = array())
	{
		$config = $this->app->config->getAll();

		if(isset($options['scan']))
		{
			$this->say("Checking your application configuration : ");

			if(!$this->app->config->has('app.url'))
			{
				$this->say("Couldn't find app.url. This key act as a base_url to url generation.");
				if($this->ask('Do you want to configure it now?', array('yes', 'no')) == 'no')
					return $this->executeIndex();
			}
		}

		$table = new Tools\Table;

		$table->setHeader(array('Key', 'Value'));

		foreach($config as $key => $value)
		{
			$table->addRow(array($key, json_encode($value)));
		}

		if($table->getRowCounts() === 0)
			$table->addRow('No config');

		$this->tabulize($table);	
	}*/

	public function executeRoutes(array $options = array())
	{
		$wizard = $this;

		$table = new Tools\Table;

		if(isset($options['params']))
			$header = explode(' ', $options['params']);
		else
			$header = array('route', 'method', 'uri');

		$table->setHeader($header);

		$previousRoute = null;

		$this->app->map->each(function(\Exedra\Application\Map\Route $route) use($table, $header, $options)
		{
			$routeName = $route->getAbsoluteName();
			$methods = $route->getMethod();
			if(count($methods) == 4)
				$methods = 'any';
			else
				$methods = implode(', ', $methods);

			// only by name.
			if(isset($options['name']))
			{
				if(strpos($routeName, $options['name']) !== 0)
					return;
			}

			$row = array();

			$data = array(
				'route' => $route->getAbsoluteName(), 
				'method' => $methods,
				'uri' => '/'.$route->getPath(true)
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
}