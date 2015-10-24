<?php
namespace Exedra\Console\Wizard\Spells;
/**
 * With the power granted by Exedra, this spell class may a little bit dangerous to a new user
 * Because it hides the mechanism of how things created.
 * Use with your own consent!
 */
class Necromancy
{
	public function __construct(\Exedra\Console\Wizard\Wizardry $wizard)
	{
		$this->wizard = $wizard;
		$this->exedra = $wizard->getExedra();
	}

	protected function loadBlueprint($name, array $data = array())
	{
		$blueprint = __DIR__.'/blueprint/'.$name;

		$content = file_get_contents($blueprint);

		foreach($data as $key => $value)
			$content = str_replace('{'.$key.'}', $value, $content);
		
		return $content;
	}

	protected function createDir($dir)
	{
		$dir = $this->exedra->getBaseDir().DIRECTORY_SEPARATOR.$dir;

		$result = mkdir($dir, 0775);

		if(!$result)
			return false;

		return true;
	}

	protected function createFile($filename, $content)
	{
		return file_put_contents($this->exedra->getBaseDir().DIRECTORY_SEPARATOR.$filename, $content);
	}

	protected function fileExists($path)
	{
		return file_exists($this->exedra->getBaseDir().DIRECTORY_SEPARATOR.$path);
	}

	public function createApp($name, $namespace = null)
	{
		// create public folder for this app.
		$baseDir = $this->exedra->getBaseDir();

		$appPath = $name;

		if(file_exists($appPath))
			return $this->wizard->say('Application folder already exists. Perhaps you can try run a scan command.');

		if(!$this->createDir($appPath))
			return $this->wizard
			->say("Somehow we couldn't create folder with path. ".$appPath.".")
			->say("Can you somehow check the permission first for this directory?");

		$param = array(
			'app_file_name' => $appPath.DIRECTORY_SEPARATOR.'app.php',
			'app_name' => $name,
			'bootstrap_file_name' => strtolower($name).'.bootstrap',
			'DS' => DIRECTORY_SEPARATOR
			);

		if($name === $namespace)
			$param['app_build'] = $name;
		else
			$param['app_build'] = "array('name' => '".$name."', 'namespace' => '".$namespace."')";


		// create $app_name.php
		$blueprintApp = $this->loadBlueprint('app', $param);
		$this->createFile($param['app_file_name'], $blueprintApp);
	
		// create bootstrap.
		/*$blueprintBootstrap = $this->loadBlueprint('bootstrap', $param);
		$this->createFile($param['bootstrap_file_name'], $blueprintBootstrap);*/

		// create public folder.
		$public_dir = 'public';

		if($this->fileExists($public_dir))
		{
			$public_dir = $public_dir.'_'.strtolower($name);

			$this->wizard->say('The public folder already occupied by another application, maybe.');
			$this->wizard->say("Is it ok if we name your public to '$public_dir'?");
			$answer = $this->wizard->ask("Leave empty if you agree :");

			if($answer !== '')
				$public_dir = $answer;
		}

		$this->createDir($public_dir);

		$blueprintIndex = $this->loadBlueprint('public.index', $param);
		$this->createFile($public_dir.DIRECTORY_SEPARATOR.'index.php', $blueprintIndex);

		// create wizard for app.
		$blueprintWizard = $this->loadBlueprint('app.wizard', $param);
		$this->createFile(strtolower($param['app_name']).'.wizard', $blueprintWizard);
	}
}


?>