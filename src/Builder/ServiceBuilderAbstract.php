<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Reference;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

abstract class ServiceBuilderAbstract implements ServiceBuilderInterface
{

    /**
     * @var ServicesFactory
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
     * @return ServicesFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param ServicesFactory $factory
     *
     * @return $this
     */
    public function setFactory(ServicesFactory $factory)
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