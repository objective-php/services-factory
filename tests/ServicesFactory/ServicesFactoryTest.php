<?php

namespace Tests\ObjectivePHP\ServicesFactory;


use ObjectivePHP\Events\EventsHandler;
use ObjectivePHP\Invokable\Invokable;
use ObjectivePHP\Invokable\InvokableInterface;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

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
        $builder = $this->getMock(ServiceBuilderInterface::class);

        $this->instance->registerBuilder($builder);

        $registeredBuilders = $this->instance->getBuilders()->toArray();
        $this->assertSame($builder, array_pop($registeredBuilders));

    }

    public function testBuilderResolverDoesNotMatchUnknownServicesSpecs()
    {
        $serviceSpecs = $this->getMock(ServiceSpecsInterface::class);

        // no match
        $resolvedBuilder = $this->instance->resolveBuilder($serviceSpecs);
        $this->assertNull($resolvedBuilder);
    }

    public function testBuilderResolverDoesMatchKnownServicesSpecs()
    {
        // match
        $serviceSpecs = $this->getMock('Fancy\Service\Specs', [], ['service.test']);
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
        $factory = $this->getMock(ServicesFactory::class, ['resolveBuilder', 'getServiceSpecs']);
        $builder = $this->getMock(ClassServiceBuilder::class, ['setServicesFactory', 'build']);
        $serviceSpecs = new ClassServiceSpecs('service.test', 'stdClass');

        $factory->expects($this->once())->method('resolveBuilder')->with($serviceSpecs)->willReturn($builder);
        $factory->expects($this->once())->method('getServiceSpecs')->with('service.test')->willReturn($serviceSpecs);
        $builder->expects($this->once())->method('setServicesFactory')->with($factory);
        $builder->expects($this->once())->method('build')->with($serviceSpecs);

        $factory->get('service.test');

    }

    public function testFactoryReturnSameInstanceIfSpecsTellsSo()
    {
        $factory = $this->getMock(ServicesFactory::class, ['resolveBuilder', 'getServiceSpecs']);
        $builder = $this->getMock(ClassServiceBuilder::class, ['setServicesFactory', 'build']);
        $serviceSpecs = new ClassServiceSpecs('service.test', 'stdClass');

        $factory->expects($this->exactly(2))->method('getServiceSpecs')->with('service.test')->willReturn($serviceSpecs);
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
        $this->expectsException(function() {
            $factory = new ServicesFactory();
            $factory->registerService('this is not a valid service spec');
        }, Exception::class, null, Exception::INVALID_SERVICE_SPECS);
    }

    public function testFactoryFailsWithExceptionWhenRequestingUnregisteredService()
    {
        $factory = new ServicesFactory();

        $this->expectsException(function() use($factory) {
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

    public function testInjectorsAreRunOnInstanceBuilding()
    {
        $service = new \stdClass();
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
        $factory = new ServicesFactory();

        $factory->registerService($rawServiceSpecs);

        $this->assertInstanceOf(PrefabServiceSpecs::class, $factory->getServices()['service.id']);
    }

    public function testRegisterMultipleServicesAtOnce()
    {
        $factory = new ServicesFactory();

        $firstService = $this->getMock(ServiceSpecsInterface::class);
        $firstService->method('getId')->willReturn('service.first');

        $secondService = $this->getMock(ServiceSpecsInterface::class);
        $secondService->method('getId')->willReturn('service.second');


        $factory->registerService($firstService, $secondService);

        $this->assertCount(2, $factory->getServices());
    }

    public function testFactoryCannotOverridePreviouslyRegisteredFinalService()
    {

        $service = (new ClassServiceSpecs('service.id', \Fancy\Service\TestService::class))->setFinal();

        $this->instance->registerService($service);

        $this->expectsException(
            function() {
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
}

/*************************
 * HELPER CLASSES
 ************************/

namespace Fancy\Service;

use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;

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


    class TestInjector {

        function __invoke()
        {
            // TODO: Implement __invoke() method.
        }

    }
