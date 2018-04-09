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
    public function build(ServiceSpecificationInterface $serviceSpecification, $params = [], $actualServiceId = null)
    {

        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecification)) {
            throw new ServicesFactoryException(sprintf('"%s" service definition is not handled by this builder.',
                get_class($serviceSpecification)), ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION);
        }


        // get the actual factory
        $factory = $serviceSpecification->getFactory();

        // merge service defined and runtime params
        $params = clone Collection::cast([$actualServiceId, $this->getServicesFactory()] + $params);
        $params = $params->values()->toArray();

        // substitute params with referenced services
        if ($this->getServicesFactory()->hasConfig()) {
            $params = $this->getServicesFactory()->getConfig()->processParameters($params);
        }

        $service = $factory(...$params);


        return $service;
    }

}
