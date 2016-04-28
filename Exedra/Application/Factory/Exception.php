<?php
namespace Exedra\Application\Factory;

/**
 * Simple application exception factory.
 */
class Exception
{
	/**
	 * @var \Exedra\Application app
	 */
	protected $app;

	public function __construct(\Exedra\Application $app)
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