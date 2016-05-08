<?php
namespace Exedra\Console\Wizard;

class Manager
{
	protected $classes = array();

	protected $commands = array();

	/**
	 * @var array wizards key-wizard
	 */
	protected $wizards = array();

	/**
	 * Protect commands from being overridden
	 * @var bool isProtected
	 */
	protected $isProtected = true;

	/**
	 * List of overwritable commands
	 * @var array overwritables
	 */
	protected $overwritables = array();


	public function __construct(\Exedra\Application $app)
	{
		$this->app = $app;

		// register application wizard
		$this->add('Exedra\Console\Wizard\Application');
	}

	/**
	 * Listen to the console arguments
	 * @param array arguments
	 */
	public function listen(array $arguments)
	{
		if($this->app['factories']->has('wizard.introducer'))
			$wizard = $this->app->create('wizard.introducer');
		else
			$wizard = new \Exedra\Console\Wizard\Introducer($this, $this->app);

		try
		{
			$this->resolve();
		}
		catch(\Exception $e)
		{
			$wizard->introduce();

			$wizard->say('Failed to start with an exception :');
			
			return $wizard->say($e->getMessage());
		}



		// shift out file name
		array_shift($arguments);

		if(!isset($arguments[0]) || (isset($arguments[0]) && strpos($arguments[0], '-') === 0))
		{
			$wizard->introduce();

			$args = $this->parseArguments($arguments);

			return $wizard->executeIndex(new Arguments($args));
		}

		$command = $arguments[0];

		// if command is not namespaced, namespace with [app]
		if(strpos($command, ':') === false)
			$command = 'app:'.$command;

		// command not found
		if(!$this->has($command))
		{
			$wizard->introduce();

			$wizard->say('Unable to find the command ['.$command.'].');
			
			if($wizard->ask('Do you still want to continue?', array('yes', 'no')) == 'no')
				exit;

			// redirect to index.
			return $wizard->executeIndex(new Arguments);
		}

		// shift out command argument
		array_shift($arguments);

		return $this->command($command, $this->parseArguments($arguments));
	}

	/**
	 * parse given console arguments
	 * @param array arguments
	 * @return array
	 */
	protected function parseArguments(array $arguments)
	{
		$newArguments = array();

		$pendingArg = null;

		$pendingValue = '';

		foreach($arguments as $argument)
		{
			if(strpos($argument, '-') === 0)
			{
				// if previously have pending argument, terminate.
				if($pendingArg !== null)
					$newArguments[$pendingArg] = $pendingValue;

				$pendingArg = substr($argument, 1);
		
				$pendingValue = ''; // reset.
		
				continue;
			}
			else
			{
				if($pendingValue === '')
					$pendingValue = $argument;
				else
					$pendingValue .= ' '.$argument;
			}
		}

		if($pendingArg !== null)
			$newArguments[$pendingArg] = $pendingValue;

		return $newArguments;
	}

	/**
	 * Register the given fully qualified class name
	 * @param string class
	 * @param string|null namespace
	 */
	public function add($class)
	{
		$this->classes[] = $class;
	}

	/**
	 * Check whether given command exists
	 * @param string command
	 * @return boolean
	 */
	public function has($command)
	{
		return isset($this->commands[$command]);
	}

	/**
	 * Execute given command
	 * @param string command
	 * @param array arguments
	 */
	public function command($command, array $arguments = array())
	{
		$arguments = new Arguments($arguments);

		if(!isset($this->commands[$command]))
			throw new \Exedra\Exception\NotFoundException('Command ['.$command.'] does not exists');

		$definition = $this->commands[$command];

		if(!isset($this->wizards[$definition['class']]))
			$this->wizards[$definition['class']] = $wizard = new $definition['class']($this, $this->app);
		else
			$wizard = $this->wizards[$definition['class']];

		@list($namespace, $command) = explode(':', $command);

		if(!$command)
			$command = $namespace;

		$method = 'execute'.ucfirst($command);

		return $wizard->$method($arguments);
	}

	/**
	 * Get all resolved commands.
	 * @return array
	 */
	public function getCommands()
	{
		return $this->commands;
	}

	/**
	 * Get command definition
	 * @param string command
	 */
	public function getDefinition($command)
	{
		return $this->command[$command];
	}

	/**
	 * Parse docblock to key-value array
	 * @param string text
	 * @return array
	 */
	protected function parseDocBlock($text)
	{
		// http://www.murraypicton.com/archive/building-a-phpdoc-parser-in-php
		if(preg_match('#^/\*\*(.*)\*/#s', $text, $comment) === false)
			return array();

		if(!isset($comment[1]))
			return array();

		$comment = trim($comment[1]);

		// http://www.murraypicton.com/archive/building-a-phpdoc-parser-in-php
		if(preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false)
			return array();

		$data = array();

		foreach($lines[1] as $line)
		{
			$line = trim($line);

			if($line[0] !== '@')
				continue;

			@list($key, $value) = explode(' ', $line, 2);

			$data[substr($key, 1)] = trim($value);
		}

		return $data;
	}

	/**
	 * Loop through each command definition
	 * @param \Closure callback
	 */
	public function eachCommand(\Closure $callback)
	{
		foreach($this->commands as $name => $definition)
		{
			$callback($name, $definition);
		}
	}

	/**
	 * Check whether commands is protected
	 * @return boolean
	 */
	public function isProtected()
	{
		return $this->isProtected;
	}

	/**
	 * Set protection
	 * @param boolean bool
	 */
	public function setProtection($protected)
	{
		$this->isProtected = $protected;
	}

	/**
	 * Set list of overwriteable
	 * @param array overwriteable
	 */
	public function setOverwritables(array $overwritables)
	{
		$this->overwritables = $overwritables;
	}

	/**
	 * Resolve meta information reflectively
	 */
	protected function resolve()
	{
		foreach($this->classes as $class)
		{
			$reflectedClass = new \ReflectionClass($class);

			if(!$reflectedClass->isSubclassOf('\Exedra\Console\Wizard\Wizardry'))
				throw new \Exedra\Exception\InvalidArgumentException('['.$class.'] must be a subclass of [\Exedra\Console\Wizard\Wizardry]');

			$namespace = $class::getNamespace();

			foreach($reflectedClass->getMethods() as $reflectedMethod)
			{
				$method = $reflectedMethod->name;

				if(strpos($method, 'execute') !== 0)
					continue;

				$definition = $this->parseDocBlock($reflectedMethod->getDocComment());

				$command = strtolower(substr($method, 7));

				// get from method based namespace
				$namespace = isset($definition['namespace']) ? $definition['namespace'] : $namespace;

				$name = $namespace . ':' . $command;

				if($this->isProtected())
				{
					if(isset($this->commands[$name]) && !in_array($name, $this->overwritables))
						throw new \Exedra\Exception\Exception('Command ['.$name.'] is being overwritten by ['.$class.'] wizard.');
				}

				$this->commands[$name] = array_merge($definition, array(
					'namespace' => $namespace,
					'description' => isset($definition['description']) ? $definition['description'] : '',
					'arguments' => isset($definition['arguments']) ? array_map('trim', explode(',', $definition['arguments'])) : array(),
					'class' => $class,
					'command' => $command
				));
			}
		}
	}
}


?>