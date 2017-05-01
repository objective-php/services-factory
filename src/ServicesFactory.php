<?php
    namespace ObjectivePHP\ServicesFactory;

    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\Common\Annotations\AnnotationRegistry;
    use Interop\Container\ContainerInterface;
    use ObjectivePHP\Config\Config;
    use ObjectivePHP\Invokable\Invokable;
    use ObjectivePHP\Matcher\Matcher;
    use ObjectivePHP\Primitives\Collection\Collection;
    use ObjectivePHP\Primitives\String\Str;
    use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
    use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
    use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
    use ObjectivePHP\ServicesFactory\Exception\Exception;
    use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
    use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
    use ObjectivePHP\ServicesFactory\Specs\InjectionAnnotationProvider;
    use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;
    use phpDocumentor\Reflection\DocBlockFactory;

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
         * @var AnnotationReader
         */
        protected $annotationsReader;

        /**
         * @var array
         */
        protected $delegateContainers = [];

        /**
         * ServicesFactory constructor.
         */
        public function __construct()
        {
            // init collections
            $this->services  = (new Collection())->restrictTo(ServiceSpecsInterface::class);
            $this->builders  = (new Collection())->restrictTo(ServiceBuilderInterface::class);
            $this->injectors = new Collection();
            $this->instances = new Collection();

            // register default annotation reader
            AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Inject.php');
            $this->setAnnotationsReader(new AnnotationReader());

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
                foreach($this->delegateContainers as $delegate)
                {
                    if($instance = $delegate->get($service))
                    {
                        $this->injectDependencies($instance);
                        return $instance;
                    }
                }

                throw new ServiceNotFoundException(sprintf('Service reference "%s" matches no registered service in this factory or its delegate containers', $service), ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
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
         * @return Collection
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

            $has = (bool) $this->getServiceSpecs($service);

            if(!$has)
            {
                foreach($this->getDelegateContainers() as $container)
                {
                    $has = $container->has($service);
                    if($has) break;
                }
            }

            return $has;

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

            if(!is_callable($injector))
            {
                // turn injector to Invokable if it is not a native callable
                $injector = Invokable::cast($injector);
            }

            $this->injectors[] = $injector;

            return $this;
        }

        /**
         * @return AnnotationReader
         */
        public function getAnnotationsReader()
        {
            return $this->annotationsReader;
        }

        /**
         * @param AnnotationReader $annotationsReader
         */
        public function setAnnotationsReader(AnnotationReader $annotationsReader)
        {
            $this->annotationsReader = $annotationsReader;
            
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
            if(is_object($instance))
            {
                // call injectors if any
                $this->getInjectors()->each(function ($injector) use ($instance, $serviceSpecs)
                {
                    $injector($instance, $this, $serviceSpecs);
                });

                if($instance instanceof InjectionAnnotationProvider)
                {
                    // automated injections
                    $reflectedInstance = new \ReflectionObject($instance);
                    $reflectedProperties = $reflectedInstance->getProperties();

                    foreach($reflectedProperties as $reflectedProperty)
                    {
                        $injection = $this->getAnnotationsReader()->getPropertyAnnotation($reflectedProperty, Annotation\Inject::class);
                        if($injection)
                        {
                            if($injection->param) {
                                if($this->has('config'))
                                {
                                    $config = $this->get('config');

                                    if($config instanceof Config)
                                    {
                                        $params = $config->subset('ObjectivePHP\Application\Config\Param');

                                        if($params->has($injection->param)) {
                                            $dependency = $params->get($injection->param);
                                        } else {
                                            if(isset($injection->default)){
                                                $dependency = $injection->default;
                                            } else {
                                                throw new Exception(sprintf('Config instance registered as "config" does not have a "%s" param, and no default value is provided', $injection->param));
                                            }
                                        }

                                    } else {
                                        throw new Exception('Service registered as "config" in this factory is no a Config instance');
                                    }
                                } else {
                                    throw new Exception('No Config is registered as "config" in this factory');
                                }
                            }
                            else if($injection->class || !$injection->service)
                            {
                                $className = $injection->getDependency();

                                if(!$className)
                                {
                                    // use phpdocumentor to get var type
                                    $docblock = DocBlockFactory::createInstance()->create($reflectedProperty);
                                    if($docblock->hasTag('var'))
                                    {
                                        $className = (string) $docblock->getTagsByName('var')[0]->getType()->getFqsen();
                                    } else
                                    {
                                        throw new Exception('Undefined dependency. Use either dependency="<className>|<serviceName>" or "@var $property ClassName"', Exception::MISSING_DEPENDENCY_DEFINITION);
                                    }
                                }

                                $dependency = new $className;
                                $this->injectDependencies($dependency);
                            } else
                            {
                                $serviceName = $injection->getDependency();
                                if(!$this->has($serviceName))
                                {
                                    throw new Exception(sprintf('Dependent service "%s" is not registered', $serviceName), Exception::DEPENDENCY_NOT_FOUND);
                                }
                                $dependency = $this->get($serviceName);
                            }

                            if($injection->setter)
                            {
                                $setter = $injection->setter;
                                $instance->$setter($dependency);
                            } else
                            {
                                if(!$reflectedProperty->isPublic())
                                {
                                    $reflectedProperty->setAccessible(true);
                                }

                                $reflectedProperty->setValue($instance, $dependency);

                                if(!$reflectedProperty->isPublic())
                                {
                                    $reflectedProperty->setAccessible(false);
                                }
                            }
                        }
                    }
                }
            }
            
            return $this;
        }

        /**
         * @param ContainerInterface $delegate
         * @return $this
         */
        public function registerDelegateContainer(ContainerInterface $delegate)
        {
            $this->delegateContainers[] = $delegate;

            return $this;
        }

        /**
         * @return array
         */
        public function getDelegateContainers()
        {
            return $this->delegateContainers;
        }


    }
