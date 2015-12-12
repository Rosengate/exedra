<?php
namespace Exedra\Application\Builder;

/**
 * Simple application exception builder.
 */
class Exception
{
	/**
	 * @var \Exedra\Application\Application app
	 */
	protected $app;

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Create exception message.
	 * @param string message
	 */
	public function create($message)
	{
		throw new \Exedra\Application\Exception\Exception($message);		
	}
}