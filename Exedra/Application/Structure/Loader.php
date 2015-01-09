<?php
namespace Exedra\Application\Structure;

class Loader
{
	private $loaded;
	public $structure;
	private $dir;

	public function __construct(Structure $structure, $dir = null)
	{
		$this->structure	= $structure;
		$this->dir = $dir;
	}

	public function isLoadable($file)
	{
		return strpos($file, ":") !== false;
	}

	private function refinePath($file)
	{
		if(($colonPos = strpos($file, ":")) !== false)
		{
			list($structure,$file)	= explode(":",$file);
			$file	= $this->structure->get($structure)."/".$file;
		}

		return $file;
	}

	public function load($file,$data = null)
	{
		$file = $this->refinePath($file);

		if(isset($loaded[$file])) return false;

		if(!file_exists($file))
			throw new \Exception("File not found : $file");

		if($data && is_array($data))
			extract($data);

		return require_once $file;
	}

	public function getContent($file)
	{
		$file = $this->refinePath($file);

		if(!file_exists($file))
			throw new \Exception("File not found : $file");

		return file_get_contents($file);
	}
}

?>