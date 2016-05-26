<?php
namespace Exedra\Form\Input;

class Input extends Base
{
	protected $type;

	public function __construct($type, $name = null)
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
		$type = $this->type;

		if($this->override)
			$this->attributes['value'] = $this->override;

		$attributes = $this->buildAttributes();

		return '<input type="'.$type.'" '.$attributes.' />';
	}
}


?>