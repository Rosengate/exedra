<?php
namespace Exedra\Application;

class Structure
{
	private $path;
	private $pattern;

	public function __construct()
	{
		## main container for application.
		$this->app			= "app";

		## default path for exedra.
		$this->path['folder']	= Array(
			"default_subapp"=>"default",
			"controller"	=>"_controller",
			"layout"		=>"_layout",
			"model"			=>"_model",
			"config"		=>"_config"
			);

		$this->pattern	= Array(
			"controller_name"=>function($val)
				{
					return "Controller".str_replace(" ","",ucwords(str_replace("/", " ", $val)));
				},
			"layout_name"=>function($val)
				{
					return "Layout".str_replace(" ","",ucwords(str_replace("/"," ", $val)));
				}
			);
	}

	public function refinePath($paths)
	{
		return implode("/",$paths);
	}

	public function getPath($name,$additional = null)
	{
		$paths	= Array();
		$paths[]	= $this->app;
		$paths[]	= $this->path['folder'][$name];

		## Exception : directory for this path does not exists.
		if(!is_dir($temp = $this->refinePath($paths)))
			throw new Exception("Directory for Structure.$name ($temp) does not exists");

		if($additional)
		{
			foreach($additionals = explode("/",$additional) as $no=>$p)
			{
				$paths[]	= $p;

				if(!is_dir($temp = $this->refinePath($paths)) && count($additionals) < $no)
					throw new Exception("Directory for path ($temp) does not exists");
			}
		}

		return $this->refinePath($paths);
	}

	public function getPattern($pattern,$val)
	{
		if(!isset($this->pattern[$pattern]))
			throw new Exception("Structure.pattern called $pattern does not exists.");

		return $this->pattern[$pattern]($val);
	}
}



?>