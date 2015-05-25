<?php

    namespace ObjectivePHP\ServicesFactory\Definition;


    interface ServiceDefinitionInterface
    {

        public function getServiceId();

        public function getAliases();

        public function getClassName();

        /**
         * @return \ObjectivePHP\Primitives\Collection
         */
        public function getParams();

        /**
         * Tells whether a new service instance should be instantiated each time it's requested or not
         *
         * @return boolean
         */
        public function isShared();

    }