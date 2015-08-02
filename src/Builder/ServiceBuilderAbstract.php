<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Factory;
use ObjectivePHP\ServicesFactory\Reference;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

abstract class ServiceBuilderAbstract implements ServiceBuilderInterface
{

    /**
     * @var Factory
     */
    protected $factory;


    /**
     * This property should be initialized in extended classes
     *
     * @var Collection
     */
    protected $handledSpecs;

    public function __construct()
    {
        $this->handledSpecs = new Collection($this->handledSpecs);
    }

    public function getHandledSpecs()
    {
        return $this->handledSpecs;
    }

    public function doesHandle(ServiceSpecsInterface $serviceDefinition)
    {
        foreach ($this->getHandledSpecs() as $handledDefinition)
        {
            if ($serviceDefinition instanceof $handledDefinition)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param Factory $factory
     *
     * @return $this
     */
    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Subsitute all references to services in a param set
     *
     * @param Collection $params
     */
    protected function substituteReferences(Collection $params)
    {
        $params->each(function (&$value)
        {
            if ($value instanceof Reference)
            {
                $value = $this->getFactory()->get($value->getId());
            }
        });
    }
}