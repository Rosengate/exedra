<?php
namespace Exedra\Wizard;

class Introducer extends Wizardry
{
	/**
	 * Say some introductional text
	 */
	public function introduce()
	{
		$this->say("---------------------------------------------------");
		$this->say("+++------------ Exedra | Wizardry ------------- +++");
		$this->say("---------------------------------------------------");
	}

	/**
	 * List all available command
	 * @param Arguments arguments
	 */
	public function executeIndex(Arguments $arguments)
	{
		$group = $arguments->get('group', false);

		if($group === '')
		{
			$choices = array();

			$this->manager->eachCommand(function($command, array $definition) use(&$choices)
			{
				if($this->manager->isHidden($command))
					return;

				$namespace = $definition['namespace'];

				$choices[$namespace] = $namespace;
			});

			$group = $this->askChoices('Select namespace.', $choices);
		}

		$choices = array();

		$this->manager->eachCommand(function($command, array $definition) use(&$choices, $group)
		{
			if($group && strpos($command, $group.':') !== 0)
				return;

			if($this->manager->isHidden($command))
				return;

				
			$choices[$command] = $definition['description'].' ('.$command.')';
		});

		if($needHelp = $arguments->get('help', false))
			$command = $this->askChoices('Which command do you need help with?', $choices);
		else
			$command = $this->askChoices('At your command, sire.', $choices);

		if($needHelp)
			$this->manager->showHelp($command);
		else
			$this->manager->command($command);

		$this->say();

		if($arguments->get('help', false))
		{
			$text = 'Continue with the help? [y/n]';
			$args = new Arguments(array('help' => true));
		}
		else
		{
			$text = 'Do you need anything else? [y/n]';
			$args = new Arguments;
		}

		if(in_array($this->ask($text), array('y', '')))
			return $this->executeIndex($args);
	}
}