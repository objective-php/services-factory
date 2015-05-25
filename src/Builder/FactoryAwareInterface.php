<?php

    namespace ObjectivePHP\ServicesFactory\Builder;


    use ObjectivePHP\ServicesFactory\Factory;

    interface FactoryAwareInterface
    {

        public function setFactory(Factory $factory);

        public function getFactory();

    }