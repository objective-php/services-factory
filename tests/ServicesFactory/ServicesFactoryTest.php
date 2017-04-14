<?php

namespace Tests\ObjectivePHP\ServicesFactory;


use Fancy\Service\AnnotatedServiceDefiningIncompleteDependencyDefinition;
use Fancy\Service\AnnotatedServiceDefiningInvalidDependency;
use Fancy\Service\AnnotatedServiceDefiningSetter;
use Fancy\Service\AnnotatedServiceReferringNotExistingService;
use Fancy\Service\BadlyAnnotatedService;
use Fancy\Service\DelegateContainer;
use Fancy\Service\DependencyClass;
use Fancy\Service\SimpleAnnotatedService;
use Fancy\Service\SimpleAnnotatedServiceReferringAnotherService;
use Fancy\Service\SimpleAnnotatedServiceWitImplicitDependency;
use Fancy\Service\TestService;
use ObjectivePHP\Invokable\InvokableInterface;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
use ObjectivePHP\ServicesFactory\ServiceReference;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;
use Zend\ServiceManager\ServiceManager;

class FactoryTest extends TestCase
{
    
    /**
     * @var ServicesFactory
     */
    protected $instance;
    
    public function setUp()
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
        $serviceSpecs = $this->getMockBuilder(ServiceSpecsInterface::class)->getMock();
        
        // no match
        $resolvedBuilder = $this->instance->resolveBuilder($serviceSpecs);
        $this->assertNull($resolvedBuilder);
    }
    
    public function testBuilderResolverDoesMatchKnownServicesSpecs()
    {
        // match
        $serviceSpecs = $this->getMockBuilder('Fancy\Service\Specs')->setConstructorArgs(['service.test'])->getMock();
        $builder      = $this->getMockBuilder(ServiceBuilderInterface::class)
                             ->setMethods(['doesHandle', 'build'])
                             ->getMock()
        ;
        
        $builder->expects($this->once())->method('doesHandle')
                ->with($serviceSpecs)->willReturn(true)
        ;
        
        $this->instance->registerBuilder($builder);
        
        $resolvedBuilder = $this->instance->resolveBuilder($serviceSpecs);
        $this->assertSame($builder, $resolvedBuilder);
    }
    
    public function testServiceRegistration()
    {
        // default behaviour
        $serviceSpecs = new ClassServiceSpecs('service.id', 'stdClass');
        
        $setterReturn = $this->instance->registerService($serviceSpecs);
        $this->assertSame($this->instance, $setterReturn);
        
        $this->assertEquals(['service.id' => $serviceSpecs], $this->instance->getServices()->toArray());
        
        // service name normalization
        $otherServiceSpecs = new ClassServiceSpecs('oTHer.SERVICE.iD', 'stdClass');
        
        $this->instance->registerService($otherServiceSpecs);
        
        $this->assertAttributeCount(2, 'services', $this->instance);
        $this->assertAttributeContainsOnly(ServiceSpecsInterface::class, 'services', $this->instance);
        $this->assertAttributeContains($otherServiceSpecs, 'services', $this->instance);
        
        $this->assertEquals(['service.id', 'other.service.id'], array_keys($this->instance->getServices()
                                                                                          ->getInternalValue()));
        
        $this->assertSame($otherServiceSpecs, $this->instance->getServiceSpecs('other.service.id'));
    }
    
    
    public function testFactoryInjectItselfIntoBuilder()
    {
        $factory      = $this->getMockBuilder(ServicesFactory::class)->setMethods(['resolveBuilder', 'getServiceSpecs'])
                             ->getMock()
        ;
        $builder      = $this->getMockBuilder(ClassServiceBuilder::class)->setMethods(['setServicesFactory', 'build'])
                             ->getMock()
        ;
        $serviceSpecs = new ClassServiceSpecs('service.test', 'stdClass');
        
        $factory->expects($this->once())->method('resolveBuilder')->with($serviceSpecs)->willReturn($builder);
        $factory->expects($this->once())->method('getServiceSpecs')->with('service.test')->willReturn($serviceSpecs);
        $builder->expects($this->once())->method('setServicesFactory')->with($factory);
        $builder->expects($this->once())->method('build')->with($serviceSpecs);
        
        $factory->get('service.test');
        
    }
    
    public function testFactoryReturnSameInstanceIfSpecsTellsSo()
    {
        $factory      = $this->getMockBuilder(ServicesFactory::class)->setMethods(['resolveBuilder', 'getServiceSpecs'])
                             ->getMock()
        ;
        $builder      = $this->getMockBuilder(ClassServiceBuilder::class)->setMethods(['setServicesFactory', 'build'])
                             ->getMock()
        ;
        $serviceSpecs = new ClassServiceSpecs('service.test', 'stdClass');
        
        $factory->expects($this->exactly(2))->method('getServiceSpecs')->with('service.test')
                ->willReturn($serviceSpecs)
        ;
        $factory->expects($this->once())->method('resolveBuilder')->with($serviceSpecs)->willReturn($builder);
        $builder->expects($this->once())->method('setServicesFactory')->with($factory);
        $builder->expects($this->once())->method('build')->with($serviceSpecs)->willReturn(new \stdClass());
        
        $service    = $factory->get('service.test');
        $serviceBis = $factory->get('service.test');
        
        $this->assertSame($service, $serviceBis);
        
    }
    
    public function testStaticServiceStateIsIgnoredWhenParamsArePassedToFactory()
    {
        $factory = new ServicesFactory();
        
        $serviceSpecs = new ClassServiceSpecs('service.id', \Fancy\Service\TestService::class);
        $serviceSpecs->setParams(['param' => 'default']);
        $factory->registerService($serviceSpecs);
        
        $service1 = $factory->get('service.id');
        $service2 = $factory->get('service.id');
        
        $this->assertSame($service1, $service2);
        
        $service3 = $factory->get('service.id', ['param' => uniqid()]);
        
        $this->assertNotSame($service2, $service3);
    }
    
    public function testRegisterServiceFailsWithAnExceptionWhenInvalidSpecsArePassed()
    {
        $this->expectsException(function ()
        {
            $factory = new ServicesFactory();
            $factory->registerService('this is not a valid service spec');
        }, Exception::class, null, Exception::INVALID_SERVICE_SPECS);
    }
    
    public function testFactoryFailsWithExceptionWhenRequestingUnregisteredService()
    {
        $factory = new ServicesFactory();
        
        $this->expectsException(function () use ($factory)
        {
            $factory->get('this is not a registered service id');
        }, ServiceNotFoundException::class, 'matches no registered service in this factory', ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
    }
    
    public function testIsServiceRegistered()
    {
        $factory = new ServicesFactory();
        
        $serviceSpecs = new ClassServiceSpecs('service.id', \Fancy\Service\TestService::class);
        $serviceSpecs->setParams(['param' => 'default']);
        $factory->registerService($serviceSpecs);
        
        $this->assertTrue($factory->isServiceRegistered('service.id'));
        $this->assertFalse($factory->isServiceRegistered(uniqid(uniqid())));
        
    }
    
    public function testNonCallableInjectorsAreCastedToInvokableInstance()
    {
        $factory = (new ServicesFactory())->registerInjector('any_injector_class');
        
        $this->assertInstanceOf(InvokableInterface::class, $factory->getInjectors()[0]);
    }
    
    public function testCallableInjectorsAreKeptAsIs()
    {
        $factory = (new ServicesFactory())->registerInjector($injector = function ()
        {
        });
        
        $this->assertSame($injector, $factory->getInjectors()[0]);
    }
    
    
    public function testInjectorsAreRunOnInstanceBuilding()
    {
        $service      = new \stdClass();
        $serviceSpecs = new PrefabServiceSpecs('service.id', $service);
        
        $factory = (new ServicesFactory())->registerService($serviceSpecs);
        
        $injector = $this->getMockBuilder(InvokableInterface::class)->getMock();
        
        $injector->expects($this->once())->method('__invoke')->with($service, $factory, $serviceSpecs);
        $factory->registerInjector($injector);
        
        $factory->get('service.id');
    }
    
    public function testInjectionForANonLocallyBuiltInstance()
    {
        $service = new \stdClass();
        
        $factory = new ServicesFactory();
        
        $injector = $this->getMockBuilder(InvokableInterface::class)->getMock();
        
        $injector->expects($this->once())->method('__invoke')->with($service, $factory, null);
        $factory->registerInjector($injector);
        
        $factory->injectDependencies($service);
    }
    
    public function testFactoryCanRegisterServicesFromRawSpecifications()
    {
        // class
        $rawSpecs = [
            'id'    => 'service.id',
            'class' => 'Service\Class',
        ];
        
        $factory = new ServicesFactory();
        $factory->registerRawService($rawSpecs);
        
        $this->assertEquals(AbstractServiceSpecs::factory($rawSpecs), $factory->getServices()['service.id']);
    }
    
    public function testRegisterServiceRoutesToRegisterRawServiceIfReceivesAnArray()
    {
        $rawServiceSpecs = ['id' => 'service.id', 'instance' => 'test'];
        $factory         = new ServicesFactory();
        
        $factory->registerService($rawServiceSpecs);
        
        $this->assertInstanceOf(PrefabServiceSpecs::class, $factory->getServices()['service.id']);
    }
    
    public function testRegisterMultipleServicesAtOnce()
    {
        $factory = new ServicesFactory();
        
        $firstService = $this->getMockBuilder(ServiceSpecsInterface::class)->getMock();
        $firstService->method('getId')->willReturn('service.first');
        
        $secondService = $this->getMockBuilder(ServiceSpecsInterface::class)->getMock();
        $secondService->method('getId')->willReturn('service.second');
        
        
        $factory->registerService($firstService, $secondService);
        
        $this->assertCount(2, $factory->getServices());
    }
    
    public function testFactoryCannotOverridePreviouslyRegisteredFinalService()
    {
        
        $service = (new ClassServiceSpecs('service.id', \Fancy\Service\TestService::class))->setFinal();
        
        $this->instance->registerService($service);
        
        $this->expectsException(
            function ()
            {
                $this->instance->registerService(new PrefabServiceSpecs('service.id', $this));
            }
            , Exception::class, null, Exception::FINAL_SERVICE_OVERRIDING_ATTEMPT);
        
    }
    
    public function testUndefinedServicesSpecsCanBeFetchedUsingWildcard()
    {
        $servicesFactory = new ServicesFactory();
        $servicesFactory->registerService(['id' => 'service.*']);
        
        $specs = $servicesFactory->getServiceSpecs('service.test');
        
        $this->assertInstanceOf(ServiceSpecsInterface::class, $specs);
        $this->assertEquals('service.test', (string) $specs->getId());
        
        // should work at least twice...
        $otherSpecs = $servicesFactory->getServiceSpecs('service.other');
        
        $this->assertInstanceOf(ServiceSpecsInterface::class, $otherSpecs);
        $this->assertEquals('service.other', (string) $otherSpecs->getId());
        
        // both specs should not be the same instance
        $this->assertNotSame($otherSpecs, $specs);
    }
    
    public function testAnnotatedDependenciesGetInjectedUSingReflection()
    {
        
        $factory = new ServicesFactory();
        
        $service = new SimpleAnnotatedService();
        
        $factory->injectDependencies($service);
        
        $this->assertAttributeInstanceOf(DependencyClass::class, 'dependency', $service);
        
    }
    
    public function testAnnotatedDependenciesGetInjectedUSingReflectionAndVarTag()
    {
        
        $factory = new ServicesFactory();
        
        $service = new SimpleAnnotatedServiceWitImplicitDependency();
        
        $factory->injectDependencies($service);
        
        $this->assertAttributeInstanceOf(DependencyClass::class, 'dependency', $service);
        
    }
    
    public function testAnnotatedDependenciesGetInjectedUSingSetter()
    {
        
        $factory = new ServicesFactory();
        
        $service = new AnnotatedServiceDefiningSetter();
        
        $factory->injectDependencies($service);
        
        $this->assertAttributeInstanceOf(DependencyClass::class, 'dependency', $service);
        
    }
    
    public function testAnnotatedDependenciesWithoutSpecifyingClassNameThrowsAnException()
    {
        
        $factory = new ServicesFactory();
        
        $service = new AnnotatedServiceDefiningInvalidDependency();
        
        $this->expectException(Exception::class);
        $factory->injectDependencies($service);
    }
    
    public function testAnnotatedDependenciesWithoutSpecifyingClassOrServiceNameThrowsAnException()
    {
        
        $factory = new ServicesFactory();
        
        $service = new AnnotatedServiceDefiningIncompleteDependencyDefinition();
        
        $this->expectException(Exception::class);
        $this->expectExceptionCode(Exception::MISSING_DEPENDENCY_DEFINITION);
        $factory->injectDependencies($service);
    }
    
    public function testAnnotatedDependencyReferringNotExistingServiceThrowsAnException()
    {
        
        $factory = new ServicesFactory();
        
        $service = new AnnotatedServiceReferringNotExistingService();
        
        $this->expectException(Exception::class);
        $this->expectExceptionCode(Exception::DEPENDENCY_NOT_FOUND);
        
        $factory->injectDependencies($service);
    }
    
    public function testAnnotatedDependenciesGetInjectedUsingReflectionAndServiceReference()
    {
        $dependency = new DependencyClass();
        $factory    = new ServicesFactory();
        $factory->registerService(['id' => 'other.service', 'instance' => $dependency]);
        
        $service = new SimpleAnnotatedServiceReferringAnotherService();
        
        $factory->injectDependencies($service);
        
        $this->assertAttributeSame($dependency, 'dependency', $service);
        
    }
    
    public function testAnnotatedServiceLackingInjectionAnnotationProviderDoesNotGetInjected()
    {
        $factory = new ServicesFactory();
        
        $service = new BadlyAnnotatedService();
        
        $factory->injectDependencies($service);
        
        $this->assertAttributeEquals(null, 'dependency', $service);
    }
    
    public function testHasService()
    {
        $factory = new ServicesFactory();
        $this->assertFalse($factory->has('unregistered.service'));
        
        $spec = $this->getMockForAbstractClass(AbstractServiceSpecs::class, ['registered.service']);
        $factory->registerService($spec);
        
        $this->assertTrue($factory->has('registered.service'));
    }
    
    public function testGettingServiceSpecsUsingServiceReference()
    {
        $factory = new ServicesFactory();
        $spec    = $this->getMockForAbstractClass(AbstractServiceSpecs::class, ['service.id']);
        
        $factory->registerService($spec);
        
        $this->assertSame($spec, $factory->getServiceSpecs(new ServiceReference('service.id')));
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
        $delegate->registerService(new PrefabServiceSpecs('test', $service));

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


}

/*************************
 * HELPER CLASSES
 ************************/

namespace Fancy\Service;

use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Annotation\Inject;
use ObjectivePHP\ServicesFactory\Specs\InjectionAnnotationProvider;

class Specs extends AbstractServiceSpecs
{
    
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
    
}

class TestService
{
    public function __construct($id)
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
    public function getDependency(): TestService
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
    
}

class DependencyClass
{
    
}

class TestInjector
{
    
    function __invoke()
    {
        // TODO: Implement __invoke() method.
    }
    
}

class DelegateContainer extends ServicesFactory
{

}
