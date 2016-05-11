<?php
namespace Exedra\Console\Wizard;

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
				if($this->isHidden($command))
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

			if($this->isHidden($command))
				return;

			$choices[$command] = $definition['description'].' ('.$command.')';
		});

		$command = $this->askChoices('At your command, sire.', $choices);

		$this->manager->command($command);

		$this->say();

		if(in_array($this->ask('Do you need anything else? [y/n]'), array('y', '')))
			$this->executeIndex(new Arguments);
	}
}

?>