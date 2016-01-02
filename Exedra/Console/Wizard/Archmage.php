<?php
namespace Exedra\Console\Wizard;
/**
 * \Exedra\Exedra based wizard
 */
class Archmage extends Wizardry
{
	public function __construct($exedra)
	{
		parent::__construct($exedra);
	}

	protected function setUp()
	{
		$this->register('start', array(
			'description' => 'Create application',
			'options' => array()
			));
	}

	public function executeIndex()
	{
		$this->say("Looks like you've come to the right place!");
		$this->say("So is there're anything i can help?");
		$this->say("");
		parent::executeIndex();
	}

	protected function executeList()
	{
		$this->say("List of your applications by the same instance of Exedra :");

		$apps = $this->exedra->getAll();
	}

	protected function executeStart()
	{
		$this->say("I am about to conjure a basic skeleton for your application.");
		$this->say("First, tell me your application name.");
		// $this->say("It will decide the base directory, \nand \Namespace for your application.");

		// ask the name.
		$answer = $this->ask('App Name [Empty for default (App)] : ', $nameValidation = function($answer, $wizard)
		{
			// if app name have some spacebar pressed.
			if(count(explode(' ', $answer)) > 1)
			{
				$wizard->say();
				$wizard->say('Please press no spacebar for your application name.');
				$wizard->say();
				return false;
			}

			return true;
		});

		$appName = $answer === '' ? 'App' : $answer;

		$this->setVariable('app_name', $appName);
		$this->setVariable('namespace', $appName);

		if($this->exedra->loader->has($appName))
			return $this->say('The application folder already exists.')->say('Looks like you\'re good to go!');

		$answer = $this->ask(array(
			'',
			'Will this configuration be ok with you?',
			'',
			'1. Application folder : {app_name}',
			'2. Namespace (Vendor) : \{namespace}',
			'',
			'Option (yes,no) : '
			), array('yes', 'no'));

		if($answer == 'no')
		{
			$this->say();
			$this->say('Ok, lets do some edit.');
			$this->say();

			$answer = $this->ask(array(
				'',
				'Please select the item do you want to edit :',
				'',
				'1. Application folder : {app_name}',
				'2. Namespace (Vendor) : \{namespace}',
				'',
				'Option : '), 
			function($value, $wizard) use($nameValidation)
			{
				if(!in_array($value, array(1,2)))
					return false;

				$keys = array(
					1 => 'app_name',
					2 => 'namespace');

				$answer = $wizard->ask('Change '.($key = ($value == 1 ? 'folder name' : 'namespace')).' to (or leave empty to remain the same) : ', $nameValidation);
				
				if($answer !== '')
					$wizard->setVariable($keys[$value], $answer);
				else
					$answer = $wizard->getVariable($keys[$value]);

				$wizard->say();

				if($wizard->ask(ucwords($key).' has been updated to '.$answer.'. Do you want to continue editing?', array('yes', 'no')) == 'no')
					return true;

				return false;
			});
		}

		// create skeleton.
		$this->withNecromancy()->createApp($this->getVariable('app_name'), $this->getVariable('namespace'));

		$this->say();
		$this->say('Successfully conjured the skeleton of your application!');
		$this->say('Remember, you\'re free to alter the structure or edit anything by any mean!');
		$this->say('My job here is done. Until next time.');
	}
}

?>