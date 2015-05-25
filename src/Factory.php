<?php
    namespace ObjectivePHP\ServicesFactory;

    use ObjectivePHP\Primitives\Collection;
    use ObjectivePHP\Primitives\String;
    use ObjectivePHP\ServicesFactory\Builder\FactoryAwareInterface;
    use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
    use ObjectivePHP\ServicesFactory\Definition\ServiceDefinitionInterface;

    class Factory
    {

        /**
         * @var Collection
         */
        protected $services;

        protected $builders;

        public function __construct()
        {
            $this->services = (new Collection())->restrictTo(ServiceDefinitionInterface::class);
            $this->builders = new Collection();
        }

        /**
         * @param      $service string      Service ID or class name
         * @param null $params
         */
        public function get($service, $params = null)
        {

            $serviceDefinition = $this->getServiceDefinition($service);

            $builder = $this->resolveBuilder($serviceDefinition);

            if($builder instanceof FactoryAwareInterface)
            {
                $builder->setFactory($this);
            }

            return $builder->build($serviceDefinition, $params);

        }

        /**
         * @param ServiceDefinitionInterface $serviceDefinition
         */
        public function registerService(ServiceDefinitionInterface $serviceDefinition)
        {
            $serviceId = String::cast($serviceDefinition->getServiceId())->lower()->getInternalValue();
            $this->services[$serviceId] = $serviceDefinition;
        }

        /**
         * @param $serviceId
         *
         * @return ServiceDefinitionInterface
         */
        public function getServiceDefinition($serviceId)
        {
            return @$this->services[$serviceId] ?: null;
        }

        /**
         * @param $serviceId
         *
         * @return bool
         */
        public function isServiceRegistered($serviceId)
        {
            return isset($this->services[$serviceId]);
        }

        /**
         * @param ServiceBuilderInterface $builder
         */
        public function registerBuilder(ServiceBuilderInterface $builder)
        {
            // append new builder
            $this->builders[] = $builder;
        }

        public function resolveBuilder(ServiceDefinitionInterface $serviceDefinition)
        {

            /** @var ServiceBuilderInterface $builder */
            foreach($this->getBuilders() as $builder)
            {
                if($builder->doesHandle($serviceDefinition)) return $builder;
            }

            return null;
        }

        /**
         * @return array
         */
        public function getBuilders()
        {
            return $this->builders;
        }

        /**
         * @return array
         */
        public function getServices()
        {
            return $this->services;
        }

    }