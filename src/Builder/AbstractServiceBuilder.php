<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\ServicesFactoryAwareInterface;
use ObjectivePHP\ServicesFactory\ServicesFactoryAwareTrait;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

/**
 * Class AbstractServiceBuilder
 *
 * @package ObjectivePHP\ServicesFactory\Builder
 */
abstract class AbstractServiceBuilder implements ServiceBuilderInterface, ServicesFactoryAwareInterface
{

    use ServicesFactoryAwareTrait;

    /**
     * This property should be initialized in extended classes
     *
     * @var Collection
     */
    protected $handledSpecs;

    /**
     * AbstractServiceBuilder constructor.
     */
    public function __construct()
    {
        $this->handledSpecs = new Collection($this->handledSpecs);
    }

    /**
     * @param ServiceSpecificationInterface $serviceDefinition
     *
     * @return bool
     */
    public function doesHandle(ServiceSpecificationInterface $serviceDefinition)
    {
        foreach ($this->getHandledSpecs() as $handledDefinition) {
            if ($serviceDefinition instanceof $handledDefinition) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection
     */
    public function getHandledSpecs()
    {
        return $this->handledSpecs;
    }

    /**
     * @return ServicesFactory
     */
    public function getServicesFactory()
    {
        return $this->servicesFactory;
    }
}
