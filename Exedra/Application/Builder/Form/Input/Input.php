<?php
namespace Exedra\Application\Builder\Form\Input;

class Input extends Base
{
	protected $type;

	public function __construct($type, $name)
	{
		parent::__construct($name);
		$this->type($type);
	}

	/**
	 * Set input type
	 * @param string type
	 * @return this
	 */
	public function type($type)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * Construct the html
	 * @return string
	 */
	public function toString()
	{
		$name = $this->name;
		$id = $this->id ? : $name;
		$value = isset($this->override) ? $this->override : ($this->value ? : '');

		$type = $this->type;
		$attributes = $this->buildAttributes();

		return '<input type="'.$type.'" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$attributes.' />';
	}
}


?>