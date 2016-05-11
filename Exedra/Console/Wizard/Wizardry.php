<?php
namespace Exedra\Console\Wizard;

abstract class Wizardry
{
	/**
	 * Command namespace
	 * @var string|null
	 */
	protected static $namespace = 'app';

	protected static $definitions = array();

	/**
	 * Application instance
	 * @var \Exedra\Application app
	 */
	protected $app;

	/**
	 * Wizard manager
	 * @var \Exedra\Console\Wizard\Manager
	 */
	protected $manager;

	/**
	 * Get meta information : namespace
	 * If it's null, return 'app'
	 * @return string
	 */
	public static function getNamespace()
	{
		return static::$namespace ? : 'app';
	}

	public function __construct(\Exedra\Console\Wizard\Manager $manager, \Exedra\Application $app)
	{
		$this->manager = $manager;

		$this->app = $app;
	}

	/**
	 * Say something on console
	 * @param string text
	 * @param boolean break give line break
	 */
	public function say($text = '', $break = true)
	{
		if(is_array($text))
			$text = implode("\n", $text).($break === true ? "\n" : '');
		else
			$text = $text.($break === true ? "\n" : '');

		echo $text;

		return $this;
	}

	/**
	 * Read PHP input
	 * @return string
	 */
	public function inputRead()
	{
		$text = fgets(fopen('php://stdin', 'rw'));

		$text = trim($text);

		return $text;
	}

	/**
	 * Ask something on console
	 * @param string text
	 * @param mixed validation
	 * @param boolean recursive is doing something recursie
	 * @return string
	 */
	public function ask($text = '', $validation = null, $default = null, $recursive = false)
	{
		if(is_array($validation))
			$validation[] = 'abort';

		if(is_string($text) && is_array($validation) && $recursive === false)
			$text = $text.' ('.implode(',', $validation).') : ';

		if(is_string($text) && $validation === null)
			$text = trim(ucfirst($text)).': ';

		$this->say($text, false);

		$answer = $this->inputRead();

		// if default is passed, and answer exactly empty.
		if($default !== null && $answer === '')
		{
			$validation = null;
			$answer = $default;
		}

		// if validation failed, recursive.
		if($validation)
		{
			// quit
			if($answer == 'abort')
				return $this->abort();

			if(is_array($validation) && !in_array($answer, $validation))
			{
				return $this->say()->say('Please select a valid choice!')->ask($text, $validation, $default, true);
			}
			else if(is_callable($validation) && $validation($answer, $this) === false)
			{
				$this->say();

				return $this->ask($text, $validation, $default, true);
			}
		}

		$this->say();

		return $answer;
	}

	/**
	 * Configure an associative array
	 * @param array associative array
	 */
	public function configureAssoc(array $configuration, $validation = null)
	{
		$config = array();

		foreach($configuration as $key => $value)
		{
			revalidate:

			$this->say('# '.$key.($value ? ' ('.$value.')':'').' : ', false);

			$answer = $this->inputRead();

			$answer = $answer == '' ? $value : $answer;

			if(is_callable($validation))
			{
				if($validation($key, $answer) === false)
					goto revalidate;
			}

			$config[$key] = $answer;
		}

		$this->say();

		return $config;
	}

	/**
	 * Configure the given key
	 * @return array associative array
	 */
	public function configureKeys(array $keys, $validation = null)
	{
		$config = array();

		foreach($keys as $key)
		{
			revalidate:

			$this->say('# '.$key.' : ', false);

			$answer = $this->inputRead();

			if(is_callable($validation))
			{
				if($validation($key, $answer) === false)
					goto revalidate;
			}

			$config[$key] = $answer;
		}

		$this->say();

		return $config;
	}

	/**
	 * Pretty print to console
	 * @param string text
	 * @param string wall delimiter
	 */
	public function sayNice($text, $wall = '|')
	{
		$width = strlen($dashes = '-------------------------------------------------------');
		
		$wallLength = (strlen($wall) + 1) * 2;
		
		$text = wordwrap($text, $width - $wallLength);

		$texts = array();

		foreach(explode("\n", $text) as $line)
			$texts[] = $wall.' '.$line.str_repeat(' ', $width-strlen($line) - $wallLength).' '.$wall;

		$this->say($dashes);

		$this->say($texts);
		
		$this->say($dashes);
	}

	/**
	 * Abort! abort!
	 * Abort the console and stop anything.
	 */
	public function abort()
	{
		$lang = array('Well, as you like.', 'Abort! Abort!', 'See you later then!', 'Quitter!');
		
		$this->say();

		$this->say($lang[rand(0, 3)]);
		
		exit;
	}

	/**
	 * Ask a choice based question
	 * @param string question
	 * @param array choices
	 */
	public function askChoices($question, array $choices)
	{
		$choiceNumbers = array();

		$this->say($question);

		$num = 1;
		$answers = array();

		foreach($choices as $no => $choice)
		{
			$answers[$num] = $no;
			$choiceNumbers[] = $num;
			$choices[$no] = $num++.'. '.$choice;
		}

		return $answers[$this->ask(array_merge($choices, array('', 'Option : ')), $choiceNumbers)];
	}

	/**
	 * Tabulize the given table
	 * @param \Exedra\Console\Wizard\Tools\Table table
	 */
	public function tabulize(Tools\Table $table)
	{
		$table->printTable();
	}

	/**
	 * Validate by the given closure
	 * If false returned, recursive.
	 * @param string answer
	 * @param \Closure closure
	 */
	public function validate($answer, \Closure $closure)
	{
		$result = $closure($answer);

		if($result === false)
		{
			$this->validate($this->ask('Re-answer'), $closure);
		}
	}

	/**
	 * Show some tick
	 * @param string message
	 * @param boolean|true tick
	 */
	public function tick($message, $tick = true)
	{
		$checked = $tick ? '[X]' : '[ ]';

		$this->say($checked.' '.$message);
	}

}