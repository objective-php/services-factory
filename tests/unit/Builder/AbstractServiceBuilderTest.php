<?php


namespace Tests\ObjectivePHP\ServicesFactory\Builder;


use Codeception\Test\Unit;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\Specification\AbstractServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\ClassServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\PrefabServiceSpecification;

class AbstractServiceBuilderTest extends Unit
{

    public function testSimpleFactory()
    {
        $rawSpecs = [
            'id' => 'service.id',
            'static' => false,
            'alias' => 'my.service',
            'aliases' => ['my.own.service', 'yes.it.is'],
            'class' => 'Service\SomeService',
            'params' => ['param' => 'value'],
            'setters' => ['setAnything' => 'value']
        ];

        $serviceSpecification = AbstractServiceSpecification::factory($rawSpecs);
        $this->assertEquals('service.id', $serviceSpecification->getId());
        $this->assertEquals('Service\SomeService', $serviceSpecification->getClass());
        $this->assertFalse($serviceSpecification->isStatic());
        $this->assertEquals(['my.service', 'my.own.service', 'yes.it.is'], $serviceSpecification->getAliases());
    }

    public function testAbstractServiceSpecsFactorySanityChecks()
    {
        // id is always mandatory, class is mandatory for ClassServiceSpecs only (the default one)
        $rawSpecs = [
        ];
        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::INCOMPLETE_SERVICE_SPECS);
        AbstractServiceSpecification::factory($rawSpecs);

    }

    public function testClassServiceSpecsFactoryFailsWithIncompleteServiceSpecification()
    {
        // class parameter is missing
        $rawSpecs = [
            'id' => 'service.id'
        ];
        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::INCOMPLETE_SERVICE_SPECS);
        ClassServiceSpecification::factory($rawSpecs);

    }

    public function testClassServiceSpecsFactoryFailsIfClassNameIsNotAString()
    {

        // class parameter is not a string
        $rawSpecs = [
            'id' => 'service.id',
            'class' => ['I am not a string']
        ];
        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::INVALID_SERVICE_SPECS);
        ClassServiceSpecification::factory($rawSpecs);

    }

    public function testAnyServiceSpecsCanBeBuiltUsingFactory()
    {
        $rawSpecs = [
            'id' => 'service.id',
            'type' => PrefabServiceSpecification::class,
            'instance' => $this
        ];

        $service = AbstractServiceSpecification::factory($rawSpecs);

        $this->assertInstanceOf(PrefabServiceSpecification::class, $service);

    }

    public function testObjectServiceSpecsFactorySanityChecks()
    {
        // missing instance parameter

        $rawSpecs = [
            'id' => 'service.id'
        ];
        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::INCOMPLETE_SERVICE_SPECS);
        PrefabServiceSpecification::factory($rawSpecs);

    }

    public function testFactoryCanGuessTypeForDefaultSpecs()
    {
        // class
        $rawSpecs = [
            'id' => 'service.id',
            'class' => 'Service\Class',
        ];

        $serviceSpecs = AbstractServiceSpecification::factory($rawSpecs);
        $this->assertInstanceOf(ClassServiceSpecification::class, $serviceSpecs);

        // prefab
        $rawSpecs = [
            'id' => 'service.id',
            'instance' => new \stdClass(),
        ];

        $serviceSpecs = AbstractServiceSpecification::factory($rawSpecs);
        $this->assertInstanceOf(PrefabServiceSpecification::class, $serviceSpecs);


        // ambiguous
        $rawSpecs = [
            'id' => 'service.id',
            'class' => 'Service\Class',
            'instance' => new \stdClass()
        ];

        $this->expectException(ServicesFactoryException::class);
        $this->expectExceptionCode(ServicesFactoryException::AMBIGUOUS_SERVICE_SPECS);
        $serviceSpecs = AbstractServiceSpecification::factory($rawSpecs);

    }

}

namespace Service;

class SomeService
{
    public function setAnything($value)
    {

    }
}
