<?php
namespace Exedra;

class ExedraLoader
{
	var $loadedClass	= Array();
	private $dir		= null;

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

	public function registerAutoload($dir)
	{
		spl_autoload_register(function($class) use($dir)
		{
			## extract both class name and vendor from the called name.
			list($vendor,$class)	= explode("\\",$class,2);

			## check the vendor based class.
			$class	= ucfirst($class);

			## if vendor is Exedra, just use current __DIR__
			if($vendor == "Exedra")
				$path	= __DIR__."/".$class.".php";
			else
				$path	= rtrim($dir,"/")."/".$vendor."/".$class.".php";

			$path	= refine_path($path);

			if(file_exists($path))
				require_once $path;
		});
	}

	/* Old method */
	public function _registerAutoload($dir = null)
	{
		$dir	= !$dir?__DIR__:$dir;

		$context	= $this;
		spl_autoload_register(function($class) use ($dir,$context,$vendor)
		{
			list($exedra,$class)	= explode("\\",$class,2);

			## get last charater from dir.
			$lastDirChar	= $dir[strlen($dir)-1];
			$class	= ucfirst($class);
			$dir	= rtrim($dir,"/").($lastDirChar == "_"?"":"/");
			
			if($vendor)
				$path	= $dir.$vendor."/".$class.".php";
			else
				$path	= $dir.$class.".php";

			$path	= refine_path($path);
			if(file_exists($path))
			{
				$context->registerLoadedClass($class);
				require_once $path;
			}
			else
			{
				echo $path."<br>";
			}
		});
	}

	public function registerLoadedClass($class)
	{
		$this->loadedClass[]	= $class;
	}
}


?>