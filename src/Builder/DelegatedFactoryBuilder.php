<?php

namespace ObjectivePHP\ServicesFactory\Builder;

use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\Specification\DelegatedFactorySpecification;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

class DelegatedFactoryBuilder extends AbstractServiceBuilder
{
    /**
     * Service definition types this builder can handle
     *
     * @var array
     */
    protected $handledSpecs = [DelegatedFactorySpecification::class];

    /**
     * @param DelegatedFactorySpecification $serviceSpecification
     * @param array $params
     *
     * @return mixed
     * @throws ServicesFactoryException
     */
    public function build(ServiceSpecificationInterface $serviceSpecification, $params = [], string $serviceId = null)
    {
        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecification)) {
            throw new ServicesFactoryException(
                sprintf(
                    '"%s" service definition is not handled by this builder.',
                    get_class($serviceSpecification)
                ),
                ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION
            );
        }

        // get the actual factory
        $factory = $serviceSpecification->getFactory();

        // substitute params with referenced services
        if ($this->getServicesFactory()->hasConfig()) {
            $params = $this->getServicesFactory()->getConfig()->processParameters($params);
        }

        // merge service defined and runtime params
        $params = clone Collection::cast([$serviceId, $this->getServicesFactory()] + $params);
        $params = $params->values()->toArray();

        $service = $factory(...$params);

        return $service;
    }
}
