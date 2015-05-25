<?php

    namespace ObjectivePHP\ServicesFactory\Builder;


    use ObjectivePHP\ServicesFactory\Definition\ServiceDefinitionInterface;

    interface ServiceBuilderInterface
    {

        /**
         * Tells whether the builder can or not build a service according to a given service definition
         *
         * @param ServiceDefinitionInterface $serviceDefinition
         *
         * @return bool
         */
        public function doesHandle(ServiceDefinitionInterface $serviceDefinition);

        /**
         * Actually build the service
         *
         * @param ServiceDefinitionInterface $serviceDefinition
         * @param null                       $params
         *
         * @return mixed
         */
        public function build(ServiceDefinitionInterface $serviceDefinition, $params = null);

    }