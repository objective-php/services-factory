<?php

namespace Tests\ObjectivePHP\ServicesFactory {


    use Codeception\Test\Unit;
    use Fancy\Service\AnnotatedServiceDefiningIncompleteDependencyDefinition;
    use Fancy\Service\AnnotatedServiceDefiningInvalidDependency;
    use Fancy\Service\AnnotatedServiceDefiningSetter;
    use Fancy\Service\AnnotatedServiceReferringNotExistingService;
    use Fancy\Service\AutowiredWithDefaultValues;
    use Fancy\Service\BadlyAnnotatedService;
    use Fancy\Service\DelegateContainer;
    use Fancy\Service\DependencyClass;
    use Fancy\Service\PartiallyAutowired;
    use Fancy\Service\SimpleAnnotatedService;
    use Fancy\Service\SimpleAnnotatedServiceReferringAnotherService;
    use Fancy\Service\SimpleAnnotatedServiceWitImplicitDependency;
    use Fancy\Service\SimpleRunner;
    use Fancy\Service\SomeClass;
    use Fancy\Service\SomeClassDependingOnServicesFactory;
    use Fancy\Service\SomeClassWithDependency;
    use Fancy\Service\SomeOtherClass;
    use Fancy\Service\SomeService;
    use Fancy\Service\TestService;
    use ObjectivePHP\Config\Config;
    use ObjectivePHP\Config\Directive\AbstractScalarDirective;
    use ObjectivePHP\Config\Directive\ScalarDirective;
    use ObjectivePHP\ServicesFactory\Annotation\Inject;
    use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
    use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
    use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
    use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
    use ObjectivePHP\ServicesFactory\Injector\InjectorInterface;
    use ObjectivePHP\ServicesFactory\ParameterProcessor\ServiceReferenceParameterProcessor;
    use ObjectivePHP\ServicesFactory\ServicesFactory;
    use ObjectivePHP\ServicesFactory\Specification\AbstractServiceSpecification;
    use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
    use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;
    use ObjectivePHP\ServicesFactory\Specification\PrefabServiceSpecification;
    use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;
    use Zend\ServiceManager\ServiceManager;

    class ServicesFactoryTest extends Unit
    {

        /**
         * @var ServicesFactory
         */
        protected $instance;

        public function setUp(): void
        {
            $this->instance = new ServicesFactory();

        }

        public function testBuilderRegistration()
        {
            $builder = $this->getMockBuilder(ServiceBuilderInterface::class)->getMock();

            $this->instance->registerBuilder($builder);

            $registeredBuilders = $this->instance->getBuilders()->toArray();
            $this->assertSame($builder, array_pop($registeredBuilders));

        }

        public function testBuilderResolverDoesNotMatchUnknownServicesSpecs()
        {
            $serviceSpecs = $this->getMockBuilder(ServiceSpecificationInterface::class)->getMock();

            // no match
            $resolvedBuilder = $this->instance->resolveBuilder($serviceSpecs);
            $this->assertNull($resolvedBuilder);
        }

        public function testBuilderResolverDoesMatchKnownServicesSpecs()
        {
            // match
            $serviceSpecs = $this->getMockBuilder('Fancy\Service\Specification')->setConstructorArgs(['service.test'])
                ->getMock();
            $builder = $this->getMockBuilder(ServiceBuilderInterface::class)
                ->setMethods(['doesHandle', 'build'])
                ->getMock();

            $builder->expects($this->once())->method('doesHandle')
                ->with($serviceSpecs)->willReturn(true);

            $this->instance->registerBuilder($builder);

            $resolvedBuilder = $this->instance->resolveBuilder($serviceSpecs);
            $this->assertSame($builder, $resolvedBuilder);
        }

        public function testServiceRegistration()
        {
            // default behaviour
            $serviceSpecs = new ClassServiceSpecification('service.id', 'stdClass');

            $setterReturn = $this->instance->registerService($serviceSpecs);
            $this->assertSame($this->instance, $setterReturn);

            $this->assertSame($serviceSpecs, $this->instance->getServiceSpecification('service.id'));

            // service name normalization
            $otherServiceSpecs = new ClassServiceSpecification('oTHer.SERVICE.iD', 'stdClass');

            $this->instance->registerService($otherServiceSpecs);

            $this->assertContains('service.id', array_keys($this->instance->getServices()
                ->getInternalValue()));
            $this->assertContains('other.service.id', array_keys($this->instance->getServices()
                ->getInternalValue()));

            $this->assertSame($otherServiceSpecs, $this->instance->getServiceSpecification('other.service.id'));
        }

        public function testServiceIdNormalization()
        {
            $serviceSpecs = new ClassServiceSpecification(TestService::class, TestService::class);
            $this->instance->registerService($serviceSpecs);

            $this->assertTrue($this->instance->has(TestService::class));

            $this->assertInstanceOf(TestService::class, $this->instance->get(TestService::class));
        }


        public function testFactoryReturnSameInstanceIfSpecsTellsSo()
        {
            $factory = $this->getMockBuilder(ServicesFactory::class)->setMethods([
                'resolveBuilder',
                'getServiceSpecification'
            ])
                ->getMock();
            $builder = $this->getMockBuilder(ClassServiceBuilder::class)->setMethods([
                'setServicesFactory',
                'build'
            ])
                ->getMock();
            $serviceSpecs = new ClassServiceSpecification('service.test', 'stdClass');

            $factory->expects($this->exactly(2))->method('getServiceSpecification')->with('service.test')
                ->willReturn($serviceSpecs);
            $factory->expects($this->once())->method('resolveBuilder')->with($serviceSpecs)->willReturn($builder);
            $builder->expects($this->once())->method('setServicesFactory')->with($factory);
            $builder->expects($this->once())->method('build')->with($serviceSpecs)->willReturn(new \stdClass());

            $service = $factory->get('service.test');
            $serviceBis = $factory->get('service.test');

            $this->assertSame($service, $serviceBis);

        }

        public function testStaticServiceStateIsIgnoredWhenParamsArePassedToFactory()
        {
            $factory = new ServicesFactory();

            $serviceSpecs = new ClassServiceSpecification('service.id', \Fancy\Service\TestService::class);
            $serviceSpecs->setConstructorParams(['param' => 'default']);
            $factory->registerService($serviceSpecs);

            $service1 = $factory->get('service.id');
            $service2 = $factory->get('service.id');

            $this->assertSame($service1, $service2);

            $service3 = $factory->get('service.id', ['param' => uniqid()]);

            $this->assertNotSame($service2, $service3);
        }

        public function testRegisterServiceFailsWithAnExceptionWhenInvalidSpecsArePassed()
        {
            $this->expectException(ServicesFactoryException::class);
            $this->expectExceptionCode(ServicesFactoryException::INVALID_SERVICE_SPECS);
            $factory = new ServicesFactory();
            $factory->registerService('this is not a valid service spec');
        }

        public function testFactoryFailsWithExceptionWhenRequestingUnregisteredService()
        {
            $factory = new ServicesFactory();

            $this->expectException(ServiceNotFoundException::class);
            $this->expectExceptionCode(ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
            $factory->get('this is not a registered service id');
        }

        public function testIsServiceRegistered()
        {
            $factory = new ServicesFactory();

            $serviceSpecs = new ClassServiceSpecification('service.id', \Fancy\Service\TestService::class);
            $factory->registerService($serviceSpecs);

            $this->assertTrue($factory->isServiceRegistered('service.id'));
            $this->assertFalse($factory->isServiceRegistered(uniqid(uniqid())));

        }


        public function testInjectorsAreRunOnInstanceBuilding()
        {
            $service = new \stdClass();
            $serviceSpecification = new PrefabServiceSpecification('service.id', $service);

            $factory = (new ServicesFactory())->registerService($serviceSpecification);

            $injector = $this->getMockBuilder(InjectorInterface::class)->getMock();

            $injector->expects($this->once())->method('injectDependencies')->with($service, $factory,
                $serviceSpecification);
            $factory->registerInjector($injector);

            $factory->get('service.id');
        }

        public function testInjectionForANonLocallyBuiltInstance()
        {
            $service = new \stdClass();

            $factory = new ServicesFactory();

            $injector = $this->getMockBuilder(InjectorInterface::class)->getMock();

            $injector->expects($this->once())->method('injectDependencies')->with($service, $factory, null);
            $factory->registerInjector($injector);

            $factory->injectDependencies($service);
        }

        public function testFactoryCanRegisterServicesFromRawSpecifications()
        {
            // class
            $rawSpecs = [
                'id' => 'service.id',
                'class' => TestService::class
            ];

            $factory = new ServicesFactory();
            $factory->registerRawService($rawSpecs);

            $this->assertEquals(AbstractServiceSpecification::factory($rawSpecs),
                $factory->getServices()['service.id']);
        }

        public function testRegisterServiceRoutesToRegisterRawServiceIfReceivesAnArray()
        {
            $rawServiceSpecs = ['id' => 'service.id', 'instance' => 'test'];
            $factory = new ServicesFactory();

            $factory->registerService($rawServiceSpecs);

            $this->assertInstanceOf(PrefabServiceSpecification::class, $factory->getServices()['service.id']);
        }

        public function testRegisterMultipleServicesAtOnce()
        {
            $factory = new ServicesFactory();

            $firstService = $this->getMockBuilder(ServiceSpecificationInterface::class)->getMock();
            $firstService->method('getId')->willReturn('service.first');
            $firstService->method('getAutoAliases')->willReturn([]);

            $secondService = $this->getMockBuilder(ServiceSpecificationInterface::class)->getMock();
            $secondService->method('getId')->willReturn('service.second');
            $secondService->method('getAutoAliases')->willReturn([]);


            $factory->registerService($firstService, $secondService);

            $this->assertCount(3, $factory->getServices());
        }

        public function testFactoryCannotOverridePreviouslyRegisteredFinalService()
        {

            $service = (new ClassServiceSpecification('service.id', \Fancy\Service\TestService::class))->setFinal();

            $this->instance->registerService($service);

            $this->expectException(ServicesFactoryException::class);
            $this->expectExceptionCode(ServicesFactoryException::FINAL_SERVICE_OVERRIDING_ATTEMPT);
            $this->instance->registerService(new PrefabServiceSpecification('service.id', $this));

        }

        public function testAnnotatedDependenciesGetInjectedUSingReflection()
        {

            $factory = new ServicesFactory();

            $service = new SimpleAnnotatedService();

            $factory->injectDependencies($service);

            $this->assertInstanceOf(DependencyClass::class, $service->getDependency());

        }

        public function testAnnotatedDependenciesGetInjectedUSingReflectionAndVarTag()
        {

            $factory = new ServicesFactory();

            $service = new SimpleAnnotatedServiceWitImplicitDependency();

            $factory->injectDependencies($service);

            $this->assertInstanceOf(DependencyClass::class, $service->getDependency());

        }

        public function testAnnotatedDependenciesGetInjectedUsingSetter()
        {

            $factory = new ServicesFactory();

            $service = new AnnotatedServiceDefiningSetter();

            $factory->injectDependencies($service);

            $this->assertInstanceOf(DependencyClass::class, $service->getDependency());

        }

        public function testAnnotatedDependenciesWithoutSpecifyingClassNameThrowsAnException()
        {

            $factory = new ServicesFactory();

            $service = new AnnotatedServiceDefiningInvalidDependency();

            $this->expectException(ServicesFactoryException::class);
            $factory->injectDependencies($service);
        }

        public function testAnnotatedDependenciesWithoutSpecifyingClassOrServiceNameThrowsAnException()
        {

            $factory = new ServicesFactory();

            $service = new AnnotatedServiceDefiningIncompleteDependencyDefinition();

            $this->expectException(ServicesFactoryException::class);
            $this->expectExceptionCode(ServicesFactoryException::MISSING_DEPENDENCY_DEFINITION);
            $factory->injectDependencies($service);
        }

        public function testAnnotatedDependencyReferringNotExistingServiceThrowsAnException()
        {

            $factory = new ServicesFactory();

            $service = new AnnotatedServiceReferringNotExistingService();

            $this->expectException(ServicesFactoryException::class);
            $this->expectExceptionCode(ServicesFactoryException::DEPENDENCY_NOT_FOUND);

            $factory->injectDependencies($service);
        }

        public function testAnnotatedDependenciesGetInjectedUsingReflectionAndServiceReference()
        {
            $dependency = new DependencyClass();
            $factory = new ServicesFactory();
            $factory->registerService(['id' => 'other.service', 'instance' => $dependency]);

            $service = new SimpleAnnotatedServiceReferringAnotherService();

            $factory->injectDependencies($service);

            $this->assertSame($dependency, $service->getDependency());

        }

        public function testAnnotatedServiceLackingInjectionAnnotationProviderDoesNotGetInjected()
        {
            $factory = new ServicesFactory();

            $service = new BadlyAnnotatedService();

            $factory->injectDependencies($service);

            $this->assertEquals(null, $service->getDependency());
        }

        public function testAnnotatedParamInjection()
        {
            $factory = new ServicesFactory();
            $config = (new Config())
                ->registerDirective(new class extends AbstractScalarDirective {
                    protected $key = 'param.test';
                })
                ->set('param.test', 'param.value');
            $factory->registerService(['id' => 'config', 'instance' => $config]);

            $service = new class implements InjectionAnnotationProvider {
                /**
                 * @Inject(param="param.test", default="test")
                 */
                protected $property;

                /**
                 * @return mixed
                 */
                public function getProperty()
                {
                    return $this->property;
                }


            };

            $factory->injectDependencies($service);
            $this->assertEquals('param.value', $service->getProperty());
        }

        public function testHasService()
        {
            $factory = new ServicesFactory();
            $this->assertFalse($factory->has('unregistered.service'));

            $spec = $this->getMockForAbstractClass(AbstractServiceSpecification::class, ['registered.service']);
            $factory->registerService($spec);

            $this->assertTrue($factory->has('registered.service'));
        }

        public function testGettingServiceSpecsUsingServiceReference()
        {
            $factory = new ServicesFactory();
            $spec = $this->getMockForAbstractClass(AbstractServiceSpecification::class, ['service.id']);

            $factory->registerService($spec);

            $this->assertSame($spec, $factory->getServiceSpecification('service.id'));
        }

        public function testDelegateContainerRegistration()
        {
            $factory = new ServicesFactory();
            $delegate = new DelegateContainer();

            $factory->registerDelegateContainer($delegate);

            $this->assertSame($delegate, $factory->getDelegateContainers()[0]);
        }

        public function testDelegateContainerLookup()
        {
            $factory = new ServicesFactory();

            $delegate = new DelegateContainer();
            $service = new \stdClass();
            $delegate->registerService(new PrefabServiceSpecification('test', $service));

            $factory->registerDelegateContainer($delegate);

            $this->assertTrue($factory->has('test'));
            $this->assertSame($service, $factory->get('test'));
        }

        public function testLookingUpUndefinedServiceInDelegateContainers()
        {
            $factory = new ServicesFactory();
            $delegate = new DelegateContainer();

            $factory->registerDelegateContainer($delegate);

            $this->expectException(ServiceNotFoundException::class);
            $this->expectExceptionCode(ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
            $factory->get('test');
        }

        public function testInjectingDependenciesInServiceComingFromDelegateContainers()
        {
            $factory = new ServicesFactory();
            $factory->registerService(['id' => 'other.service', 'instance' => new TestService('test.service')]);

            $delegate = new ServiceManager();
            $delegate->setService('test', new SimpleAnnotatedServiceReferringAnotherService());

            $factory->registerDelegateContainer($delegate);

            $service = $factory->get('test');

            $this->assertInstanceOf(TestService::class, $service->getDependency());
        }

        public function testUnregisteredServiceMatchingExistingClassIsAutoRegistered()
        {
            $factory = new ServicesFactory();
            $this->assertTrue($factory->has(TestService::class));
        }

        public function testFetchingServiceUsingAlias()
        {
            $factory = new ServicesFactory();
            $factory->registerService(['id' => 'test.service', 'class' => TestService::class, 'static' => true]);

            $this->assertTrue($factory->has(TestService::class));

            $this->assertInstanceOf(TestService::class, $instance = $factory->get(TestService::class));
            $this->assertSame($factory->get('test.service'), $factory->get(TestService::class));

        }

        public function testAutoAliasing()
        {
            $factory = new ServicesFactory();

            $factory->registerService(['id' => 'test.service.1', 'class' => TestService::class]);
            $this->assertSame($factory->get('test.service.1'), $factory->get(TestService::class));

            $factory->registerService(['id' => 'test.service.2', 'class' => TestService::class]);

            $this->assertSame($factory->get('test.service.2'), $factory->get(TestService::class));

        }

        public function testAutoAliasingDoesNotCollideWithFinal()
        {
            $factory = new ServicesFactory();

            $factory->registerService(['id' => 'test.service.1', 'class' => TestService::class, 'final' => true]);
            $this->assertSame($factory->get('test.service.1'), $factory->get(TestService::class));

            $factory->registerService(['id' => 'test.service.2', 'class' => TestService::class]);

            $this->assertSame($factory->get('test.service.1'), $factory->get(TestService::class));

        }

        public function testPartialAutowiring()
        {

            $factory = new ServicesFactory();

            $factory->registerService(new ClassServiceSpecification('test', PartiallyAutowired::class,
                ['this is the scalar property value']));

            $this->assertEquals('this is the scalar property value', $factory->get('test')->getScalar());
            $this->assertInstanceOf(DependencyClass::class, $factory->get('test')->getDependency());

        }

        public function testPartialAutowiringWithDefaultValues()
        {

            $factory = new ServicesFactory();

            $factory->registerService(new ClassServiceSpecification('test', AutowiredWithDefaultValues::class));

            $this->assertEquals([], $factory->get('test')->getParam());

        }

        public function testAutorun()
        {
            $factory = new ServicesFactory();
            $injectedDependency = new DependencyClass();

            $factory->registerService(['id' => 'dependency', 'instance' => $injectedDependency]);

            $runner = new SimpleRunner();

            $dependency = $factory->autorun($runner, 'runWithMixedParams', ['test']);

            $this->assertSame($injectedDependency, $dependency);

            $this->assertEquals($dependency->property, 'test');

        }


        public function testConfigParametersAutowiring()
        {
            $factory = new ServicesFactory();

            $config = new Config();
            $config->registerDirective(new ScalarDirective('api.token', 'xyz'));
            $factory->setConfig($config);

            $service = new SimpleRunner();

            $result = $factory->autorun([$service, 'runWithConfigParameter']);

            $this->assertEquals('xyz', $result);

        }

        public function testFurtherAutowireHints()
        {
            // won't be autoloaded otherwise
            new SomeOtherClass();

            $factory = new ServicesFactory();
            $factory->registerService(new ClassServiceSpecification(SomeClass::class, SomeClass::class));
            $factory->getConfig()->registerParameterProcessor((new ServiceReferenceParameterProcessor())->setServicesFactory($factory));
            $someService = $factory->get(SomeService::class);
            $this->assertInstanceOf(SomeClass::class, $factory->autorun([$someService, 'someMethod']));
            $someOtherService = $factory->get(SomeOtherClass::class);
            $this->assertSame($someOtherService, $factory->autorun([$someService, 'someOtherMethod']));


        }

        public function testServicesFactoryInjectsItselfWhenAutowiringServicesFactory()
        {
            $factory = new ServicesFactory();

            $this->assertSame($factory, $factory->get(ServicesFactory::class));
        }
    }
}

/*************************
 * HELPER CLASSES
 ************************/

namespace Fancy\Service {

    use ObjectivePHP\ServicesFactory\Annotation\AutowireHint;
    use ObjectivePHP\ServicesFactory\Annotation\Inject;
    use ObjectivePHP\ServicesFactory\Injector\InjectorInterface;
    use ObjectivePHP\ServicesFactory\ServicesFactory;
    use ObjectivePHP\ServicesFactory\Specification\AbstractServiceSpecification;
    use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;
    use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

    class Specification extends AbstractServiceSpecification
    {

        protected $id;

        public function getId()
        {
            return $this->id;
        }

    }

    class TestService
    {
        public function __construct($id = null)
        {
            $this->id = $id;
        }
    }

    class SimpleAnnotatedService implements InjectionAnnotationProvider
    {

        /**
         * @Inject(class="Fancy\Service\DependencyClass")
         * @var DependencyClass
         */
        protected $dependency;

        public function getDependency()
        {
            return $this->dependency;
        }

    }

    class SimpleAnnotatedServiceReferringAnotherService implements InjectionAnnotationProvider
    {

        /**
         * @Inject(service="other.service")
         * @var TestService
         */
        protected $dependency;

        /**
         * @return TestService
         */
        public function getDependency()
        {
            return $this->dependency;
        }


    }

    class SimpleAnnotatedServiceWitImplicitDependency implements InjectionAnnotationProvider
    {

        /**
         * @Inject
         * @var \Fancy\Service\DependencyClass
         */
        protected $dependency;

        public function getDependency()
        {
            return $this->dependency;
        }

    }

    class AnnotatedServiceDefiningSetter implements InjectionAnnotationProvider
    {

        /**
         * @Inject(class="Fancy\Service\DependencyClass", setter="setDependency")
         * @var DependencyClass
         */
        protected $dependency;

        /**
         * @param DependencyClass $dependency
         */
        public function setDependency($dependency)
        {
            $this->dependency = $dependency;
        }

        public function getDependency()
        {
            return $this->dependency;
        }

    }

    class AnnotatedServiceDefiningInvalidDependency implements InjectionAnnotationProvider
    {

        /**
         * @Inject(setter="setDependency")
         */
        protected $dependency;

        /**
         * @param DependencyClass $dependency
         */
        public function setDependency($dependency)
        {
            $this->dependency = $dependency;
        }

    }

    class AnnotatedServiceReferringNotExistingService implements InjectionAnnotationProvider
    {

        /**
         * @Inject(service="not.existing.service")
         */
        protected $dependency;

        /**
         * @param DependencyClass $dependency
         */
        public function setDependency($dependency)
        {
            $this->dependency = $dependency;
        }

    }

    class AnnotatedServiceDefiningIncompleteDependencyDefinition implements InjectionAnnotationProvider
    {

        /**
         * @Inject()
         */
        protected $dependency;

        /**
         * @param DependencyClass $dependency
         */
        public function setDependency($dependency)
        {
            $this->dependency = $dependency;
        }

    }

    class BadlyAnnotatedService
    {
        /**
         * This won't be taken in account, because the class does not implements InjectionAnnotationProvider
         *
         * @Inject(class="Fancy\Service\DependencyClass", setter="setDependency")
         * @var DependencyClass
         */
        protected $dependency;

        public function getDependency()
        {
            return $this->dependency;
        }

    }

    class DependencyClass
    {
        public $property = '';
    }

    class TestInjector implements InjectorInterface
    {
        public function injectDependencies(
            $instance,
            ServicesFactory $servicesFactory,
            ServiceSpecificationInterface $serviceSpecification = null
        ) {

        }

    }

    class DelegateContainer extends ServicesFactory
    {

    }

    class SimpleRunner
    {
        public function run(DependencyClass $dependency)
        {
            return $dependency;
        }

        public function runWithMixedParams(string $test, DependencyClass $dependencyClass)
        {
            $dependencyClass->property = $test;
            return $dependencyClass;
        }

        /**
         * @param string $token API Token
         *
         * @AutowireHint(mapping={
         *     "tokenFromConfig"="param(api.token)",
         *     "dependencyFromServicesFactory" = "service(\stdClass)"
         * })
         * @return string
         */
        public function runWithConfigParameter($tokenFromConfig, \stdClass $dependencyFromServicesFactory)
        {
            return $tokenFromConfig;
        }
    }

    class PartiallyAutowired
    {
        protected $scalar;

        protected $dependency;

        /**
         * PartiallyAutowired constructor.
         * @param $scalar
         * @param $dependency
         */
        public function __construct($scalar, DependencyClass $dependency)
        {
            $this->scalar = $scalar;
            $this->dependency = $dependency;
        }

        /**
         * @return mixed
         */
        public function getScalar()
        {
            return $this->scalar;
        }

        /**
         * @param mixed $scalar
         */
        public function setScalar($scalar): void
        {
            $this->scalar = $scalar;
        }

        /**
         * @return mixed
         */
        public function getDependency()
        {
            return $this->dependency;
        }

        /**
         * @param mixed $dependency
         */
        public function setDependency($dependency): void
        {
            $this->dependency = $dependency;
        }

    }

    interface SomeInterface
    {

    }

    class SomeClass implements SomeInterface
    {

    }

    class SomeOtherClass implements SomeInterface
    {

    }

    class SomeService
    {

        public function someMethod(SomeInterface $dependency)
        {
            return $dependency;
        }


        /**
         * @param SomeInterface $dependency
         * @return SomeInterface
         *
         * @AutowireHint(mapping={"dependency"="service(\Fancy\Service\SomeOtherClass)"})
         */
        public function someOtherMethod(SomeInterface $dependency)
        {
            return $dependency;
        }

    }

    class AutowiredWithDefaultValues
    {

        protected $param;

        /**
         * AutowiredWithDefaultValues constructor.
         */
        public function __construct(array $param = [])
        {
            $this->param = $param;
        }

        /**
         * @return mixed
         */
        public function getParam()
        {
            return $this->param;
        }

    }

    class SomeClassDependingOnServicesFactory
    {
        /** @var DependencyClass */
        protected $factory;

        /**
         * SomeClassWithDependency constructor.
         * @param ServicesFactory $factory
         */
        public function __construct(ServicesFactory $factory)
        {
            $this->factory = $factory;
        }

        /**
         * @return DependencyClass
         */
        public function getFactory(): DependencyClass
        {
            return $this->factory;
        }

    }
}
