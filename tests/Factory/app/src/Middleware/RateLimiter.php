<?php
namespace App\Middleware;

class RateLimiter
{
	public function handle($exe)
	{
		return $exe->text.'-foo-bar!';
	}
}