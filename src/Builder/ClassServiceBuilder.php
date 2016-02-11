<?php

namespace ObjectivePHP\ServicesFactory\Builder;

use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

class ClassServiceBuilder extends AbstractServiceBuilder
{

    /**
     * Service definition types this builder can handle
     *
     * @var array
     */
    protected $handledSpecs = [ClassServiceSpecs::class];


    /**
     * @param ClassServiceSpecs $serviceSpecs
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function build(ServiceSpecsInterface $serviceSpecs, $params = [])
    {

        // check compatibility with the service definition
        if (!$this->doesHandle($serviceSpecs))
        {
            throw new Exception(sprintf('"%s" service definition is not handled by this builder.', get_class($serviceSpecs)), Exception::INCOMPATIBLE_SERVICE_DEFINITION);
        }

        $serviceClassName = $serviceSpecs->getClass();


        // check class existence
        if(!class_exists($serviceClassName))
        {
            throw new Exception(sprintf('Unable to build service: class "%s" is unknown', $serviceClassName), Exception::INVALID_SERVICE_SPECS);
        }

        // merge service defined and runtime params
        $constructorParams = clone Collection::cast($params);
        $constructorParams->add($serviceSpecs->getParams());

        // substitute params with referenced services
        $this->substituteReferences($constructorParams);

        $service = new $serviceClassName(...$constructorParams->values());

        // call setters if any
        if($setters = $serviceSpecs->getSetters())
        {
            foreach($setters as $setter => $setterParams)
            {
                $instanceSetterParams = clone Collection::cast($setterParams);
                $this->substituteReferences($instanceSetterParams);

                $service->$setter(...$instanceSetterParams->values());
            }
        }

        return $service;
    }

}
