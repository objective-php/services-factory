<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\Specification\PrefabServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

class PrefabServiceBuilder extends AbstractServiceBuilder
{

    /**
     * Service specification types this builder can handle
     *
     * @var array
     */
    protected $handledSpecs = [PrefabServiceSpecification::class];


    /**
     * @param PrefabServiceSpecification|ServiceSpecificationInterface $serviceSpecification
     * @param array $params Ignored for this service type
     *
     * @return mixed
     * @throws ServicesFactoryException
     */
    public function build(ServiceSpecificationInterface $serviceSpecification, $params = [], string $serviceId = null)
    {
        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecification)) {
            throw new ServicesFactoryException(sprintf('"%s" service spec is not handled by this builder.', get_class($serviceSpecification)), ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION);
        }

        return $serviceSpecification->getInstance();
    }

}
