<?php
namespace Exedra\Application\Builder;

class Path
{
	/**
	 * @param \Exedra\Loader
	 */
	public function __construct(\Exedra\Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Create Path Instance
	 * @param string path
	 * @return Exedra\Application\Blueprint\File
	 */
	public function create($path)
	{
		return new Blueprint\Path($this->loader, $path);
	}
}


