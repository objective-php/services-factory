<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Codeception\Test\Unit;
use Helpers\DependencyInterface;
use Helpers\DependencyService;
use Helpers\DependencyServiceImplementingAnInterface;
use Helpers\TestAutowiredService;
use Helpers\TestInterfaceAutowiredService;
use Helpers\TestService;
use ObjectivePHP\Config\Config;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\PrefabServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

class ClassServiceBuilderTest extends Unit
{

    public function testBuildingUnsupportedServiceThrowsAnException()
    {
        $builder = new PrefabServiceBuilder();

        $serviceDefinition = $this->getMockBuilder(ServiceSpecificationInterface::class)->getMock();

        $this->assertFalse($builder->doesHandle($serviceDefinition));

        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::INCOMPATIBLE_SERVICE_DEFINITION);

        $builder->build($serviceDefinition);

    }

    public function testSimpleServiceIsBuilt()
    {
        $builder = new ClassServiceBuilder();
        $serviceDefinition = new ClassServiceSpecification('test.service', TestService::class);

        $this->assertTrue($builder->doesHandle($serviceDefinition));

        $factory = $this->getMockBuilder(ServicesFactory::class)->getMock();
        $builder->setServicesFactory($factory);
        $service = $builder->build($serviceDefinition);

        $this->assertInstanceOf(TestService::class, $service);

        $serviceDefinition->setConstructorParams(['first' => 'x', 'second' => 'y']);

        // add params to service definition
        $service = $builder->build($serviceDefinition);
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertEquals(Collection::cast(['arg1' => 'x', 'arg2' => 'y']), $service->getArgs());

        // override service definition params at runtime
        $service = $builder->build($serviceDefinition, ['first' => 'OVERRIDDEN']);
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertEquals(Collection::cast(['arg1' => 'OVERRIDDEN', 'arg2' => 'y']), $service->getArgs());

    }

    public function testClassBuilderCallsSetters()
    {
        $serviceSpecs = new ClassServiceSpecification('service.id', TestService::class);

        $serviceSpecs->setSetters(
            [
                'setOptionalDependency' => ['optional dependency value'],
                'setOtherOptionalDependency' => ['service(other.service)']
            ]
        );

        $dependency = new \stdClass;
        $factory = (new ServicesFactory())->setConfig(new Config());

        $factory->registerService(new PrefabServiceSpecification('other.service', $dependency));

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

        $factory = (new ServicesFactory())->setConfig(new Config());
        $factory->registerService(new PrefabServiceSpecification('dependency.id', $dependency));

        $builder = new ClassServiceBuilder();
        $builder->setServicesFactory($factory);

        $serviceDefinition = new ClassServiceSpecification('main.service', 'stdClass');
        $serviceDefinition->setConstructorParams(['dependency' => 'service(dependency.id)']);

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
            ->setSetters(['setOptionalDependency' => ['service(dependency.id)']])
            ->setStatic(false);

        $servicesFactory = (new ServicesFactory())->setConfig(new Config())->registerService($serviceDefinition,
            $dependencyDefinition);

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
        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::INVALID_SERVICE_SPECS);
        $builder->build($serviceSpecs);

    }

    public function testImplementedInterfacesAreUsedAsAliasByDefault()
    {
        $factory = new ServicesFactory();
        $factory->registerService([
            'id' => 'test.dependency',
            'class' => DependencyServiceImplementingAnInterface::class
        ]);

        $this->assertTrue($factory->has(DependencyInterface::class));
    }

    public function testAutowiring()
    {
        $factory = new ServicesFactory();
        $factory->registerService(['id' => 'test.dependency', 'instance' => new DependencyService()]);

        $builder = new ClassServiceBuilder();
        $builder->setServicesFactory($factory);
        $instance = $builder->build(new ClassServiceSpecification('test.service', TestAutowiredService::class));

        $this->assertInstanceOf(DependencyService::class, $instance->getDependency());
    }

    public function testInterfaceBasedAutowiring()
    {
        $factory = new ServicesFactory();
        $factory->registerService([
            'id' => 'test.dependency',
            'class' => DependencyServiceImplementingAnInterface::class
        ]);

        $builder = new ClassServiceBuilder();
        $builder->setServicesFactory($factory);
        $instance = $builder->build(new ClassServiceSpecification('test.service',
            TestInterfaceAutowiredService::class));

        $this->assertTrue($factory->has(DependencyInterface::class));
        $this->assertInstanceOf(DependencyServiceImplementingAnInterface::class, $instance->getDependency());
    }

}

namespace Helpers;

use ObjectivePHP\Primitives\Collection\Collection;

class TestAutowiredService
{
    /** @var DependencyService */
    protected $dependency;

    /**
     * TestAutowiredService constructor.
     * @param DependencyService $dependency
     */
    public function __construct(DependencyService $dependency)
    {
        $this->dependency = $dependency;
    }

    /**
     * @return DependencyService
     */
    public function getDependency(): DependencyService
    {
        return $this->dependency;
    }

    /**
     * @param DependencyService $dependency
     */
    public function setDependency(DependencyService $dependency)
    {
        $this->dependency = $dependency;
    }

}

class TestInterfaceAutowiredService
{
    /** @var DependencyService */
    protected $dependency;

    /**
     * TestAutowiredService constructor.
     * @param DependencyService $dependency
     */
    public function __construct(DependencyInterface $dependency)
    {
        $this->dependency = $dependency;
    }

    /**
     * @return DependencyService
     */
    public function getDependency(): DependencyServiceImplementingAnInterface
    {
        return $this->dependency;
    }

    /**
     * @param DependencyService $dependency
     */
    public function setDependency(DependencyService $dependency)
    {
        $this->dependency = $dependency;
    }

}

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

    /**
     * @return array|static
     */
    public function getArgs()
    {
        return $this->args;
    }

}


class DependencyService
{

}


interface DependencyInterface
{

}

class DependencyServiceImplementingAnInterface implements DependencyInterface
{

}
