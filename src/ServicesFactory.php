<?php
    namespace ObjectivePHP\ServicesFactory;

    use Interop\Container\ContainerInterface;
    use ObjectivePHP\Invokable\Invokable;
    use ObjectivePHP\Invokable\InvokableInterface;
    use ObjectivePHP\Matcher\Matcher;
    use ObjectivePHP\Primitives\Collection\Collection;
    use ObjectivePHP\Primitives\String\Str;
    use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
    use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
    use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
    use ObjectivePHP\ServicesFactory\Exception\Exception;
    use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
    use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
    use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

    class ServicesFactory implements ContainerInterface
    {

        /**
         * @var Collection
         */
        protected $services;

        /**
         * @var Collection
         */
        protected $builders;

        /**
         * @var Collection
         */
        protected $instances;

        /**
         * @var Collection
         */
        protected $injectors;

        /**
         * ServicesFactory constructor.
         */
        public function __construct()
        {
            // init collections
            $this->services  = (new Collection())->restrictTo(ServiceSpecsInterface::class);
            $this->builders  = (new Collection())->restrictTo(ServiceBuilderInterface::class);
            $this->injectors = (new Collection())->restrictTo(InvokableInterface::class);
            $this->instances = new Collection();

            // load default builders
            $this->builders->append(new ClassServiceBuilder(), new PrefabServiceBuilder());
        }

        /**
         * @param            $service string      Service ID or class name
         * @param array|null $params
         *
         * @return mixed|null
         * @throws Exception
         */
        public function get($service, $params = [])
        {

            if ($service instanceof ServiceReference)
            {
                $service = $service->getId();
            }

            $serviceSpecs = $this->getServiceSpecs($service);

            if (is_null($serviceSpecs))
            {
                throw new ServiceNotFoundException(sprintf('Service reference "%s" matches no registered service in this factory', $service), ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
            }

            if (
                !$serviceSpecs->isStatic()
                || $this->getInstances()->lacks($service)
                || $params
            )
            {
                $builder = $this->resolveBuilder($serviceSpecs);

                if ($builder instanceof ServicesFactoryAwareInterface)
                {
                    $builder->setServicesFactory($this);
                }

                $instance = $builder->build($serviceSpecs, $params);;

                $this->injectDependencies($instance, $serviceSpecs);


                if (!$serviceSpecs->isStatic() || $params)
                {
                    // if params are passed, we don't store the instance for
                    // further reference, even if the service is static
                    return $instance;
                }
                else
                {
                    $this->instances[$service] = $instance;
                }

            }

            return $this->instances[$service];

        }

        /**
         * Proxy for isServiceRegistered()
         *
         * This method ensures ContainerInterface compliance
         *
         * @param string|ServiceReference $service
         *
         * @return bool
         */
        public function has($service)
        {
            return $this->isServiceRegistered($service);
        }

        /**
         * @param $service
         *
         * @return ServiceSpecsInterface
         */
        public function getServiceSpecs($service)
        {
            if ($service instanceof ServiceReference)
            {
                $service = $service->getId();
            }

            $specs = $this->services[$service] ?? null;

            if (is_null($specs))
            {
                $matcher = new Matcher();
                foreach ($this->services as $id => $specs)
                {
                    if ($matcher->match($service, $id))
                    {

                        return (clone $specs)->setId($service);
                        break;
                    }
                    $specs = null;
                }
            }

            return $specs;
        }

        /**
         * @return Collection
         */
        public function getInstances()
        {
            return $this->instances;
        }

        /**
         * @param ServiceSpecsInterface $serviceSpecs
         *
         * @return null|ServiceBuilderInterface
         */
        public function resolveBuilder(ServiceSpecsInterface $serviceSpecs)
        {

            /** @var ServiceBuilderInterface $builder */
            foreach ($this->getBuilders() as $builder)
            {
                if ($builder->doesHandle($serviceSpecs)) return $builder;
            }

            return null;
        }

        /**
         * @return Collection
         */
        public function getBuilders()
        {
            return $this->builders;
        }

        /**
         * @return $this|Collection
         */
        public function getInjectors()
        {
            return $this->injectors;
        }

        /**
         * @param string|ServiceReference $service
         *
         * @return bool
         */
        public function isServiceRegistered($service)
        {
            $service = ($service instanceof ServiceReference) ? $service->getId() : $service;

            return (bool) $this->getServiceSpecs($service);
        }

        /**
         *
         * @param array $servicesSpecs
         *
         * @return $this
         * @throws Exception
         */
        public function registerService(...$servicesSpecs)
        {
            foreach ($servicesSpecs as $serviceSpecs)
            {
                // if service specs is not an instance of ServiceSpecsInterface,
                // try to build the specs using factory
                if (!$serviceSpecs instanceof ServiceSpecsInterface)
                {
                    try
                    {
                        $serviceSpecs = AbstractServiceSpecs::factory($serviceSpecs);
                    } catch (\Exception $e)
                    {
                        throw new Exception(AbstractServiceSpecs::class . '::factory() was unable to build service specifications', Exception::INVALID_SERVICE_SPECS, $e);
                    }
                }

                if (!$serviceSpecs instanceof ServiceSpecsInterface)
                {
                    // the specs are still not valid
                    throw new Exception('Service specifications are not an instance of ' . ServiceSpecsInterface::class, Exception::INVALID_SERVICE_SPECS);
                }

                $serviceId = Str::cast($serviceSpecs->getId())->lower();

                // prevent final services from being overridden
                if ($previouslyRegistered = $this->getServiceSpecs((string) $serviceId))
                {
                    // a service with same name already has been registered
                    if ($previouslyRegistered->isFinal())
                    {
                        // as it is marked as final, it cannot be overridden
                        throw new Exception(sprintf('Cannot override service "%s" as it has been registered as a final service', $serviceId), Exception::FINAL_SERVICE_OVERRIDING_ATTEMPT);
                    }
                }

                // store the service specs for further reference
                $this->services[(string) $serviceId] = $serviceSpecs;
            }

            return $this;
        }

        public function registerRawService($rawServiceSpecs)
        {
            $specs = AbstractServiceSpecs::factory($rawServiceSpecs);
            $this->registerService($specs);

            return $this;
        }

        /**
         * @param ServiceBuilderInterface $builder
         */
        public function registerBuilder(ServiceBuilderInterface $builder)
        {
            // append new builder
            $this->builders[] = $builder;
        }

        /**
         * @return Collection
         */
        public function getServices()
        {
            return $this->services;
        }

        /**
         * @param $injector invokable
         *
         * @return $this
         */
        public function registerInjector($injector)
        {
            $this->injectors[] = Invokable::cast($injector);

            return $this;
        }

        /**
         * @param $instance
         * @param $serviceSpecs
         * @return $this
         * @throws \ObjectivePHP\Primitives\Exception
         */
        public function injectDependencies($instance, $serviceSpecs = null)
        {
            // call injectors if any
            $this->getInjectors()->each(function ($injector) use ($instance, $serviceSpecs)
            {
                $injector($instance, $this, $serviceSpecs);
            });

            return $this;
        }


    }
