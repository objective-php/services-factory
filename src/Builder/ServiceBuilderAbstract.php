<?php

    namespace ObjectivePHP\ServicesFactory\Builder;


    use ObjectivePHP\Primitives\Collection;
    use ObjectivePHP\ServicesFactory\Definition\ServiceDefinitionInterface;

    abstract class ServiceBuilderAbstract implements ServiceBuilderInterface
    {

        /**
         * This property should be initialized in extended classes
         *
         * @var Collection
         */
        protected $handledDefinitions;

        public function __construct()
        {
            $this->handledDefinitions = new Collection($this->handledDefinitions);
        }

        public function getHandledDefinitions()
        {
            return $this->handledDefinitions;
        }

        public function doesHandle(ServiceDefinitionInterface $serviceDefinition)
        {
            foreach ($this->getHandledDefinitions() as $handledDefinition)
            {
                if ($serviceDefinition instanceof $handledDefinition)
                {
                    return true;
                }
            }

            return false;
        }

    }