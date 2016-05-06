<?php
namespace Exedra\Provider;

interface ProviderInterface
{
	public function register(\Exedra\Application $app);
}