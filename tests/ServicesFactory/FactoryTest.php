<?php

namespace Tests\ObjectivePHP\ServicesFactory;


use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Factory;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

class FactoryTest extends TestCase
{

    /**
     * @var Factory
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new Factory();
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

        $this->instance->registerService($serviceSpecs);

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
        $factory = $this->getMock(Factory::class, ['resolveBuilder', 'getServiceSpecs']);
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
        $factory = $this->getMock(Factory::class, ['resolveBuilder', 'getServiceSpecs']);
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
        $factory = new Factory();

        $serviceSpecs = new ClassServiceSpecs('service.id', \Fancy\Service\TestService::class);
        $serviceSpecs->setParams(['param' => 'default']);
        $factory->registerService($serviceSpecs);

        $service1 = $factory->get('service.id');
        $service2 = $factory->get('service.id');

        $this->assertSame($service1, $service2);

        $service3 = $factory->get('service.id', ['param' => uniqid()]);

        $this->assertNotSame($service2, $service3);
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