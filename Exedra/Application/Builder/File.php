<?php
namespace Exedra\Application\Builder;

class File
{
	/**
	 * @param \Exedra\Loader
	 */
	public function __construct(\Exedra\Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Create File Instance
	 * @param string path
	 * @return Exedra\Application\Blueprint\File
	 */
	public function get($path)
	{
		return new Blueprint\File($this->loader, $path)
	}
}

