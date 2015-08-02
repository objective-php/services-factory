<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Helpers\TestService;
use ObjectivePHP\PHPUnit\TestCase;
use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Exception;
use ObjectivePHP\ServicesFactory\Factory;
use ObjectivePHP\ServicesFactory\Reference;
use ObjectivePHP\ServicesFactory\Specs\ClassServiceSpecs;

class ClassServiceBuilderTest extends TestCase
{

    public function testFactoryAccessors()
    {
        $factory = $this->getMock(Factory::class);

        $builder = new ClassServiceBuilder();
        $setterReturn = $builder->setFactory($factory);

        $this->assertAttributeSame($factory, 'factory', $builder);
        $this->assertSame($factory, $builder->getFactory());

        $this->assertSame($builder, $setterReturn);


    }

    public function testBuildingUnsupportedServiceThrowsAnException()
    {
        $builder = new ClassServiceBuilder();

        $serviceDefinition = new ClassServiceSpecs('test.service', TestService::class);

        // change handled services definition
        $this->setObjectAttribute($builder, 'handledSpecs', ['Whatever']);

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
                'setOtherOptionalDependency' => [new Reference('other.service')]
            ]
        );

        $dependency = new \stdClass;
        $factory = $this->getMock(Factory::class);
        $factory->expects($this->once())->method('get')->with('other.service')->willReturn($dependency);

        $builder = new ClassServiceBuilder();
        $builder->setFactory($factory);

        $service = $builder->build($serviceSpecs);

        // test by transitivity
        $this->assertEquals('optional dependency value', $service->getOptionalDependency());
        $this->assertSame($dependency, $service->getOtherOptionalDependency());

    }

    public function testClassBuilderSubstitutesServiceReferences()
    {

        $dependency = new \stdClass;

        $factory = $this->getMock(Factory::class);
        $factory->expects($this->once())->method('get')->with('dependency.id')->willReturn($dependency);

        $builder = new ClassServiceBuilder();
        $builder->setFactory($factory);

        $serviceDefinition = new ClassServiceSpecs('main.service', 'stdClass');
        $serviceDefinition->setParams(['dependency' => new Reference('dependency.id')]);

        $builder->build($serviceDefinition);

    }

    public function testClassBuilderSanityChecks()
    {
        $dependency = new \stdClass;

        $factory = $this->getMock(Factory::class);
        $factory->expects($this->any())->method('get')->with('dependency.id')->willReturn($dependency);

        $builder = new ClassServiceBuilder();
        $builder->setFactory($factory);

        // unknown class
        $serviceSpecs = new ClassServiceSpecs('id', 'nonExistentClass');
        $this->expectsException(
            function() use ($builder, $serviceSpecs)
            {
                $builder->build($serviceSpecs);
            },
            Exception::class, 'unknown', Exception::INVALID_SERVICE_DEFINITION);

    }

}

namespace Helpers;

use ObjectivePHP\Primitives\Collection;

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