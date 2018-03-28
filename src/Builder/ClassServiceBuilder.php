<?php

namespace ObjectivePHP\ServicesFactory\Builder;

use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

class ClassServiceBuilder extends AbstractServiceBuilder
{

    /**
     * Service definition types this builder can handle
     *
     * @var array
     */
    protected $handledSpecs = [ClassServiceSpecification::class];


    /**
     * @param ClassServiceSpecification $serviceSpecs
     * @param array $params
     * @return mixed
     * @throws ServicesFactoryException
     */
    public function build(ServiceSpecificationInterface $serviceSpecs, $params = [], $serviceId = null)
    {

        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecs)) {
            throw new ServicesFactoryException(sprintf('"%s" service definition is not handled by this builder.', get_class($serviceSpecs)), ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION);
        }

        $serviceClassName = $serviceSpecs->getClass();


        // check class existence
        if (!class_exists($serviceClassName)) {
            throw new ServicesFactoryException(sprintf('Unable to build service: class "%s" is unknown', $serviceClassName), ServicesFactoryException::INVALID_SERVICE_SPECS);
        }

        // merge service defined and runtime params
        $constructorParams = clone Collection::cast($params);
        $constructorParams->add($serviceSpecs->getConstructorParams());

        // substitute params with referenced services
        $this->substituteReferences($constructorParams);

        $service = new $serviceClassName(...$constructorParams->values());

        // call setters if any
        if ($setters = $serviceSpecs->getSetters()) {
            foreach ($setters as $setter => $setterParams) {
                $instanceSetterParams = clone Collection::cast($setterParams);

                $this->substituteReferences($instanceSetterParams);

                $service->$setter(...$instanceSetterParams->values());
            }
        }

        return $service;
    }

}
