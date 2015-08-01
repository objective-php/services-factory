<?php

    namespace Tests\ObjectivePHP\ServicesFactory;


    use ObjectivePHP\PHPUnit\TestCase;
    use ObjectivePHP\Primitives\Collection;
    use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
    use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
    use ObjectivePHP\ServicesFactory\Definition\ClassServiceDefinition;
    use ObjectivePHP\ServicesFactory\Definition\ServiceDefinitionInterface;
    use ObjectivePHP\ServicesFactory\Factory;

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

            $this->assertAttributeEquals(Collection::cast([$builder]), 'builders', $this->instance);

        }

        public function testBuilderResolverDoesNotMatchUnknownServicesDefinition()
        {
            $serviceDefinition      = $this->getMock(ServiceDefinitionInterface::class);

            $builder = $this->getMockBuilder(ServiceBuilderInterface::class)
                            ->setMethods(['doesHandle', 'build'])
                            ->getMock();

            $builder->expects($this->once())->method('doesHandle')
                    ->with($serviceDefinition)->willReturn(false);


            $this->instance->registerBuilder($builder);

            // no match
            $resolvedBuilder = $this->instance->resolveBuilder($serviceDefinition);
            $this->assertNull($resolvedBuilder);
        }

        public function testBuilderResolverDoesMatchKnownServicesDefinition()
        {
            // match
            $serviceDefinition = $this->getMock('Fancy\Service\Definition', [], ['service.test']);
            $builder = $this->getMockBuilder(ServiceBuilderInterface::class)
                            ->setMethods(['doesHandle', 'build'])
                            ->getMock();

            $builder->expects($this->once())->method('doesHandle')
                    ->with($serviceDefinition)->willReturn(true);

            $this->instance->registerBuilder($builder);

            $resolvedBuilder        = $this->instance->resolveBuilder($serviceDefinition);
            $this->assertSame($builder, $resolvedBuilder);
        }

        public function testServiceRegistration()
        {
            // default behaviour
            $serviceDefinition = new ClassServiceDefinition('service.id', 'stdClass');

            $this->instance->registerService($serviceDefinition);

            $this->assertAttributeEquals(Collection::cast(['service.id' => $serviceDefinition]), 'services', $this->instance);

            // service name normalization
            $otherServiceDefinition = new ClassServiceDefinition('oTHer.SERVICE.iD', 'stdClass');

            $this->instance->registerService($otherServiceDefinition);

            $this->assertAttributeCount(2, 'services', $this->instance);
            $this->assertAttributeContainsOnly(ServiceDefinitionInterface::class, 'services', $this->instance);
            $this->assertAttributeContains($otherServiceDefinition, 'services', $this->instance);

            $this->assertEquals(['service.id', 'other.service.id'], array_keys($this->instance->getServices()
                                                                                              ->getInternalValue()));

            $this->assertSame($otherServiceDefinition, $this->instance->getServiceDefinition('other.service.id'));
        }


        public function testFactoryInjectItselfIntoBuilder()
        {
            $factory = $this->getMock(Factory::class, ['resolveBuilder', 'getServiceDefinition']);
            $builder = $this->getMock(ClassServiceBuilder::class, ['setFactory', 'build']);
            $serviceDefinition = new ClassServiceDefinition('service.test', 'stdClass');

            $factory->expects($this->once())->method('resolveBuilder')->with($serviceDefinition)->willReturn($builder);
            $factory->expects($this->once())->method('getServiceDefinition')->with('service.test')->willReturn($serviceDefinition);
            $builder->expects($this->once())->method('setFactory')->with($factory);
            $builder->expects($this->once())->method('build')->with($serviceDefinition);

            $factory->get('service.test');

        }

        public function testFactoryReturnSameInstanceIfDefinitionTellsSo()
        {
            $factory = $this->getMock(Factory::class, ['resolveBuilder', 'getServiceDefinition']);
            $builder = $this->getMock(ClassServiceBuilder::class, ['setFactory', 'build']);
            $serviceDefinition = new ClassServiceDefinition('service.test', 'stdClass');

            $factory->expects($this->once())->method('resolveBuilder')->with($serviceDefinition)->willReturn($builder);
            $factory->expects($this->once())->method('getServiceDefinition')->with('service.test')->willReturn($serviceDefinition);
            $builder->expects($this->once())->method('setFactory')->with($factory);
            $builder->expects($this->once())->method('build')->with($serviceDefinition);

            $factory->get('service.test');

        }

    }

    /*************************
     * HELPER CLASSES
     ************************/

    namespace Fancy\Service;

    use ObjectivePHP\ServicesFactory\Definition\AbstractServiceDefinition;

    class Definition extends AbstractServiceDefinition
    {

        protected $id;

        public function getId()
        {
            return $this->id;
        }

    }