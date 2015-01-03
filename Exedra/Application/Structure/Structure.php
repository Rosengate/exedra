<?php
namespace Exedra\Application\Structure;

class Structure
{
	private $path;
	private $pattern;
	private $data;
	public $appName;

	public function __construct($appName)
	{
		## main container for application.
		$this->appName			= $appName;

		## default path name.
		$this->data			= Array(
			"controller"	=>"controller",
			"model"			=>"model",
			"config"		=>"config",
			"view"			=>"view",
			"route"			=>"routes",
			"documents"		=>"documents",
			"middleware"	=>"middleware"
			);

		$this->character = array(
			"absolute"		=>"@"
			);

		$this->pattern	= Array(
			"controller_name"=>function($val)
				{
					return "Controller".str_replace(" ","",ucwords(str_replace("/", " ", $val)));
				},
			"middleware_name"=>function($val)
				{
					return "Middleware".str_replace(" ", "", ucwords(str_replace("/", " ", $val)));
				}
			);
	}

	private function refinePath($paths)
	{
		return implode("/",$paths);
	}

	public function getAppName()
	{
		return $this->appName;
	}

	## prefix, add just after the app name. suffix add at the end of structure value.
	public function get($name,$suffix = null,$prefix = null)
	{
		$paths	= Array();
		$paths[]	= $this->appName;

		if($prefix)
		{
			foreach($prefix = explode("/",$prefix) as $no=>$p)
			{
				$paths[]	= $p;

				if(!is_dir($temp = $this->refinePath($paths)) && count($additionals) < $no)
					throw new \Exception("Structure : Directory for path ($temp) does not exist");
			}
		}

		if(!isset($this->data[$name]))
			throw new \Exception("Structure '$name' does not exist");

		$paths[]	= $this->data[$name];

		## Exception : directory for this path does not exists.
		if(!is_dir($temp = $this->refinePath($paths)))
			throw new \Exception("Structure : Directory for path ($temp) does not exist");

		if($suffix)
		{
			foreach($additionals = explode("/",$suffix) as $no=>$p)
			{
				$paths[]	= $p;

				if(!is_dir($temp = $this->refinePath($paths)) && count($additionals) < $no)
					throw new \Exception("Structure : Directory for path ($temp) does not exist");
			}
		}

		return $this->refinePath($paths);
	}

	public function getCharacter($key)
	{
		return $this->character[$key];
	}

	public function getPattern($pattern,$val)
	{
		if(!isset($this->pattern[$pattern]))
			throw new \Exception("Structure : pattern called $pattern does not exists.");

		return $this->pattern[$pattern]($val);
	}
}



?>