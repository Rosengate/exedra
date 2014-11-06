<?php
namespace Exedra;

class ExedraLoader
{
	var $loadedClass	= Array();

	public function loadFunctions($file)
	{
		if(is_array($file))
		{
			foreach($file as $fileName)
			{
				$this->loadFunctions($fileName);
			}
			return;
		}
		#$path	= "exedra/libraries/Functions/$file.php";
		$path	= dirname(__FILE__)."/Functions/$file.php";

		if(file_exists($path))
		{
			require_once $path;
		}
	}

	public function registerAutoload()
	{
		$dir	= dirname(__FILE__);
		$context	= $this;
		spl_autoload_register(function($class) use ($dir,$context)
		{
			list($exedra,$class)	= explode("\\",$class,2);

			## get last charater from dir.
			$lastDirChar	= $dir[strlen($dir)-1];

			$class	= ucfirst($class);
			#$dir	= trim($dir,"/").($lastDirChar == "_"?"":"/"); # i find this to be trimming '/' from path, whichis not good for linux.
			$dir	= rtrim($dir,"/").($lastDirChar == "_"?"":"/");
			$path	= $dir.$class.".php";

			$path	= refine_path($path);
			if(file_exists($path))
			{
				$context->registerLoadedClass($class);
				require_once $path;
			}
		});
	}

	public function registerLoadedClass($class)
	{
		$this->loadedClass[]	= $class;
	}
}


?>