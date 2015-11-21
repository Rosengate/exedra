<?php
namespace Exedra\Application\Builder\Form;

class Form
{
	/**
	 * Form data
	 * @var array data
	 */
	protected $data = array();

	/**
	 * Form options data
	 * For select() for example
	 * @var array dataOptions
	 */
	protected $dataOptions = array();

	/**
	 * Overriding form data
	 * @var array override
	 */
	protected $override = array();

	/**
	 * Set form data
	 * @param string|array key
	 * @param string|boolean value
	 * @param boolean override
	 * @return this
	 */
	public function set($key, $value = null, $override = false)
	{
		// if key is array,
		// loop the set, while expecting value parameter to be override
		if(is_array($key))
		{
			$override = $value;

			foreach($key as $k => $val)
			{
				if($override === true)
					$this->setOverride($k, $val);
				else
					$this->data[$k] = $val;
			}

			return $this;
		}
		else
		{
			if($override === true)
				$this->setOverride($key, $value);
			else
				$this->data[$key] = $value;

			return $this;
		}
	}

	/**
	 * Set overriding data
	 * @param string key
	 * @param string value
	 * @return this
	 */
	public function setOverride($key, $value = null)
	{
		if(is_array($key))
			foreach($key as $k => $val)
				$this->override[$k] = $val;
		else
			$this->override[$key] = $value;

		return $this;
	}

	/**
	 * Set options
	 * @param string key
	 * @param array options
	 * @return this
	 */
	public function setOptions($key, array $options)
	{
		$this->dataOptions[$key] = $options;

		return $this;
	}

	/**
	 * Alias to setOptions
	 */
	public function setOption($key, array $options)
	{
		return $this->setOptions($key, $options);
	}

	/**
	 * Alias to setOverride
	 */
	public function override($key, $value = null)
	{
		return $this->setOverride($key, $value);
	}

	/**
	 * Alias to set()
	 */
	public function populate($key, $value = null, $override = false)
	{
		return $this->set($key, $value, $override);
	}

	/**
	 * Check data existence
	 * @param string key
	 * @return boolean
	 */
	public function has($key)
	{
		return isset($this->override[$key]) ? true : (isset($this->data[$key]) ? true : false);
	}

	/**
	 * Get form data
	 * @param string key
	 * @return string
	 */
	public function get($key, $default = null)
	{
		return isset($this->override[$key]) ? $this->override[$key] : (isset($this->data[$key]) ? $this->data[$key] : $default);
	}

	/**
	 * Create html input
	 * @param string type
	 * @param string name
	 * @param mixed attr
	 * @param string value
	 * @return \Exedra\Application\Builder\Form\Input\Input
	 */
	protected function createInput($type, $name, $value = null, $attr = null)
	{
		if($type == 'textarea')
			$input = new Input\Textarea($name);
		else
			$input = new Input\Input($type, $name);

		if($value)
			$input->value($value);
		else if(isset($this->data[$name]))
			$input->value($this->data[$name]);

		if($attr)
			$input->attr($attr);

		if(isset($this->override[$name]))
			$input->override($this->override[$name]);

		return $input;
	}

	/**
	 * Create html select
	 * @param string name
	 * @param array options (optional)
	 * @param mixed attr (optional)
	 * @param string value (optional)
	 * @param string first (optional)
	 * @return \Exedra\Application\Builder\Form\Input\Select
	 */
	public function select($name, array $options = array(), $value = null, $attr = null, $first = null)
	{
		$select = new Input\Select($name);

		if(count($options) > 0)
			$select->options($options);
		elseif (isset($this->dataOptions[$name]))
			$select->options($this->dataOptions[$name]);

		if($value)
			$select->value($value);
		else if(isset($this->data[$name]))
			$select->value($this->data[$name]);

		if($attr)
			$select->attr($attr);

		if(isset($this->override[$name]))
			$select->override($this->override[$name]);

		if($first)
			$select->first($first);

		return $select;
	}

	/**
	 * Create html text input
	 * @return Input\Input
	 */
	public function text($name, $value = null, $attr = null)
	{
		return $this->createInput('text', $name, $value, $attr);
	}

	/**
	 * Create html password input
	 * @return Input\Input
	 */
	public function password($name, $value = null, $attr = null)
	{
		return $this->createInput('password', $name, $value, $attr);
	}

	/**
	 * Create html textarea input
	 * @return Input\Input
	 */
	public function textarea($name, $value = null, $attr = null)
	{
		return $this->createInput('textarea', $name, $value, $attr);
	}

	/**
	 * Create html 5 date input
	 * @return Input\Input
	 */
	public function date($name, $value = null, $attr = null)
	{
		return $this->createInput('date', $name, $value, $attr);
	}

	/**
	 * Create html 5 time input
	 * @return Input\Input
	 */
	public function time($name, $value = null, $attr = null)
	{
		return $this->createInput('time', $name, $value, $attr);
	}

	/**
	 * Create html file input
	 * @return Input\Input
	 */
	public function file($name, $value = null, $attr = null)
	{
		return $this->createInput('file', $name, $value, $attr);
	}

	/** 
	* Create html hidden input
	* @return Input\Input
	*/
	public function hidden($name, $value = null, $attr = null)
	{
		return $this->createInput('hidden', $value, $attr);
	}

	/**
	 * Create html checkbox input
	 * @return Input\Input
	 */
	public function checkbox($name, $value, $status = false)
	{
		$input = $this->createInput('checkbox', $value);

		if($status)
			$input->attr('checked', true);

		return $input;
	}
}
