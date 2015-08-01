<?php

    namespace ObjectivePHP\ServicesFactory\Definition;


    interface ServiceDefinitionInterface
    {

        /**
         * @return string Service identifier - must be unique
         */
        public function getId();

        /**
         * @return array Service aliases
         */
        public function getAliases();

        /**
         * Tells whether a new service instance should be instantiated each time it's requested or not
         *
         * @return boolean
         */
        public function isStatic();

    }