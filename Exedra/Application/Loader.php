<?php
namespace Exedra\Application;

class Loader
{
	private $loaded;
	public $structure;

	public function __construct(\Exedra\Application\Structure $structure)
	{
		$this->structure	= $structure;
	}

	public function isLoadable($file)
	{
		return strpos($file, ":") !== false;
	}

	public function load($file,$data = null, $subapp = null)
	{
		if(($colonPos = strpos($file, ":")) !== false)
		{
			list($structure,$file)	= explode(":",$file);
			$file	= $this->structure->get($structure)."/".$file;
		}

		if(isset($loaded[$file])) return false;

		if(!file_exists($file))
			throw new \Exception("File not found : $file", 1);

		if($data && is_array($data))
			extract($data);

		return require_once $file;
	}
}

?>