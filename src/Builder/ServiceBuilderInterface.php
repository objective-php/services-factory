<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

interface ServiceBuilderInterface
{

    /**
     * Tells whether the builder can or not build a service according to a given service definition
     *
     * @param ServiceSpecsInterface $serviceDefinition
     *
     * @return bool
     */
    public function doesHandle(ServiceSpecsInterface $serviceDefinition);

    /**
     * Actually build the service
     *
     * @param ServiceSpecsInterface $serviceSpecs
     * @param null $params
     *
     * @return mixed
     */
    public function build(ServiceSpecsInterface $serviceSpecs, $params = null);

}
