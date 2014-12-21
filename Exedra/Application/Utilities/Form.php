<?php
namespace Exedra\Application\Utilities;

class Form
{
	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;
	}


	private function buildParameter($attr = null, $value = null, $valueOnly = false)
	{
		$attrs	= "";
		if(is_array($attr))
		{
			foreach($attr as $key=>$val)
			{
				$attrs	.= $key."='".$val."' ";
			}
		}
		else
		{
			$attrs = $attr?$attr:"";
		}

		$value = $value?($valueOnly?$value:"value='$value'"):"";

		return $value.' '.$attrs;
	}

	public function text($name, $attr = null, $value = null)
	{
		return "<input name='$name' id='$name' type='text' ".$this->buildParameter($attr, $value)." />";
	}

	public function textarea($name, $attr = null, $value = null)
	{
		return "<textarea name='$name' id='$name' $attr ".$this->buildParameter($attr, null).">".$this->buildParameter(null, $value, true)."</textarea>"
	}

	public function password($name,$attr = null,$value = null)
	{
		return "<input type='password' name='$name' id='$name' ".$this->buildParameter($attr, $value)." />";
	}

	public function select($name,$array = array(),$attr = null,$value = null)
	{
		$array = is_array($array)?$array:array();
		$firstOpt = $firstOpt !== false?"<option value=''>$firstOpt</option>":"";

		$value = $this->buildParameter(null, $value, true);
		
		$sel = "<select name='$name' id='$name' ".$this->buildParameter($attr)." >$firstOpt";
		foreach($array as $key=>$val)
		{
			if($value != "")
				$selected	= $value == $key?"selected":"";
			
			$sel .= "<option value='$key' $selected>$val</option>";
		}
		$sel .=	"</select>";
		
		return $sel;
	}

	public function radio($name,$array = array(),$attr = null,$value = null,$wrapper = "")
	{
		$array		= is_array($array)? $array : array();
		$result		= "";

		$attr = $this->buildParameter($attr);
		$value = $this->buildParameter(null, $value, true);

		foreach($array as $key=>$val)
		{
			$sel	=  $value?($key == $value?"checked":""):"";
			$radio	= "<label><input type='radio' value='$key'  $sel $attr name='$name' />$val</label>";
			$result	.= $wrapper?str_replace("{content}",$radio, $wrapper):$radio;
		}

		return $result;
	}

	public function file($name,$attr = "")
	{
		return "<input type='file' name='$name' id='$name' ".$this->buildParameter($attr)." />";
	}

	public function hidden($name,$attr = "",$value = "")
	{
		return "<input name='$name' id='$name' type='hidden' $message ".$this->buildParameter($attr, $value)." />";
	}
}