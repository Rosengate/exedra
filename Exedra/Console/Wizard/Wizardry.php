<?php
namespace Exedra\Console\Wizard;

abstract class Wizardry
{
	/**
	 * @var \Exedra\Exedra
	 */
	protected $exedra;

	protected $variables = array();

	protected $memory = array();

	protected $commands = array();

	public function __construct(\Exedra\Exedra $exedra)
	{
		$this->exedra = $exedra;
		$this->setUp();
	}

	/**
	 * Register command
	 * @param string name
	 * @param array definition
	 */
	protected function register($name, array $definition)
	{
		$this->commands[$name] = $definition;
	}

	public function run(array $argv)
	{
		if(!isset($argv[0]) || (isset($argv[0]) && !isset($this->commands[$argv[0]])))
			$this->introduce();

		if(!isset($argv[0]))
			return $this->executeIndex();

		// reset.
		$this->variables = array();

		$command = $argv[0];

		// command options.
		array_shift($argv);

		$options = array();

		$pendingOption = null;
		$pendingValue = '';

		foreach($argv as $val)
		{
			if(strpos($val, '-') === 0)
			{
				// if previously have pending option, terminate.
				if($pendingOption !== null)
					$options[$pendingOption] = $pendingValue;

				$pendingOption = substr($val, 1);
				$pendingValue = ''; // reset.
				continue;
			}
			else
			{
				if($pendingValue === '')
					$pendingValue = $val;
				else
					$pendingValue .= ' '.$val;
			}
		}

		if($pendingOption !== null)
			$options[$pendingOption] = $pendingValue;

		if(!isset($this->commands[$command]))
		{
			$this->say('Unable to find the command you are looking for.');
			if($this->ask('Do you still want to continue?', array('yes', 'no')) == 'no')
				exit;

			// redirect to index.
			return $this->executeIndex();
		}

		// return $this->$command($options);
		return $this->runCommand($command, $options);
	}

	protected function runCommand($command, array $options = array())
	{
		$command = 'execute'.ucwords($command);

		return $this->$command($options);
	}

	protected function introduce()
	{
		$this->say("-------------------------------------------------------");
		$this->say("+++ Welcome to the forbidden practice of dark arts! +++");
		$this->say("-------------------------------------------------------");
		$this->say("");
	}

	public function executeIndex()
	{
		$this->say('At your command, sire.');

		$choices = array();

		$no = 0;
		$choiceNo = array();
		$commands = array();

		foreach($this->commands as $command => $struct)
		{
			$no++;
			$choiceNo[] = $no;
			$choices[] = $no.'. '.$struct['description']. ' ('.$command.')';

			$commands[$no] = $command;
		}
		
		/*foreach($this->reference->getRegistry() as $command => $struct)
		{
			$no++;
			$choiceNo[] = $no;
			$choices[] = $no.'. '.$struct['description']. ' ('.$command.')';

			$commands[$no] = 'execute'.ucwords($command);
		}*/

		$choices = array_merge($choices, array('', 'Option : '));

		$answer = $this->ask($choices, $choiceNo);

		return $this->runCommand($commands[$answer]);
	}

	/**
	 * Tabulize the given table
	 */
	public function tabulize(Tools\Table $table)
	{
		$table->printTable();
	}

	/**
	 * @return \Exedra\Console\Wizard\Spells\Necromancy
	 */
	public function withNecromancy()
	{
		return isset($this->memory['necromancy']) ? $this->memory['necromancy'] : $this->memory['necromancy'] = new \Exedra\Console\Wizard\Spells\Necromancy($this);
	}

	public function say($text = '', $break = true)
	{
		if(is_array($text))
			$text = implode("\n", $text).($break === true ? "\n" : '');
		else
			$text = $text.($break === true ? "\n" : '');

		foreach($this->variables as $key => $value)
			$text = str_replace('{'.$key.'}', $value, $text);

		echo $text;

		return $this;
	}

	public function getExedra()
	{
		return $this->exedra;
	}

	public function inputRead()
	{
		$text = fgets(fopen('php://stdin', 'rw'));
		$text = trim($text);

		return $text;
	}

	public function setVariable($varname, $value)
	{
		$this->variables[$varname] = $value;
	}

	public function getVariable($varname, $default = null)
	{
		return isset($this->variables[$varname]) ? $this->variables[$varname] : $default;
	}

	/**
	 * @return string
	 */
	public function ask($text, $validation = null, $recursive = false)
	{
		if(is_array($validation))
			$validation[] = 'abort';

		if(is_string($text) && is_array($validation) && $recursive === false)
			$text = $text.' ('.implode(',', $validation).') : ';

		$this->say($text, false);

		$answer = $this->inputRead();

		$this->say();

		// if validation failed, recursive.
		if($validation)
		{
			// quite
			if($answer == 'abort')
				return $this->abort();

			if(is_array($validation) && !in_array($answer, $validation))
				return $this->say()->say('Please select a valid choice!')->ask($text, $validation, true);
			else if(is_callable($validation) && $validation($answer, $this) === false)
				return $this->ask($text, $validation, true);
		}

		return $answer;
	}

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
	 * With a given number
	 */
	public function askChoices($question, array $choices)
	{
		$choiceNumbers = array();

		$this->sayNice($question);


		$num = 1;
		$answers = array();

		foreach($choices as $no => $choice)
		{
			$answers[$num] = $no;
			$choiceNumbers[] = $num;
			$choices[$no] = $num++.'. '.$choice;
		}

		$this->say();

		return $answers[$this->ask(array_merge($choices, array('', 'Option : ')), $choiceNumbers)];
	}

	public function abort()
	{
		$lang = array('Well, as you like.', 'Abort! Abort!', 'See you later then!', 'Quitter!');
		
		$this->say();
		$this->say($lang[rand(0, 3)]);
		$this->say();
		exit;
	}
}

?>