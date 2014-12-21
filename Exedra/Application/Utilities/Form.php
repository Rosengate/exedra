<?php
namespace Exedra\Application\Utilities;

class Form
{
	public $data;

	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		if($flash_data = $this->exe->flash->has('form_data'))
			$this->set($this->exe->flash->get('form_data'));
	}

	private function buildParameter($name, $attr = null, $value = null, $valueOnly = false)
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

		// $value = $this->flash->get($name, $value);
		$value = $this->has($name) ? $this->get($name) : $value;
		$value = $value?($valueOnly?$value:"value='$value'"):"";

		return $attrs.$value;
	}

	/**
	 * @param mixed key
	 * @param mixed val
	 * @param boolean override
	 * @return $this
	 */
	public function set($key, $val = false, $override = false)
	{
		if(!is_string($key) && !is_array($key))
			return $this->exe->exception->create("Form.set first argument must be array, or string. ");

		// if key was intended as an array, recurse while treating val as the overriding flag.
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->set($k, $v, $val);
			}
		}
		else
		{
			if(!$this->has($key) || $override == true)
				$this->data[$key] = $val;
		}

		return $this;
	}

	public function get($key)
	{
		return $this->data[$key];
	}

	/**
	 * @param mixed key
	 * @param mixed val
	 * @param boolean override
	 * @return $this
	 */
	public function populate($key, $val = false, $override = false)
	{
		return $this->set($key, $val, $override);
	}

	public function has($key)
	{
		return isset($this->data[$key]);
	}

	public function flash($data = array())
	{
		## re-flash with post data, if $data wasn't set.
		$data = count($data) == 0?$this->exe->request->post:$data;

		if(count($data) == 0) return $this;

		$this->exe->flash->set("form_data", $data);

		return $this;
	}

	public function text($name, $attr = null, $value = null)
	{
		return "<input name='$name' id='$name' type='text' ".$this->buildParameter($name, $attr, $value)." />";
	}

	public function textarea($name, $attr = null, $value = null)
	{
		return "<textarea name='$name' id='$name' $attr value='' ".$this->buildParameter($name, $attr, null).">".$this->buildParameter($name, null, $value, true)."</textarea>";
	}

	public function password($name,$attr = null,$value = null)
	{
		return "<input type='password' name='$name' id='$name' ".$this->buildParameter($name, $attr, $value)." />";
	}

	/**
	 * HTML 5 compliance
	 */
	public function date($name, $attr = null, $value = null)
	{
		return "<input type='date' name='$name' id='$name' ".$this->buildParameter($name, $attr, $value)." />";
	}

	/**
	 * HTML 5 compliance
	 */
	public function time($name, $attr = null, $value = null)
	{
		return "<input type='time' name='$name' id='$name' ".$this->buildParameter($name, $attr, $value)." />";
	}

	public function select($name,$array = array(),$attr = null,$value = null, $firstOpt = '[Please select]')
	{
		$array = is_array($array)?$array:array();
		$firstOpt = $firstOpt !== false?"<option value=''>$firstOpt</option>":"";

		$value = $this->buildParameter($name, null, $value, true);
		
		$sel = "<select name='$name' id='$name' ".$this->buildParameter($name, $attr)." >$firstOpt";
		$selected = "";
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

		$attr = $this->buildParameter($name, $attr);
		$value = $this->buildParameter($name, null, $value, true);

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
		return "<input type='file' name='$name' id='$name' ".$this->buildParameter($name, $attr)." />";
	}

	public function hidden($name,$attr = "",$value = "")
	{
		return "<input name='$name' id='$name' type='hidden' $message ".$this->buildParameter($name, $attr, $value)." />";
	}
}