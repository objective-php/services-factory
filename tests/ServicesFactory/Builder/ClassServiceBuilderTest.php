<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Helpers\DependencyService;
use Helpers\TestService;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\ServiceReference;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\PrefabServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

class ClassServiceBuilderTest extends TestCase
{

    public function testBuildingUnsupportedServiceThrowsAnException()
    {
        $builder = new PrefabServiceBuilder();

        $serviceDefinition = $this->getMockBuilder(ServiceSpecsInterface::class)->getMock();

        $this->assertFalse($builder->doesHandle($serviceDefinition));

        $this->expectsException(function () use ($serviceDefinition, $builder)
        {
            $builder->build($serviceDefinition);
        }, Exception::class, null, Exception::INCOMPATIBLE_SERVICE_DEFINITION);

    }

    public function testSimpleServiceIsBuilt()
    {
        $builder = new ClassServiceBuilder();
        $serviceDefinition = new ClassServiceSpecs('test.service', TestService::class);

        $this->assertTrue($builder->doesHandle($serviceDefinition));

        $service = $builder->build($serviceDefinition);

        $this->assertInstanceOf(TestService::class, $service);

        $serviceDefinition->setParams(['first' => 'x', 'second' => 'y']);

        // add params to service definition
        $service = $builder->build($serviceDefinition);
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertAttributeEquals(Collection::cast(['arg1' => 'x', 'arg2' => 'y']), 'args', $service);

        // override service definition params at runtime
        $service = $builder->build($serviceDefinition, ['first' => 'OVERRIDDEN']);
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertAttributeEquals(Collection::cast(['arg1' => 'OVERRIDDEN', 'arg2' => 'y']), 'args', $service);

    }

    public function testClassBuilderCallsSetters()
    {
        $serviceSpecs = new ClassServiceSpecs('service.id', TestService::class);

        $serviceSpecs->setSetters(
            [
                'setOptionalDependency' => ['optional dependency value'],
                'setOtherOptionalDependency' => [new ServiceReference('other.service')]
            ]
        );

        $dependency = new \stdClass;
        $factory = $this->getMockBuilder(ServicesFactory::class)->getMock();
        $factory->expects($this->once())->method('get')->with('other.service')->willReturn($dependency);

        $builder = new ClassServiceBuilder();
        $builder->setServicesFactory($factory);

        $service = $builder->build($serviceSpecs);

        // test by transitivity
        $this->assertEquals('optional dependency value', $service->getOptionalDependency());
        $this->assertSame($dependency, $service->getOtherOptionalDependency());

    }

    public function testSimpleReferenceSubstitution()
    {

        $dependency = new \stdClass;

        $factory = $this->getMockBuilder(ServicesFactory::class)->getMock();
        $factory->expects($this->once())->method('get')->with('dependency.id')->willReturn($dependency);

        $builder = new ClassServiceBuilder();
        $builder->setServicesFactory($factory);

        $serviceDefinition = new ClassServiceSpecs('main.service', 'stdClass');
        $serviceDefinition->setParams(['dependency' => new ServiceReference('dependency.id')]);

        $builder->build($serviceDefinition);

    }
    
    /**
     * This test is not quite unit, but helped a lot pinpointing a very
     * twisted issue with static service references
     *
     * @throws Exception
     */
    public function testStaticServiceReferenceSubstitutedByNewInstance()
    {
        $dependencyDefinition = new ClassServiceSpecs('dependency.id', DependencyService::class);
        $dependencyDefinition->setStatic(false);

        $serviceDefinition = new ClassServiceSpecs('main.service', TestService::class);
        $serviceDefinition
                ->setSetters(['setOptionalDependency' => [new ServiceReference('dependency.id')]])
                ->setStatic(false);

        $servicesFactory = (new ServicesFactory())->registerService($serviceDefinition, $dependencyDefinition);

        $firstInstance = $servicesFactory->get('main.service');
        $secondInstance = $servicesFactory->get('main.service');

        $this->assertNotSame($firstInstance, $secondInstance);

        $this->assertNotSame($firstInstance->getOptionalDependency(), $secondInstance->getOptionalDependency());

    }

    public function testClassBuilderSanityChecks()
    {
        $dependency = new \stdClass;

        $factory = $this->getMockBuilder(ServicesFactory::class)->getMock();
        $factory->expects($this->any())->method('get')->with('dependency.id')->willReturn($dependency);

        $builder = new ClassServiceBuilder();
        $builder->setServicesFactory($factory);

        // unknown class
        $serviceSpecs = new ClassServiceSpecs('id', 'nonExistentClass');
        $this->expectsException(
            function() use ($builder, $serviceSpecs)
            {
                $builder->build($serviceSpecs);
            },
            Exception::class, 'unknown', Exception::INVALID_SERVICE_SPECS);

    }

}

namespace Helpers;

use ObjectivePHP\Primitives\Collection\Collection;

class TestService
{

    protected $optionalDependency;
    protected $otherOptionalDependency;

    protected $args = [];

    public function __construct($arg1 = null, $arg2 = null)
    {
        $this->args = Collection::cast($this->args);
        $this->args['arg1'] = $arg1;
        $this->args['arg2'] = $arg2;
    }

    /**
     * @return mixed
     */
    public function getOtherOptionalDependency()
    {
        return $this->otherOptionalDependency;
    }

    /**
     * @param mixed $otherOptionalDependency
     *
     * @return $this
     */
    public function setOtherOptionalDependency($otherOptionalDependency)
    {
        $this->otherOptionalDependency = $otherOptionalDependency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOptionalDependency()
    {
        return $this->optionalDependency;
    }

    /**
     * @param mixed $optionalDependency
     *
     * @return $this
     */
    public function setOptionalDependency($optionalDependency)
    {
        $this->optionalDependency = $optionalDependency;
        return $this;
    }

}

class DependencyService
{

}
