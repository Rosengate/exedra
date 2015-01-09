<?php
namespace Exedra\Application\Builder;

class File
{
	public function __construct($app, $dirPrefix = null)
	{
		$this->app = $app;
		$this->dirPrefix = $dirPrefix;
	}

	public function load($firstArg, $secondArg = null)
	{
		if(!$secondArg)
			$path = $firstArg;
		else
			$path = $this->app->structure->get($firstArg, $secondArg, $this->dirPrefix);

		return new Blueprint\File($path);
	}
}