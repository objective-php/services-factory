<?php

namespace Tests\ObjectivePHP\ServicesFactory;


use ObjectivePHP\Events\EventsHandler;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Exception;
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

        /*
         *
         $builder = $this->getMockBuilder(ServiceBuilderInterface::class)
            ->setMethods(['doesHandle', 'build'])
            ->getMock();

        $builder->expects($this->once())->method('doesHandle')
            ->with($serviceSpecs)->willReturn(false);


        $this->instance->registerBuilder($builder);
        */

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

        $this->assertAttributeEquals(Collection::cast(['service.id' => $serviceSpecs]), 'services', $this->instance);

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
        $builder = $this->getMock(ClassServiceBuilder::class, ['setFactory', 'build']);
        $serviceSpecs = new ClassServiceSpecs('service.test', 'stdClass');

        $factory->expects($this->once())->method('resolveBuilder')->with($serviceSpecs)->willReturn($builder);
        $factory->expects($this->once())->method('getServiceSpecs')->with('service.test')->willReturn($serviceSpecs);
        $builder->expects($this->once())->method('setFactory')->with($factory);
        $builder->expects($this->once())->method('build')->with($serviceSpecs);

        $factory->get('service.test');

    }

    public function testFactoryReturnSameInstanceIfSpecsTellsSo()
    {
        $factory = $this->getMock(ServicesFactory::class, ['resolveBuilder', 'getServiceSpecs']);
        $builder = $this->getMock(ClassServiceBuilder::class, ['setFactory', 'build']);
        $serviceSpecs = new ClassServiceSpecs('service.test', 'stdClass');

        $factory->expects($this->exactly(2))->method('getServiceSpecs')->with('service.test')->willReturn($serviceSpecs);
        $factory->expects($this->once())->method('resolveBuilder')->with($serviceSpecs)->willReturn($builder);
        $builder->expects($this->once())->method('setFactory')->with($factory);
        $builder->expects($this->once())->method('build')->with($serviceSpecs);

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

    public function testFactoryFailsWithExceptionWhenRequestingUnregisteredService()
    {
        $factory = new ServicesFactory();

        $this->expectsException(function() use($factory) {
            $factory->get('this is not a registered service id');
        }, Exception::class, 'matches no registered service in this factory', Exception::UNREGISTERED_SERVICE_REFERENCE);
    }


    public function testEventIsTriggeredUponServiceBuilding()
    {
        $service = new \stdClass();
        $serviceSpecs = new PrefabServiceSpecs('service.id', $service);

        $factory = (new ServicesFactory())->registerService($serviceSpecs);

        $eventsHandler = $this->getMockBuilder(EventsHandler::class)->setMethods(['trigger'])->getMock();
        $eventsHandler->expects($this->once())->method('trigger')
            ->with(ServicesFactory::EVENT_INSTANCE_BUILT . '.service.id', $factory, ['serviceSpecs' => $serviceSpecs, 'instance' => $service]);

        $factory->setEventsHandler($eventsHandler);

        // check that factory actually injected itself to the EventsHandler
        $this->assertSame($factory, $eventsHandler->getServicesFactory());

        $factory->get('service.id');

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