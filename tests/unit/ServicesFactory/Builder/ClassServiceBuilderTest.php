<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Codeception\Test\Unit;
use Helpers\DependencyService;
use Helpers\TestService;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\ServiceReference;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

class ClassServiceBuilderTest extends Unit
{

    public function testBuildingUnsupportedServiceThrowsAnException()
    {
        $builder = new PrefabServiceBuilder();

        $serviceDefinition = $this->getMockBuilder(ServiceSpecificationInterface::class)->getMock();

        $this->assertFalse($builder->doesHandle($serviceDefinition));

        $this->setExpectedException(ServicesFactoryException::class, null, ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION);

        $builder->build($serviceDefinition);

    }

    public function testSimpleServiceIsBuilt()
    {
        $builder = new ClassServiceBuilder();
        $serviceDefinition = new ClassServiceSpecification('test.service', TestService::class);

        $this->assertTrue($builder->doesHandle($serviceDefinition));

        $service = $builder->build($serviceDefinition);

        $this->assertInstanceOf(TestService::class, $service);

        $serviceDefinition->setConstructorParams(['first' => 'x', 'second' => 'y']);

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
        $serviceSpecs = new ClassServiceSpecification('service.id', TestService::class);

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

        $serviceDefinition = new ClassServiceSpecification('main.service', 'stdClass');
        $serviceDefinition->setConstructorParams(['dependency' => new ServiceReference('dependency.id')]);

        $builder->build($serviceDefinition);

    }

    /**
     * This test is not quite unit, but helped a lot pinpointing a very
     * twisted issue with static service references
     *
     * @throws ServicesFactoryException
     */
    public function testStaticServiceReferenceSubstitutedByNewInstance()
    {
        $dependencyDefinition = new ClassServiceSpecification('dependency.id', DependencyService::class);
        $dependencyDefinition->setStatic(false);

        $serviceDefinition = new ClassServiceSpecification('main.service', TestService::class);
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
        $serviceSpecs = new ClassServiceSpecification('id', 'nonExistentClass');
        $this->setExpectedException(ServicesFactoryException::class, 'unknown', ServicesFactoryException::INVALID_SERVICE_SPECS);
        $builder->build($serviceSpecs);

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
