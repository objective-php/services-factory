<?php

    namespace ObjectivePHP\ServicesFactory\Builder;


    use ObjectivePHP\Primitives\Collection\Collection;
    use ObjectivePHP\ServicesFactory\ServicesFactory;
    use ObjectivePHP\ServicesFactory\ServiceReference;
    use ObjectivePHP\ServicesFactory\ServicesFactoryAwareInterface;
    use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;
    use ObjectivePHP\ServicesFactory\ServicesFactoryAwareTrait;

    /**
     * Class AbstractServiceBuilder
     *
     * @package ObjectivePHP\ServicesFactory\Builder
     */
    abstract class AbstractServiceBuilder implements ServiceBuilderInterface, ServicesFactoryAwareInterface
    {

        use ServicesFactoryAwareTrait;

        /**
         * This property should be initialized in extended classes
         *
         * @var Collection
         */
        protected $handledSpecs;

        /**
         * AbstractServiceBuilder constructor.
         */
        public function __construct()
        {
            $this->handledSpecs = new Collection($this->handledSpecs);
        }

        /**
         * @param ServiceSpecsInterface $serviceDefinition
         *
         * @return bool
         */
        public function doesHandle(ServiceSpecsInterface $serviceDefinition)
        {
            foreach ($this->getHandledSpecs() as $handledDefinition)
            {
                if ($serviceDefinition instanceof $handledDefinition)
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * @return Collection
         */
        public function getHandledSpecs()
        {
            return $this->handledSpecs;
        }

        /**
         * Substitute all references to services in a param set
         *
         * @param Collection $params
         *
         * @return Collection
         */
        protected function substituteReferences(Collection $params)
        {
            $params->each(function (&$value)
            {
                if ($value instanceof ServiceReference)
                {
                    $value = $this->getServicesFactory()->get($value->getId());
                }
            });
        }

        /**
         * @return ServicesFactory
         */
        public function getServicesFactory()
        {
            return $this->servicesFactory;
        }
    }
