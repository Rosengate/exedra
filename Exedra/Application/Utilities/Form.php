<?php namespace Exedra\Application\Utilities;
/**
 * Old form
 * For new one refer to \Exedra\Application\Builder\Form\Form
 */
class Form
{
	public $data;
	public $optionData;

	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		if($flash_data = $this->exe->flash->has('form_data'))
			$this->set($this->exe->flash->get('form_data'));
	}

	/**
	 * @param string name
	 * @param string value
	 * @param boolean valueOnly
	 * @return string
	 */
	private function buildValue($name, $value = null, $valueOnly = false)
	{
		if($value !== false)
		{
			$value = $this->has($name) ? $this->get($name) : $value;
			$value = $value?($valueOnly?$value:' value="'.$value. '"' ):"";
		}

		return $value;
	}

	/**
	 * @param mixed attr
	 * @return string
	 */
	private function buildAttr($attr = null)
	{
		$attrs	= "";
		if(is_array($attr))
		{
			foreach($attr as $key=>$val)
			{
				$attrs	.= $key.'="'.$val.'" ';
			}
		}
		else
		{
			$attrs = $attr?$attr:"";
		}

		return $attrs;
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

	/**
	 * Set option type of input with list of array.
	 * @param mixed key string of the input name, or array (to be recursive.)
	 * @param list array of list.
	 * @return this
	 */
	public function setOption($key, array $list = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=> $v)
				$this->setOption($k, $v);
		}
		else
		{
			$this->optionData[$key] = $list;
		}

		return $this;
	}

	/**
	 * Check whether has option with the given key or not.
	 * @param string key
	 * @return boolean
	 */
	public function hasOption($key)
	{
		return isset($this->optionData[$key]);
	}

	/**
	 * Get the options
	 * @param string key
	 * @return array
	 */
	public function getOption($key)
	{
		return $this->optionData[$key];
	}

	/**
	 * Get form data.
	 * @param mixed key
	 * @return mixed $this->data
	 */
	public function get($key = null)
	{
		return $key ? $this->data[$key] : $this->data;
	}

	/**
	 * aliast to set()
	 * @param mixed key
	 * @param mixed val
	 * @param boolean override
	 * @return $this
	 */
	public function populate($key, $val = false, $override = false)
	{
		return $this->set($key, $val, $override);
	}

	/**
	 * @param string key
	 * @param boolean
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	public function flash($data = array())
	{
		## re-flash with post data, if $data wasn't set.
		$data = count($data) == 0?$this->exe->request->post():$data;

		if(count($data) == 0) return $this;

		$this->exe->flash->set("form_data", $data);

		return $this;
	}

	/**
	 * @param string name
	 * @param mixed attr
	 * @param string value
	 * @return string
	 */
	public function text($name, $attr = null, $value = null)
	{
		$value = $this->buildValue($name, $value);
		$attr = $this->buildAttr($attr);
		return "<input name='$name' id='$name' type='text' $attr $value />";
	}

	/**
	 * @param string name
	 * @param mixed attr
	 * @param string value
	 * @return string
	 */
	public function textarea( $name, $attr = null, $value = null)
	{
		$attr = $this->buildAttr( $attr );
		$value = $this->buildValue( $name, $value, true );

		return '<textarea name="'.$name.'" id="'.$name.'" '.$attr.'>'.$value.'</textarea>';
	}

	/**
	 * @param string name
	 * @param mixed attr
	 * @param string value
	 * @return string
	 */
	public function password($name,$attr = null,$value = null)
	{
		$attr = $this->buildAttr($attr);
		$value = $this->buildValue($name, $value);
		return '<input type="password" name="'. $name .'" id="'.$name.'" '.$attr.' '.$value.' />';
	}

	/**
	 * HTML 5 compliance
	 */
	public function date($name, $attr = null, $value = null)
	{
		$attr = $this->buildAttr($attr);
		$value = $this->buildValue($name, $value);

		return '<input type="date" name="'. $name .'" id="'. $name .'" '.$attr.' '.$value.' />';
	}

	/**
	 * HTML 5 compliance
	 */
	public function time($name, $attr = null, $value = null)
	{
		$attr = $this->buildAttr($attr);
		$value = $this->buildValue($name, $value);

		return '<input type="time" name="'.$name.'" id="'.$name.'" '.$attr.' '.$value.' />';
	}

	/**
	 * @param string name
	 * @param array array
	 * @param mixed attr
	 * @param string value
	 * @param string firstOpt
	 * @return string
	 */
	public function select($name,$array = array(),$attr = null,$value = null, $firstOpt = '[Please select]')
	{
		$array = is_array($array) && count($array) > 0 ? $array : ($this->hasOption($name) ? $this->getOption($name) : array() );
		$firstOpt = $firstOpt !== false?'<option value="">'. $firstOpt .'</option>':'';

		$attr = $this->buildAttr($attr);
		$value = $this->buildValue($name, $value, true);

		$sel = '<select name="'.$name.'" id="'.$name.'" '.$attr.' >'.$firstOpt;
		$selected = '';
		foreach($array as $key=>$val)
		{
			if($value != '')
				$selected	= $value == $key?'selected':'';
			
			$sel .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
		}
		$sel .=	'</select>';
		
		return $sel;
	}

	/**
	 * @param string name
	 * @param array array
	 * @param mixed attr
	 * @param string value
	 * @param string wrapper
	 * @return string
	 */
	public function radio($name,$array = array(),$attr = null,$value = null,$wrapper = "")
	{
		$array = is_array($array) && count($array) > 0 ? $array : ($this->hasOption($name) ? $this->getOption($name) : array() );
		$result		= "";

		$attr = $this->buildParameter($name, $attr);
		$value = $this->buildParameter($name, null, $value, true);

		foreach($array as $key=>$val)
		{
			$sel	=  $value?($key == $value?'checked':''):'';
			$radio	= '<label><input type="radio" value="'.$key.'"  '.$sel.' '.$attr.' name="'.$name.'" />'.$val.'</label>';
			$result	.= $wrapper?str_replace("{content}",$radio, $wrapper):$radio;
		}

		return $result;
	}

	/**
	 * @param string name
	 * @param mixed attr
	 * @return string
	 */
	public function file($name,$attr = null)
	{
		$attr = $this->buildAttr($attr);
		return '<input type="file" name="'.$name.'" id="'.$name.'" '.$attr.' />';
	}

	/**
	 * @param string name
	 * @param mixed attr
	 * @param string value
	 * @return string
	 */
	public function hidden($name,$attr = null,$value = null)
	{
		$attr = $this->buildAttr($attr);
		$value = $this->buildValue($name, $value);
		return '<input type="hidden" name="'.$name.'" id="'.$name.'" '.$attr.' '.$value.' />';
	}
}