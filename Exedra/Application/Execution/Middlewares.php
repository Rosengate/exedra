<?php
namespace Exedra\Application\Execution;

/**
 * A collection of middleware
 */

class Middlewares extends \ArrayIterator
{
	/**
	 * Append a middleware to the collection.
	 * @param mixed middleware
	 */
	public function add($middleware)
	{
		$this->append($middleware);
	}

	/**
	 * Add middleware by array.
	 * @param array middlewares
	 */
	public function addByArray(array $middlewares)
	{
		foreach($middlewares as $middleware)
			$this->add($middleware);
	}

	/**
	 * Resolve middlewares all into usable callbacks.
	 * @param \Exedra\Application\Execution\Exec
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe)
	{
		$this->rewind();

		while($this->valid())
		{
			$middleware = $this->current();

			if(is_string($middleware))
			{
				if(strpos($middleware, ":") !== false)
				{
					$closure = $exe->loader->load($middleware);
					if($closure instanceof \Closure)
					{
						$this[$this->key()] = $closure;
					}
					else
					{
						return $exe->exception->create("The file located in '".$middleware."' must be a returned closure.");
					}
				}
				// has middleware builder.
				else if(strpos($middleware, "middleware=") === 0)
				{
					$middleware = str_replace("middleware=", "", $middleware);

					$atoms = explode("@", $middleware);
					
					$middleware = $atoms[0];

					// if no method was passed, will use handle as method name.
					$method = isset($atoms[1]) ? $atoms[1] : "handle";

					// create a handler.
					$this[$this->key()] = function($exe) use($middleware, $method) {
						return $exe->middleware->create($middleware)->$method($exe);};
				}
			}

			$this->next();
		}
	}
}