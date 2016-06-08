<?php
namespace Exedra\Runtime\Handler;

/**
 * A blueprint functional handler
 * Used when no class is given in handler registry
 */
class Handler extends HandlerAbstract
{
	protected $validation;

	protected $resolve;

	public function validate($pattern)
	{
		$validation = $this->validation;

		return $validation($pattern);
	}

	public function resolve($pattern)
	{
		$resolve = $this->resolve;

		return $resolve($pattern);
	}

	public function onValidate(\Closure $validation)
	{
		$this->validation = $validation;

		return $this;
	}

	public function onResolve(\Closure $resolve)
	{
		$this->resolve = $resolve;

		return $this;
	}
}