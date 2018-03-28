<?php

namespace ObjectivePHP\ServicesFactory\Builder;

use ObjectivePHP\Invokable\Exception as InvokableException;
use ObjectivePHP\Invokable\Invokable;
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
     * @param DelegatedFactorySpecification $serviceSpecs
     * @param array                 $params
     *
     * @return mixed
     * @throws ServicesFactoryException
     */
    public function build(ServiceSpecificationInterface $serviceSpecs, $params = [], $actualServiceId = null)
    {
        
        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecs)) {
            throw new ServicesFactoryException(sprintf('"%s" service definition is not handled by this builder.',
                get_class($serviceSpecs)), ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION);
        }
        
        
        // check class existence
        try {
            /** @var Invokable $factory */
            $factory = $serviceSpecs->getFactory();
            if ($servicesFactory = $this->getServicesFactory()) {
                $factory->setServicesFactory($servicesFactory);
            }
            $factory = $factory->getCallable();
        } catch (InvokableException $e) {
            throw new ServicesFactoryException(sprintf('Unable to build service: provided factory is not callable'),
                ServicesFactoryException::INVALID_SERVICE_SPECS, $e);
        }
        
        // merge service defined and runtime params
        $constructorParams = clone Collection::cast([$actualServiceId, $this->getServicesFactory()] + $params);
        $constructorParams->add($serviceSpecs->getParams());
        
        // substitute params with referenced services
        $this->substituteReferences($constructorParams);
        
        $service = $factory(...$constructorParams->values());
        
        
        return $service;
    }
    
}
