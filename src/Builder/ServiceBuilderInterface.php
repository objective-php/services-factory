<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

interface ServiceBuilderInterface
{

    /**
     * Tells whether the builder can or not build a service according to a given service definition
     *
     * @param ServiceSpecificationInterface $serviceDefinition
     *
     * @return bool
     */
    public function doesHandle(ServiceSpecificationInterface $serviceDefinition);

    /**
     * Actually build the service
     *
     * @param ServiceSpecificationInterface $serviceSpecification
     * @param null $params
     *
     * @return mixed
     */
    public function build(ServiceSpecificationInterface $serviceSpecification, $params = null);

}
