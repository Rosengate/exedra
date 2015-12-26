<?php
namespace Exedra\Application\Middleware\Handler;

/**
 * Handle loader based middleware, specified by type of load like below example :
 * class=Auth
 * - this handler will look for class middleware under application namespace + Middleware
 * - for example, it will look for App\Middleware\Auth
 * load=mymiddleware.php
 * - Will look for a file named mymiddleware.php under App/Middleware/
 * - Expect a returned \Closure from the file
 * route=backend.default
 * - Will look for a file named backend.default.php under App/Middleware/routes/
 * - Expect a returned \Closure from the file
 */
class Loader extends HandlerAbstract
{
	protected $type;

	protected $path;

	protected $loader;

	/**
	 * @return bool
	 */
	public function validate($pattern)
	{
		if(!is_string($pattern))
			return false;
		
		@list($type, $path) = explode('=', $pattern);

		if(!$path)
			return false;

		if(!$this->isAvailableType($type))
			return false;

		$this->loader = $this->exe->app->loader;
		$this->type = $type;
		$this->path = $path;

		return true;
	}

	protected function isAvailableType($type)
	{
		return in_array($type, array('class', 'load', 'route'));
	}

	public function resolve($class)
	{
		$method = 'resolve'.ucwords($this->type);

		return $this->$method($this->path);
	}

	public function resolveClass($path)
	{
		$class = $this->path;

		return function($exe) use($class)
		{
			return $exe->middleware->create($class)->handle($exe);
		};
	}

	public function resolveRoute()
	{
		$route = $this->path;

		$path = array('structure' => 'middleware', 'path' => 'routes/'.$route.'.php');

		if(!$this->loader->has($path))
			return $this->exe->exception->create('Unable to find route based middleware file '.$this->loader->buildPath($path));

		$closure = $this->loader->load($path);

		if(!($closure instanceof \Closure))
			return $this->exe->exception->create('The loaded file on path '.$path.' must return type \Closure');

		return $closure;
	}

	public function resolveLoad()
	{
		$path = array('structure' => 'middleware', 'path' => $this->path);

		if(!$this->loader->has($path))
			return $this->exe->exception->create('Unable to find middleware file '.$this->loader->buildPath($path));

		$closure = $this->loader->load($path);

		if(!($closure instanceof \Closure))
			return $this->exe->exception->create('The loaded file on path '.$path.' must return type \Closure');

		return $closure;
	}
}


?>