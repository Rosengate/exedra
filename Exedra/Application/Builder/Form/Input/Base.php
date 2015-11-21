<?php
namespace Exedra\Application\Builder\Form\Input;

class Base
{
	/**
	 * Value attribute
	 * @var string value
	 */
	protected $value;

	/**
	 * List of attribute in key value pairs
	 * @var array attributes
	 */
	protected $attributes = array();

	/**
	 * list of attribute in string
	 * @var array attributeString
	 */
	protected $attributeString = array();

	/**
	 * input name
	 * @var string name
	 */
	protected $name;

	/**
	 * Input id
	 * @var string id
	 */
	protected $id;

	/**
	 * List of input class
	 * @var array classes
	 */
	protected $classes = array();

	/**
	 * Overriding value
	 * @var string override
	 */
	protected $override;

	public function __construct($name)
	{
		$this->name($name);
	}

	/**
	 * Set input id
	 * @param string id
	 * @return this
	 */
	public function id($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Set input name
	 * @param string name
	 * @return this
	 */
	public function name($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Add class to the stack.
	 * @param string class
	 * @return this
	 */
	public function addClass($class)
	{
		$this->classes[] = $class;

		return $this;
	}

	/**
	 * Set input value
	 * @param string value
	 * @return this
	 */
	public function value($value)
	{
		$this->value = $value;

		return $this;
	}

	/**
	 * Set overriding input value
	 * @param string value
	 * @return this
	 */
	public function override($value)
	{
		$this->override = $value;

		return $this;
	}

	/**
	 * Set input attribute
	 * @param mixed key
	 * @param string value
	 * @return this
	 */
	public function attr($key, $value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k => $val)
				$this->attributes[$k] = $val;

			return $this;
		}
		elseif($value === null)
		{
			$this->attributeString[] = $key;

			return $this;
		}

		if($key == 'class')
			return $this->addClass($value);

		$this->attributes[$key] = $value;

		return $this;
	}

	/**
	 * Build input attribute
	 * Also build input class attribtue
	 * @return string
	 */
	protected function buildAttributes()
	{
		$attrs = array();

		$class = '';

		if(count($this->classes) > 0)
			$class = 'class="'.implode(' ', $this->classes).'" ';
		
		if(count($this->attributeString) > 0)
			$attrs = $this->attributeString;

		foreach($this->attributes as $key => $value)
			$attrs[] = $key.'="'.$value.'"';

		return $class.implode(' ', $attrs);
	}

	/**
	 * Get input value.
	 * @return string
	 */
	public function getValue()
	{
		$value = $this->override ? : ($this->value ? : '');

		return $value;
	}

	public function __toString()
	{
		return $this->toString();
	}
}


?>