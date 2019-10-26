<?php

namespace Exedra\Support\Wireman;

use Exedra\Support\Wireman\Resolvers\InstantiationResolver;
use Exedra\Support\Wireman\Contracts\ParamResolver;
use Exedra\Support\Wireman\Contracts\WiringResolver;
use Exedra\Support\Wireman\Exceptions\ParamResolveException;
use Exedra\Support\Wireman\Exceptions\WiringResolveException;
use Exedra\Support\Wireman\Resolvers\OptionalParamResolver;

class Wireman
{
    /**
     * @var WiringResolver[]
     */
    protected $wiringResolvers = [];

    /**
     * @var ParamResolver[]
     */
    protected $paramResolvers = [];

    public function __construct(array $wiringResolvers, array $paramResolvers)
    {
        $this->wiringResolvers = $wiringResolvers;
        $this->paramResolvers = $paramResolvers;
    }

    public static function createClassResolver()
    {
        return new static([$resolver = new InstantiationResolver()], [$resolver, new OptionalParamResolver()]);
    }

    /**
     * @param $name
     * @return mixed
     * @throws WiringResolveException
     */
    public function resolve($name)
    {
        foreach ($this->wiringResolvers as $resolver) {
            if ($resolver->canResolveWiring($name))
                return $resolver->resolveWiring($name, $this);
        }

        throw new WiringResolveException('Failed to resolve [' . is_string($name) ? $name : gettype($name) . '] wiring.');
    }

    /**
     * @param \ReflectionParameter[] $parameters
     * @return mixed[]
     * @throws ParamResolveException
     */
    public function resolveParameters(array $parameters)
    {
        $dependencies = [];

        foreach ($parameters as $param) {
            $dependencies[] = $this->resolveParam($param);
        }

        return $dependencies;
    }

    /**
     * @param \ReflectionParameter $param
     * @return mixed
     * @throws ParamResolveException
     */
    public function resolveParam(\ReflectionParameter $param)
    {
        foreach ($this->paramResolvers as $resolver) {
            if ($resolver->canResolveParam($param)) {
                return $resolver->resolveParam($param, $this);
            }
        }

        throw new ParamResolveException($param);
    }

    /**
     * Resolve method into an array of required dependencies
     * @param \ReflectionMethod|null $method
     * @return mixed[]
     */
    public function resolveMethod(\ReflectionMethod $method = null)
    {
        if (!$method)
            return [];

        return $this->resolveParameters($method->getParameters());
    }
}